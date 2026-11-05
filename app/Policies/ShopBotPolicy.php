<?php

namespace App\Policies;

use App\Models\ShopBot;
use App\Models\User;

class ShopBotPolicy
{
    public function manage(User $user, ShopBot $bot): bool
    {
        return $bot->shop?->user_id === $user->id;
    }
}
