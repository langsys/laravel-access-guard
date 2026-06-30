<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\Support\Config;

return new class extends Migration
{
    public function up(): void
    {
        $table = Config::table('model_has_permissions');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->primary(
                ['permission_id', 'model_type', 'model_id', 'entity_type', 'entity_id'],
                'model_has_permissions_primary',
            );

            $table->foreign('permission_id')
                ->references('id')->on(Config::table('permissions'))
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Config::table('model_has_permissions'));
    }
};
