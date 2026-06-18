<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Undangan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('undangans', function (Blueprint $table) {
            if (!Schema::hasColumn('undangans', 'client_token')) {
                $table->string('client_token', 64)->nullable();
            }
        });

        // Initialize empty client_tokens with a new random string
        $undangans = Undangan::all();
        foreach ($undangans as $u) {
            $u->client_token = Str::random(32);
            $u->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('undangans', function (Blueprint $table) {
            if (Schema::hasColumn('undangans', 'client_token')) {
                $table->dropColumn('client_token');
            }
        });
    }
};
