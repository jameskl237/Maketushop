<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBalance extends Model
{
    protected $fillable = [
        'user_id',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'available_balance' => 'integer',
        'pending_balance' => 'integer',
        'total_earned' => 'integer',
        'total_withdrawn' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(VendorWalletTransaction::class, 'user_id', 'user_id');
    }

    public function addPending(int $amount, string $description, ?Model $reference = null): VendorWalletTransaction
    {
        return $this->recordTransaction('order_escrow', $description, $amount, 'credit', $reference, function () use ($amount) {
            $this->increment('pending_balance', $amount);
            $this->increment('total_earned', $amount);
        });
    }

    public function releaseToAvailable(int $amount, string $description, ?Model $reference = null): VendorWalletTransaction
    {
        return $this->recordTransaction('escrow_release', $description, $amount, 'credit', $reference, function () use ($amount) {
            $this->decrement('pending_balance', $amount);
            $this->increment('available_balance', $amount);
        });
    }

    public function withdraw(int $amount, string $description, ?Model $reference = null): VendorWalletTransaction
    {
        return $this->recordTransaction('withdrawal', $description, $amount, 'debit', $reference, function () use ($amount) {
            $this->decrement('available_balance', $amount);
            $this->increment('total_withdrawn', $amount);
        });
    }

    public function holdForWithdrawal(int $amount): void
    {
        $this->decrement('available_balance', $amount);
    }

    public function releaseHeld(int $amount): void
    {
        $this->increment('available_balance', $amount);
    }

    private function recordTransaction(string $type, string $description, int $amount, string $direction, ?Model $reference, callable $update): VendorWalletTransaction
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($type, $description, $amount, $direction, $reference, $update) {
            $balanceBefore = $this->{$direction === 'credit' ? 'pending_balance' : 'available_balance'};

            $update();

            $transaction = VendorWalletTransaction::create([
                'user_id' => $this->user_id,
                'type' => $type,
                'description' => $description,
                'amount' => $amount,
                'direction' => $direction,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->{$direction === 'credit' ? 'pending_balance' : 'available_balance'},
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference ? $reference->getKey() : null,
            ]);

            return $transaction;
        });
    }

    public static function initForUser(int $userId): self
    {
        return static::firstOrCreate(['user_id' => $userId], [
            'available_balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);
    }
}
