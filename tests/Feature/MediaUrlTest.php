<?php

namespace Tests\Feature;

use App\Support\CategoryImage;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    public function test_disk_paths_become_root_relative_urls_independent_of_app_url(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->assertSame('/storage/products/a.jpg', MediaUrl::from('products/a.jpg'));
        $this->assertSame('/storage/products/a.jpg', MediaUrl::from('public/products/a.jpg'));
        $this->assertSame('/storage/products/a.jpg', MediaUrl::from('storage/products/a.jpg'));
        $this->assertSame('/images/mode.png', MediaUrl::from('images/mode.png'));
        $this->assertSame('/images/mode.png', MediaUrl::from('/images/mode.png'));
        $this->assertSame('https://cdn.example.com/x.jpg', MediaUrl::from('https://cdn.example.com/x.jpg'));
        $this->assertSame('/storage/products/a.jpg', MediaUrl::from('http://localhost/storage/products/a.jpg'));
        $this->assertSame('/storage/products/a.jpg', MediaUrl::from('http://127.0.0.1:8000/storage/products/a.jpg'));
        $this->assertNull(MediaUrl::from(null));
        $this->assertNull(MediaUrl::from(''));
    }

    public function test_public_files_are_served_even_without_storage_symlink(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('photo.jpg')->store('products', 'public');

        $this->get('/storage/' . $path)->assertOk();
        $this->get('/storage/products/absent.jpg')->assertNotFound();
        $this->get('/storage/../.env')->assertNotFound();
    }

    public function test_categories_get_a_matching_theme_image(): void
    {
        $this->assertSame('/images/mode.webp', CategoryImage::for(null, 'Mode et Vêtements'));
        $this->assertSame('/images/electronique.webp', CategoryImage::for(null, 'Informatique et High-Tech'));
        $this->assertSame('/images/electronique.webp', CategoryImage::for(null, 'Telephonie et Accessoires'));
        $this->assertSame('/images/beaute.webp', CategoryImage::for(null, 'Santé et Bien-être'));
        $this->assertSame('/images/alimentation.webp', CategoryImage::for('viandes-et-poissons', 'Viandes et Poissons'));
    }
}
