<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Order\OrderService;
use App\Services\Refund\RefundService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Surveille les commandes payées qui ne sont jamais confirmées comme livrées.
 *
 *   paiement + 72 h ......... on propose au client d'annuler et d'être remboursé
 *   refus d'annuler + 7 j ... la commande est annulée d'office et remboursée
 */
class ProcessOrderDelays extends Command
{
    protected $signature = 'orders:process-delays {--dry-run : Affiche les commandes concernées sans rien modifier}';

    protected $description = 'Propose l\'annulation après 72 h sans livraison confirmée, puis annule d\'office après 7 jours';

    public function __construct(
        private OrderService $orders,
        private RefundService $refunds
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $offered = $this->offerCancellations($dryRun);
        $cancelled = $this->autoCancel($dryRun);

        $this->newLine();
        $this->info(sprintf(
            '%s%d annulation(s) proposée(s), %d commande(s) annulée(s) d\'office.',
            $dryRun ? '[simulation] ' : '',
            $offered,
            $cancelled
        ));

        return self::SUCCESS;
    }

    /** Étape 1 : 72 h après le paiement, sans confirmation de réception. */
    private function offerCancellations(bool $dryRun): int
    {
        $orders = Order::awaitingDelivery()
            ->whereNull('cancellation_offered_at')
            ->where('paid_at', '<=', now()->subHours(Order::CANCELLATION_OFFER_HOURS))
            ->get();

        foreach ($orders as $order) {
            $this->line(sprintf(
                '  proposition d\'annulation → %s (payée le %s)',
                $order->order_number,
                $order->paid_at->format('d/m/Y H:i')
            ));

            if ($dryRun) {
                continue;
            }

            try {
                $this->orders->offerCancellation($order);
            } catch (\Throwable $e) {
                $this->reportFailure('offer', $order, $e);
            }
        }

        return $orders->count();
    }

    /** Étape 2 : le client a refusé d'annuler et rien n'a bougé depuis 7 jours. */
    private function autoCancel(bool $dryRun): int
    {
        $orders = Order::awaitingDelivery()
            ->whereNotNull('cancellation_declined_at')
            ->where('cancellation_declined_at', '<=', now()->subDays(Order::AUTO_CANCEL_DAYS))
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            $this->line(sprintf(
                '  annulation d\'office → %s (refus le %s)',
                $order->order_number,
                $order->cancellation_declined_at->format('d/m/Y H:i')
            ));

            if ($dryRun) {
                $count++;
                continue;
            }

            try {
                $this->refunds->cancelAndRefund($order, RefundService::REASON_AUTOMATIC, automatic: true);
                $count++;
            } catch (\Throwable $e) {
                $this->reportFailure('auto-cancel', $order, $e);
            }
        }

        return $count;
    }

    private function reportFailure(string $step, Order $order, \Throwable $e): void
    {
        $this->error("    échec ({$step}) sur {$order->order_number} : {$e->getMessage()}");

        Log::error('Orders.process_delays_failed', [
            'step' => $step,
            'order_id' => $order->id,
            'error' => $e->getMessage(),
        ]);
    }
}
