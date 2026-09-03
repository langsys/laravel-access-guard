<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\Support\Config;

return new class extends Migration
{
    public function up(): void
    {
        $table = Config::table('role_has_permissions');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on(Config::table('roles'))->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on(Config::table('permissions'))->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Config::table('role_has_permissions'));
    }
};
