<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dspace_email_templates', function (Blueprint $table) {
            $table->id();
            // Vincula à configuração com exclusão em cascata
            $table->foreignId('xml_configuration_id')->constrained('dspace_xml_configurations')->onDelete('cascade');
            $table->string('name'); // Nome do arquivo (ex: 'register', 'request_item.author')
            $table->string('subject')->nullable(); // Assunto (para exibição na UI)
            $table->longText('content'); // Conteúdo completo da template (Velocity)
            $table->string('description')->nullable(); // Descrição breve
            $table->timestamps();

            // Garante que o nome da template é único por configuração
            $table->unique(['xml_configuration_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dspace_email_templates');
    }
};
