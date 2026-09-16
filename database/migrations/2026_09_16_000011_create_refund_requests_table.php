<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demande de remboursement créée à l'annulation d'une commande payée.
 * Le virement lui-même est effectué à la main par l'admin depuis le
 * back-office CinetPay, puis marqué ici comme remboursé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status')->default('pending'); // pending, refunded, rejected
            $table->string('reason')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_notes')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
