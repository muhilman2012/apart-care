<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('tickets')->orderBy('id')->each(function (object $ticket): void {
            DB::table('tickets')->where('id', $ticket->id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unique('uuid');
        });

        DB::table('whatsapp_message_logs')
            ->where('status', 'failed')
            ->where('error', "Cannot read properties of undefined (reading 'id')")
            ->update([
                'status' => 'sent',
                'error' => null,
                'sent_at' => DB::raw('created_at'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropUnique('tickets_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
