<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hexa_roles', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('team_id')->constrained('hexa_roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hexa_roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
