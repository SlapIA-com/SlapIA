<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "contact_siteweb" — messages du formulaire de contact public.
 * client_id est résolu automatiquement si l'email correspond à un compte
 * existant (voir includes/contact-form.php), sinon NULL (prospect).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_siteweb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('prenom');
            $table->string('nom')->nullable();
            $table->string('nom_entreprise')->nullable();
            $table->string('email');
            $table->string('sujet');
            $table->text('message');
            $table->boolean('prise_de_contact_ok')->default(false);
            $table->timestamp('date_creation')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_siteweb');
    }
};
