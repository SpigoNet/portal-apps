<?php

namespace Tests\Feature\ANT;

use App\Models\User;
use App\Modules\ANT\Http\Controllers\ProfessorController;
use App\Modules\ANT\Services\SemestreService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HistoricoTrabalhosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ant_configuracoes', function (Blueprint $table): void {
            $table->id();
            $table->string('admins')->nullable();
            $table->string('semestre_atual');
        });
        Schema::create('ant_professor_materia', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('materia_id');
            $table->string('semestre');
        });
        Schema::create('ant_materias', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
        });
        Schema::create('ant_trabalhos', function (Blueprint $table): void {
            $table->id();
            $table->integer('materia_id');
            $table->string('nome');
            $table->string('semestre');
        });
        Schema::create('ant_entregas', function (Blueprint $table): void {
            $table->id();
            $table->integer('trabalho_id');
            $table->string('aluno_ra');
        });

        Schema::create('ant_alunos', function (Blueprint $table): void {
            $table->id();
            $table->string('ra');
            $table->string('nome');
        });

        DB::table('ant_configuracoes')->insert(['semestre_atual' => '2026/2']);
        DB::table('ant_materias')->insert(['id' => 1, 'nome' => 'Programação']);
        foreach (['2025/1', '2025/2', '2026/1', '2026/2', '2027/1'] as $index => $semestre) {
            DB::table('ant_trabalhos')->insert(['id' => $index + 1, 'materia_id' => 1, 'nome' => $semestre, 'semestre' => $semestre]);
        }
        foreach (['2025/1', '2025/2', '2026/2', '2027/1'] as $semestre) {
            DB::table('ant_professor_materia')->insert(['user_id' => 10, 'materia_id' => 1, 'semestre' => $semestre]);
        }
    }

    public function test_history_only_lists_previous_semesters_with_matching_professor_membership(): void
    {
        session(['ant_semestre_professor' => '2026/1']);
        $data = (new ProfessorController)->historico($this->request())->getData();

        $this->assertSame(['2025/2', '2025/1'], $data['semestres']->all());
        $this->assertSame([2], $data['trabalhos']->pluck('id')->all());
        $this->assertSame('2026/1', session('ant_semestre_professor'));
        $this->assertSame('2026/2', SemestreService::getCurrent());
    }

    public function test_filter_works_for_professor_without_current_membership(): void
    {
        DB::table('ant_professor_materia')->where('semestre', '2026/2')->delete();
        $data = (new ProfessorController)->historico($this->request(['semestre' => '2025/1']))->getData();

        $this->assertSame([1], $data['trabalhos']->pluck('id')->all());
    }

    public function test_non_professor_cannot_access_history(): void
    {
        $this->expectException(HttpException::class);
        (new ProfessorController)->historico($this->request([], 99));
    }

    public function test_unavailable_semester_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new ProfessorController)->historico($this->request(['semestre' => '2026/1']));
    }

    public function test_professor_with_no_old_work_gets_empty_history(): void
    {
        DB::table('ant_professor_materia')->where('semestre', '<', '2026/2')->delete();
        $data = (new ProfessorController)->historico($this->request())->getData();

        $this->assertTrue($data['semestres']->isEmpty());
        $this->assertTrue($data['trabalhos']->isEmpty());
    }

    public function test_search_filters_can_be_combined_and_match_partial_names_or_ra(): void
    {
        DB::table('ant_trabalhos')->where('id', 2)->update(['nome' => 'Projeto Final']);
        DB::table('ant_alunos')->insert(['ra' => 'RA12345', 'nome' => 'Maria Silva']);
        DB::table('ant_entregas')->insert(['trabalho_id' => 2, 'aluno_ra' => 'RA12345']);

        foreach ([
            ['trabalho' => 'projeto'],
            ['disciplina' => 'program'],
            ['aluno' => 'maria'],
            ['aluno' => '12345'],
            ['trabalho' => 'final', 'disciplina' => 'program', 'aluno' => 'silva'],
        ] as $filtros) {
            $data = (new ProfessorController)->historico($this->request($filtros))->getData();
            $this->assertSame([2], $data['trabalhos']->pluck('id')->all());
        }
        foreach (['trabalho', 'disciplina', 'aluno'] as $filtro) {
            $data = (new ProfessorController)->historico($this->request([$filtro => 'Inexistente']))->getData();
            $this->assertTrue($data['trabalhos']->isEmpty());
            $this->assertSame(['2025/2', '2025/1'], $data['semestres']->all());
        }
    }

    public function test_student_search_does_not_expose_work_outside_professor_membership(): void
    {
        DB::table('ant_alunos')->insert(['ra' => '123', 'nome' => 'Maria']);
        DB::table('ant_entregas')->insert(['trabalho_id' => 3, 'aluno_ra' => '123']);
        $data = (new ProfessorController)->historico($this->request(['aluno' => 'Maria']))->getData();

        $this->assertTrue($data['trabalhos']->isEmpty());
    }

    private function request(array $query = [], int $userId = 10): Request
    {
        $request = Request::create('/ant/professor/historico', 'GET', $query);
        $user = new User;
        $user->id = $userId;
        $request->setUserResolver(fn (): User => $user);

        return $request;
    }
}
