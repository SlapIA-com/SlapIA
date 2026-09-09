<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le numéro de téléphone au formulaire de contact public
 * (ContactController::store / resources/js/Pages/Contact.tsx).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_siteweb', function (Blueprint $table) {
            $table->string('telephone')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('contact_siteweb', function (Blueprint $table) {
            $table->dropColumn('telephone');
        });
    }
};
