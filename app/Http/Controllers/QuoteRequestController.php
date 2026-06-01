<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Http\Request;

class QuoteRequestController extends Controller
{
    /**
     * Enregistre une demande de devis et renvoie les infos nécessaires
     * pour ouvrir WhatsApp côté client (numéro du prestataire).
     */
    public function store(Request $request, Service $service)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'budget' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->loadMissing(['shop:id,name,phone,user_id', 'shop.user:id,phone']);

        QuoteRequest::create([
            'service_id' => $service->id,
            'user_id' => $request->user()?->id,
            'customer_name' => $data['customer_name'] ?? $request->user()?->name,
            'customer_phone' => $data['customer_phone'] ?? $request->user()?->phone,
            'budget' => $data['budget'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => QuoteRequest::STATUS_PENDING,
        ]);

        return back()->with('success', 'Votre demande de devis a bien été enregistrée.');
    }
}
