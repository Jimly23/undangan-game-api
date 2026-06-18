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
        Schema::create('undangans', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();
            $table->string('tema')->default('aruma-jawa');

            // MEMPELAI WANITA
            $table->text('foto_wanita')->nullable();
            $table->string('nama_lengkap_wanita')->nullable();
            $table->string('nama_panggilan_wanita', 100)->nullable();
            $table->string('nama_ayah_wanita')->nullable();
            $table->string('nama_ibu_wanita')->nullable();
            $table->text('alamat_wanita')->nullable();
            $table->string('instagram_wanita')->nullable();
            $table->string('whatsapp_wanita', 20)->nullable();

            // MEMPELAI PRIA
            $table->text('foto_pria')->nullable();
            $table->string('nama_lengkap_pria')->nullable();
            $table->string('nama_panggilan_pria', 100)->nullable();
            $table->string('nama_ayah_pria')->nullable();
            $table->string('nama_ibu_pria')->nullable();
            $table->text('alamat_pria')->nullable();
            $table->string('instagram_pria')->nullable();
            $table->string('whatsapp_pria', 20)->nullable();

            // GALERI
            $table->json('galeri')->nullable();

            // LOVE STORY
            $table->json('love_stories')->nullable();

            // WAKTU & TEMPAT
            $table->text('alamat_akad')->nullable();
            $table->dateTime('tanggal_akad')->nullable();
            $table->string('jam_mulai_akad', 10)->nullable();
            $table->string('jam_selesai_akad', 10)->nullable();

            $table->text('alamat_resepsi')->nullable();
            $table->dateTime('tanggal_resepsi')->nullable();
            $table->string('jam_mulai_resepsi', 10)->nullable();
            $table->string('jam_selesai_resepsi', 10)->nullable();

            $table->text('link_google_maps')->nullable();

            // DRESSCODE
            $table->json('dresscodes')->nullable();

            // GIFT
            $table->string('nomor_rekening_pria', 50)->nullable();
            $table->string('nama_bank_pria', 100)->nullable();
            $table->string('atas_nama_pria')->nullable();

            $table->string('nomor_rekening_wanita', 50)->nullable();
            $table->string('nama_bank_wanita', 100)->nullable();
            $table->string('atas_nama_wanita')->nullable();

            // QR
            $table->text('qr_code')->nullable();

            // MUSIK
            $table->text('musik')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('undangans');
    }
};
