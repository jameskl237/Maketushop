<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'url' => 'media/placeholder.jpg',
            'type' => 'image',
            'is_principal' => false,
            // product_id will be set by seeder
        ];
    }
}
