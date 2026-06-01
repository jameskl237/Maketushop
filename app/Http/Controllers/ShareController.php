<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShareController extends Controller
{
    /**
     * Page de partage produit : meta Open Graph pour la vignette WhatsApp,
     * puis redirection vers la vraie fiche.
     */
    public function product(Product $product): View
    {
        $product->load(['medias:id,product_id,url,type,is_principal']);

        $image = optional(
            $product->medias->firstWhere('is_principal', true) ?? $product->medias->first()
        )->full_url ?? asset('/images/Maketu_logo.png');

        return view('share', [
            'title' => $product->name,
            'description' => Str::limit(strip_tags((string) ($product->description ?: $product->long_description)), 150)
                ?: 'Découvrez ce produit sur MaketuShop.',
            'image' => $image,
            'url' => route('products.show', ['product' => $product->id]),
        ]);
    }

    /**
     * Page de partage service.
     */
    public function service(Service $service): View
    {
        $service->load(['medias:id,service_id,url,type,is_principal']);

        $image = optional(
            $service->medias->firstWhere('is_principal', true) ?? $service->medias->first()
        )->full_url ?? asset('/images/Maketu_logo.png');

        return view('share', [
            'title' => $service->title,
            'description' => Str::limit(strip_tags((string) ($service->description ?: $service->long_description)), 150)
                ?: 'Découvrez ce service sur MaketuShop.',
            'image' => $image,
            'url' => route('services.show', ['service' => $service->id]),
        ]);
    }
}
