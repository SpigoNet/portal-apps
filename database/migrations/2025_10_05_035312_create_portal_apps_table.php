<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portal_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('icon')->nullable(); // Ex: 'fa-solid fa-star' do Font Awesome
            $table->string('start_link'); // Ex: '/todo-app'
            $table->json('images')->nullable(); // Para imagens ilustrativas
            $table->enum('visibility', ['public', 'private', 'specific'])->default('private');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_apps');
    }
};
