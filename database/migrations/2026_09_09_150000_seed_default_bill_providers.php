<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // `PaymentController` validait jusqu'ici les fournisseurs de factures
    // contre l'enum `BillProviderType` seul, sans jamais consulter cette
    // table — la case `is_active` de l'admin n'avait donc aucun effet.
    // Une ligne par cas de l'enum, toutes actives par défaut, pour que le
    // passage à une vraie vérification (`is_active` en base) ne change
    // rien au comportement existant tant qu'un opérateur ne désactive pas
    // explicitement un fournisseur.
    public function up(): void
    {
        $now = now();

        // insertOrIgnore : ce projet a déjà des lignes réelles créées à la
        // main via l'admin avant cette migration (test manuel de
        // BillProviderResource) — ne pas planter dessus, ni les écraser.
        DB::table('bill_providers')->insertOrIgnore([
            ['type' => 'senelec', 'name' => 'Senelec', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'woyofal', 'name' => 'Woyofal', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'senEau', 'name' => "SEN'EAU", 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'canalPlus', 'name' => 'Canal+', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'rapido', 'name' => 'Rapido', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('bill_providers')->whereIn('type', ['senelec', 'woyofal', 'senEau', 'canalPlus', 'rapido'])->delete();
    }
};
