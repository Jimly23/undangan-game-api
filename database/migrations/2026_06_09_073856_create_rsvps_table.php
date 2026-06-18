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
        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('undangan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nama');
            $table->string('nomor_telepon', 20);

            $table->string('email')->nullable();

            $table->enum('status', [
                'hadir',
                'tidak_hadir'
            ]);

            $table->text('pesan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
