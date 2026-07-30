<?php

return [
    'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 10),
    'min_withdrawal' => env('MIN_WITHDRAWAL', 500),
    'currency' => 'XOF',
    'cinetpay_channels' => ['MOBILE', 'CARD', 'ALL'],
];
