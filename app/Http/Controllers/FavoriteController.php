<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    /**
     * Types de modèles pouvant être mis en favori.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const FAVORITABLE_TYPES = [
        'product' => Product::class,
        'service' => Service::class,
    ];

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(self::FAVORITABLE_TYPES))],
            'id' => ['required', 'integer'],
        ]);

        $modelClass = self::FAVORITABLE_TYPES[$data['type']];
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $modelClass::findOrFail($data['id']);

        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('favoritable_type', $model->getMorphClass())
            ->where('favoritable_id', $model->getKey())
            ->first();

        if ($favorite) {
            $favorite->delete();
            $favorited = false;
        } else {
            Favorite::create([
                'user_id' => $request->user()->id,
                'favoritable_type' => $model->getMorphClass(),
                'favoritable_id' => $model->getKey(),
            ]);
            $favorited = true;
        }

        return back()->with('favorited', $favorited);
    }
}
