<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mundos_de_mim_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver')->unique();
            $table->text('base_url')->nullable();
            $table->text('sync_url')->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('mundos_de_mim_ai_providers', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('id')
                ->constrained('mundos_de_mim_providers')
                ->nullOnDelete();
        });

        $drivers = DB::table('mundos_de_mim_ai_providers')
            ->select('driver')
            ->whereNotNull('driver')
            ->distinct()
            ->pluck('driver');

        foreach ($drivers as $driver) {
            $driverName = (string) $driver;
            if ($driverName === '') {
                continue;
            }

            $name = match ($driverName) {
                'pollination' => 'Pollination',
                'airforce' => 'AirForce',
                default => ucfirst(str_replace(['-', '_'], ' ', $driverName)),
            };

            $baseUrl = match ($driverName) {
                'pollination' => 'https://gen.pollinations.ai',
                'airforce' => 'https://api.airforce',
                default => null,
            };

            $syncUrl = match ($driverName) {
                'pollination' => 'https://gen.pollinations.ai/image/models',
                'airforce' => 'https://api.airforce/v1/models',
                default => null,
            };

            $providerId = DB::table('mundos_de_mim_providers')->insertGetId([
                'name' => $name,
                'driver' => $driverName,
                'base_url' => $baseUrl,
                'sync_url' => $syncUrl,
                'api_key' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('mundos_de_mim_ai_providers')
                ->where('driver', $driverName)
                ->update(['provider_id' => $providerId]);
        }
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_ai_providers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
        });

        Schema::dropIfExists('mundos_de_mim_providers');
    }
};
