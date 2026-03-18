<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->text('visual_profile')->nullable()->after('photo_path');
            $table->text('personality_vibe')->nullable()->after('hair_type');
            $table->text('interests_and_symbols')->nullable()->after('personality_vibe');
            $table->text('style_and_wardrobe')->nullable()->after('interests_and_symbols');
            $table->text('favorite_settings')->nullable()->after('style_and_wardrobe');
            $table->text('identity_details')->nullable()->after('favorite_settings');
            $table->text('avoid_in_generations')->nullable()->after('identity_details');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->dropColumn([
                'visual_profile',
                'personality_vibe',
                'interests_and_symbols',
                'style_and_wardrobe',
                'favorite_settings',
                'identity_details',
                'avoid_in_generations',
            ]);
        });
    }
};
