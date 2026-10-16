<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quebra_cabeca_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::table('quebra_cabeca_imagens', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('user_id')
                ->constrained('quebra_cabeca_grupos')->nullOnDelete();
            $table->string('orientacao', 10)->default('paisagem')->after('caminho');
        });

        // Imagens já enviadas: descobre a orientação real pelo arquivo.
        foreach (DB::table('quebra_cabeca_imagens')->get() as $imagem) {
            $disco = Storage::disk('public');
            if (! $disco->exists($imagem->caminho)) {
                continue;
            }

            $tamanho = @getimagesize($disco->path($imagem->caminho));
            if ($tamanho && $tamanho[1] > $tamanho[0]) {
                DB::table('quebra_cabeca_imagens')->where('id', $imagem->id)->update(['orientacao' => 'retrato']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quebra_cabeca_imagens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grupo_id');
            $table->dropColumn('orientacao');
        });

        Schema::dropIfExists('quebra_cabeca_grupos');
    }
};
