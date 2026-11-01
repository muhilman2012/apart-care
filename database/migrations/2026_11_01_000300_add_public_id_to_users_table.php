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
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id', 64)->nullable()->after('id');
        });

        DB::table('users')->select('id')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'public_id' => hash('sha256', $user->id.'|'.Str::uuid().'|'.random_bytes(32)),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
