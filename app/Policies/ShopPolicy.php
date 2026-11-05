<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAssistant(User $user, Shop $shop): bool
    {
        return $shop->user_id === $user->id;
    }

    public function useAssistant(User $user, Shop $shop): bool
    {
        return $shop->user_id === $user->id && $shop->hasAssistantAccess();
    }
}
