<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clé unique du paramètre (ex: reservations.block_weekends)');
            $table->text('value')->comment('Valeur du paramètre (JSON pour les tableaux/objets)');
            $table->string('type', 20)->default('string')->comment('Type: boolean, string, integer, float, array');
            $table->string('group', 50)->default('general')->comment('Groupe de paramètres (reservations, notifications, etc.)');
            $table->text('description')->nullable()->comment('Description du paramètre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
