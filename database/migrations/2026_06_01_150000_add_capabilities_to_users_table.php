<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_vendeur')->default(false)->after('role');
            $table->boolean('is_prestataire')->default(false)->after('is_vendeur');
        });

        // Les comptes "supplier" existants pouvaient déjà créer produits ET services :
        // on les marque vendeur + prestataire pour ne rien casser.
        DB::table('users')
            ->where('role', User::ROLE_SUPPLIER)
            ->update(['is_vendeur' => true, 'is_prestataire' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_vendeur', 'is_prestataire']);
        });
    }
};
