<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->unsignedBigInteger('lock_version')->default(0)->after('status');
        });

        Schema::create('agendamento_data_locks', function (Blueprint $table) {
            $table->date('data_agendada')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_data_locks');

        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });
    }
};
