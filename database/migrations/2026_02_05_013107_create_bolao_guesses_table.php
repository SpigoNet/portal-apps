<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bolao_guesses', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('bolao_meetings')->onDelete('cascade');
            $table->string('name');
            $table->time('guess');
            $table->integer('diff_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bolao_guesses');
    }
};
