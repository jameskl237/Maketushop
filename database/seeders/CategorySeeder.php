<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed categories and essential service domains.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Alimentation', 'description' => 'Produits alimentaires, épicerie et boissons.'],
            ['name' => 'Fruits et Legumes', 'description' => 'Fruits frais, légumes et produits maraîchers.'],
            ['name' => 'Boulangerie et Patisserie', 'description' => 'Pain, pâtisserie, viennoiserie et desserts.'],
            ['name' => 'Viandes et Poissons', 'description' => 'Viandes, poissons, charcuterie et produits de mer.'],
            ['name' => 'Restauration', 'description' => 'Repas prêts, snacks, fast-food et cuisine locale.'],
            ['name' => 'Mode et Vêtements', 'description' => 'Vêtements, chaussures, accessoires de mode.'],
            ['name' => 'Beauté et Cosmetique', 'description' => 'Maquillage, soins de la peau, parfums et coiffure.'],
            ['name' => 'Santé et Bien-être', 'description' => 'Parapharmacie, compléments, bien-être et fitness.'],
            ['name' => 'Bebe et Enfants', 'description' => 'Articles bébé, puériculture, vêtements et jouets enfants.'],
            ['name' => 'Maison et Deco', 'description' => 'Meubles, décoration, linge de maison, vaisselle.'],
            ['name' => 'Electromenager', 'description' => 'Petit et gros électroménager pour la maison.'],
            ['name' => 'Informatique et High-Tech', 'description' => 'Ordinateurs, accessoires tech, périphériques, gadgets.'],
            ['name' => 'Telephonie et Accessoires', 'description' => 'Smartphones, coques, chargeurs et accessoires mobiles.'],
            ['name' => 'Bricolage et Quincaillerie', 'description' => 'Outillage, matériaux et équipements de bricolage.'],
            ['name' => 'Auto et Moto', 'description' => 'Pièces, accessoires et équipements automobiles/motos.'],
            ['name' => 'Fournitures Scolaires et Bureau', 'description' => 'Papeterie, fournitures d’école et matériel de bureau.'],
            ['name' => 'Librairie et Culture', 'description' => 'Livres, musique, jeux éducatifs et culture.'],
            ['name' => 'Sport et Loisirs', 'description' => 'Équipements sportifs, loisirs et activités de plein air.'],
            ['name' => 'Services Menagers', 'description' => 'Nettoyage, pressing, entretien et aide domestique.'],
            ['name' => 'Services Professionnels', 'description' => 'Prestations professionnelles, consulting et assistance.'],
            ['name' => 'Reparation et Maintenance', 'description' => 'Réparation appareils, maintenance et dépannage.'],
            ['name' => 'Evenementiel', 'description' => 'Organisation d’événements, décoration et location.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'image' => null,
                ],
            );
        }
    }
}

