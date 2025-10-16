<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand ENUM to include both old and new values
        DB::statement("
            ALTER TABLE nfc_scans
            MODIFY status ENUM(
                'good','bad',
                'available','borrowed','under_repair','stolen','missing_lost'
            ) NULL
        ");

        // Step 2: Remap old values to new ones
        DB::table('nfc_scans')->where('status', 'good')->update(['status' => 'available']);
        DB::table('nfc_scans')->where('status', 'bad')->update(['status' => 'under_repair']);

        // Step 3: Shrink ENUM to final set (drop good/bad)
        DB::statement("
            ALTER TABLE nfc_scans
            MODIFY status ENUM(
                'available','borrowed','under_repair','stolen','missing_lost'
            ) NULL
        ");
    }

    public function down(): void
    {
        // Reverse: allow both sets again
        DB::statement("
            ALTER TABLE nfc_scans
            MODIFY status ENUM(
                'good','bad',
                'available','borrowed','under_repair','stolen','missing_lost'
            ) NULL
        ");

        // Map back to old set (anything not in good/bad becomes NULL)
        DB::table('nfc_scans')->where('status', 'available')->update(['status' => 'good']);
        DB::table('nfc_scans')->where('status', 'under_repair')->update(['status' => 'bad']);
        DB::table('nfc_scans')->whereIn('status', ['borrowed','stolen','missing_lost'])->update(['status' => null]);

        // Finally restrict to only good/bad
        DB::statement("
            ALTER TABLE nfc_scans
            MODIFY status ENUM('good','bad') NULL
        ");
    }
};
