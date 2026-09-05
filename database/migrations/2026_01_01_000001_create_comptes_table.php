<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "comptes" — identifiants de connexion (email + mot de passe).
 * Un compte a un et un seul client associé (relation 1-1 via clients.compte_id).
 * Reverse-engineered from includes/users.php, includes/admin-accounts.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('mot_de_passe_hash')->nullable();
            $table->boolean('mail_avis')->default(true);
            $table->string('reset_token', 64)->nullable();
            $table->timestamp('reset_token_expiry')->nullable();
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
