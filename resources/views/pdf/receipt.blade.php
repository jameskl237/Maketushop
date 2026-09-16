<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $order->order_number }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 21px; font-weight: bold; letter-spacing: .5px; }
        .brand small { display: block; font-size: 10px; font-weight: normal; color: #6b7280; letter-spacing: 0; margin-top: 2px; }
        .badge { float: right; margin-top: -34px; border: 1px solid #059669; color: #059669;
                 padding: 5px 11px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { color: #6b7280; width: 108px; }
        .cols { margin: 16px 0 20px; }
        .cols td { width: 50%; vertical-align: top; padding-right: 16px; }
        .section { font-size: 10px; text-transform: uppercase; letter-spacing: .6px;
                   color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 6px; }
        .items th { background: #f3f4f6; text-align: left; padding: 7px 8px; font-size: 10px;
                    text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #d1d5db; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .num { text-align: right; }
        .totals { margin-top: 12px; width: 265px; float: right; }
        .totals td { padding: 4px 0; }
        .totals .grand td { border-top: 2px solid #111827; font-size: 14px; font-weight: bold; padding-top: 7px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e5e7eb;
                  padding-top: 7px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">MaketuShop<small>Reçu de paiement</small></div>
        <div class="badge">Payée</div>
    </div>

    <table class="meta">
        <tr><td class="label">Reçu n°</td><td><strong>{{ $order->order_number }}</strong></td></tr>
        <tr><td class="label">Date de paiement</td><td>{{ optional($order->paid_at)->format('d/m/Y à H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Moyen de paiement</td><td>{{ $paymentMethod }}</td></tr>
        @if ($transactionId)
            <tr><td class="label">Transaction</td><td>{{ $transactionId }}</td></tr>
        @endif
    </table>

    <table class="cols"><tr>
        <td>
            <div class="section">Client</div>
            {{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>
            @if ($order->phone_number){{ $order->phone_number }}<br>@endif
            @if ($order->user?->email){{ $order->user->email }}@endif
        </td>
        <td>
            <div class="section">Livraison</div>
            {{ $order->delivery_address ?: '—' }}
        </td>
    </tr></table>

    <table class="items">
        <thead>
            <tr>
                <th>Article</th>
                <th>Vendeur</th>
                <th class="num">Qté</th>
                <th class="num">P.U.</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['vendor'] }}</td>
                    <td class="num">{{ $item['quantity'] }}</td>
                    <td class="num">{{ number_format($item['unit_price'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($item['total'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total</td><td class="num">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</td></tr>
        <tr class="grand"><td>Total payé</td><td class="num">{{ number_format($total, 0, ',', ' ') }} FCFA</td></tr>
    </table>

    <div class="footer">
        Reçu généré le {{ $generatedAt->format('d/m/Y à H:i') }} — MaketuShop.
        Ce document atteste du paiement de la commande {{ $order->order_number }}.
    </div>
</body>
</html>
