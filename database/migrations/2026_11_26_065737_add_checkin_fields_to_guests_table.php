<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            if (!Schema::hasColumn('guests', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->after('nama_tamu');
            }
            if (!Schema::hasColumn('guests', 'is_checked_in')) {
                $table->boolean('is_checked_in')->default(false)->after('qr_token');
            }
            if (!Schema::hasColumn('guests', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('is_checked_in');
            }
            if (!Schema::hasColumn('guests', 'checked_in_by')) {
                $table->string('checked_in_by', 100)->nullable()->after('checked_in_at');
            }
        });

        // Generate secure unique qr_token for existing guests without altering other fields
        $existingGuests = DB::table('guests')->whereNull('qr_token')->orWhere('qr_token', '')->get();
        foreach ($existingGuests as $guest) {
            DB::table('guests')
                ->where('id', $guest->id)
                ->update(['qr_token' => Guest::generateUniqueQrToken()]);
        }

        Schema::table('guests', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable(false)->unique()->change();
            $table->index(['undangan_id', 'is_checked_in'], 'guests_undangan_checkin_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex('guests_undangan_checkin_idx');
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'is_checked_in', 'checked_in_at', 'checked_in_by']);
        });
    }
};
