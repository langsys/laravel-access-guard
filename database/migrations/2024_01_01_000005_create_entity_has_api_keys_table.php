<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\Support\Config;

return new class extends Migration
{
    public function up(): void
    {
        $table = Config::table('entity_has_api_keys');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->string('entity_type');
            $table->string('entity_id');
            $table->string('api_key_id');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->primary(['entity_type', 'entity_id', 'api_key_id'], 'entity_has_api_keys_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Config::table('entity_has_api_keys'));
    }
};
