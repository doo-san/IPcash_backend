<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Les opérateurs crédit peuvent désormais avoir un logo téléversé
    // depuis l'admin, comme les opérateurs mobile money, les fournisseurs
    // de factures et les institutions financières. `logo_url` ne stocke
    // que le chemin relatif (comportement natif de `FileUpload`) ;
    // l'accesseur `HasLogo::logoUrl()` reconstruit l'URL absolue.
    public function up(): void
    {
        Schema::table('credit_operators', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('credit_operators', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }
};
