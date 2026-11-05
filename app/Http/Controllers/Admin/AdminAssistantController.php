<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RechargeCreditsRequest;
use App\Http\Requests\Admin\UpdateBotQuotaRequest;
use App\Models\ShopBot;
use App\Models\ShopBotCreditUsage;
use App\Services\Botisimo\BotisimoClient;
use App\Services\Botisimo\ClientDirectory;
use App\Services\Botisimo\CreditLedger;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminAssistantController extends Controller
{
    /** Empêche un double clic de débiter deux fois le Mobile Money. */
    private const RECHARGE_COOLDOWN_SECONDS = 60;

    public function index(CreditLedger $credits)
    {
        $bots = ShopBot::with('shop.user:id,name,email')
            ->withCount(['catalogItems as sync_errors_count' => fn ($query) => $query->whereNotNull('sync_error')])
            ->latest('id')
            ->paginate(20);

        $used = ShopBotCreditUsage::whereIn('shop_id', $bots->pluck('shop_id'))
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('shop_id, SUM(credits) as total')
            ->groupBy('shop_id')
            ->pluck('total', 'shop_id');

        return Inertia::render('Backoffice/Admin/Assistant/Index', [
            'balance' => $credits->globalBalance(),
            'lowBalanceThreshold' => (int) config('botisimo.credits.low_balance_threshold'),
            'defaultQuota' => (int) config('botisimo.credits.monthly_quota'),
            'creditsThisMonth' => (int) ShopBotCreditUsage::where('created_at', '>=', now()->startOfMonth())->sum('credits'),
            'bots' => $bots->through(fn (ShopBot $bot) => [
                'id' => $bot->id,
                'shop_name' => $bot->shop?->name,
                'vendor_name' => $bot->shop?->user?->name,
                'status' => $bot->status,
                'whatsapp_phone_number' => $bot->whatsapp_phone_number,
                'quality_rating' => $bot->quality_rating,
                'last_error' => $bot->last_error,
                'sync_errors_count' => $bot->sync_errors_count,
                'credits_month' => (int) ($used[$bot->shop_id] ?? 0),
                'monthly_credit_quota' => $bot->monthly_credit_quota,
            ]),
        ]);
    }

    public function recharge(RechargeCreditsRequest $request, BotisimoClient $client)
    {
        if (! Cache::add('botisimo:recharge', 1, self::RECHARGE_COOLDOWN_SECONDS)) {
            return back()->with('error', 'Une recharge vient d\'être lancée. Patientez une minute.');
        }

        $data = $request->validated();

        try {
            $client->rechargeCredits([
                'amount' => (int) $data['amount'],
                'operator' => $data['operator'],
                'phone_number' => ClientDirectory::phone($data['phone_number']),
                'country_code' => PhoneNumber::defaultCountry(),
                'currency' => config('payments.currency', 'XAF'),
                'callback_url' => route('backoffice.admin.assistant.index'),
            ]);
        } catch (BotisimoException $e) {
            Cache::forget('botisimo:recharge');

            return back()->with('error', $e->userMessage());
        }

        Cache::forget('botisimo:balance');
        Log::info('Recharge Botisimo demandée', [
            'admin_id' => $request->user()->id,
            'amount' => (int) $data['amount'],
            'operator' => $data['operator'],
        ]);

        return back()->with('success', 'Recharge demandée. Validez le paiement sur le téléphone.');
    }

    public function updateQuota(UpdateBotQuotaRequest $request, ShopBot $shopBot)
    {
        $shopBot->update($request->validated());

        return back()->with('success', 'Quota mis à jour.');
    }
}
