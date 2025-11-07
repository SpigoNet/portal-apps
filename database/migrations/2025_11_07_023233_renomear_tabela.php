<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Renomeia a tabela submission_processes para dspace_submission_processes
        if (Schema::hasTable('submission_processes')) {
            Schema::rename('submission_processes', 'dspace_submission_processes');
        }

        // Renomeia a tabela submission_steps para dspace_submission_steps
        if (Schema::hasTable('submission_steps')) {
            Schema::rename('submission_steps', 'dspace_submission_steps');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte o nome da tabela dspace_submission_steps para submission_steps
        if (Schema::hasTable('dspace_submission_steps')) {
            Schema::rename('dspace_submission_steps', 'submission_steps');
        }

        // Reverte o nome da tabela dspace_submission_processes para submission_processes
        if (Schema::hasTable('dspace_submission_processes')) {
            Schema::rename('dspace_submission_processes', 'submission_processes');
        }
    }
};
