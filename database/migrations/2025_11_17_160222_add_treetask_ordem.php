<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('treetask_tarefas')) {
            Schema::table('treetask_tarefas', function (Blueprint $table) {
                if (! Schema::hasColumn('treetask_tarefas', 'ordem')) {
                    $table->integer('ordem')->default(0);
                }

                if (! Schema::hasColumn('treetask_tarefas', 'ordem_global')) {
                    $table->integer('ordem_global')->default(0);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('treetask_tarefas')) {
            Schema::table('treetask_tarefas', function (Blueprint $table) {
                $table->dropColumn(['ordem', 'ordem_global']);
            });
        }
    }
};
