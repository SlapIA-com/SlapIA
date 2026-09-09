<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "factures" — fichiers PDF de facture attachés à un client.
 * chemin_fichier pointe vers le disque "local" (storage/app/factures/...),
 * jamais exposé directement — toujours servi via un contrôleur qui vérifie
 * la propriété (voir InvoiceController / Admin\AdminInvoiceController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type')->default('application/pdf');
            $table->unsignedBigInteger('taille_octets')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
