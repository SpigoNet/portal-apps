<?php

namespace App\Modules\Yomi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\MidiaTipo;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\StatusLeitura;
use App\Modules\Yomi\Exceptions\YomiSyncException;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\MangaExternalId;
use App\Modules\Yomi\Models\ProgressoUsuario;
use App\Modules\Yomi\Models\UsuarioCapitulo;
use App\Modules\Yomi\Repositories\MangaRepository;
use App\Modules\Yomi\Services\ProgressService;
use App\Modules\Yomi\Services\SyncManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class YomiController extends Controller
{
    public function __construct(
        private readonly ProgressService $progressService,
        private readonly SyncManager $syncManager,
        private readonly MangaRepository $mangaRepository,
    ) {}

    public function index(Request $request): View
    {
        $progressos = $this->userProgress($request)->take(12);
        $active = $progressos->first(fn (ProgressoUsuario $progress) => $progress->status === StatusLeitura::Lendo)
            ?? $progressos->first();

        return view('Yomi::index', [
            'active' => $active,
            'queue' => $progressos->filter(fn (ProgressoUsuario $progress) => $progress->id !== $active?->id)->take(4),
            'newChapters' => $this->recentChapters($request),
            'weeklyTotal' => UsuarioCapitulo::where('user_id', $request->user()->id)->where('read_at', '>=', now()->subDays(7))->count(),
            'streak' => $this->readingStreak($request),
            'cover' => fn (?Manga $manga): ?string => $this->coverUrl($manga),
        ]);
    }

    public function library(Request $request): View
    {
        $status = $request->string('status')->toString();
        $validStatus = collect(StatusLeitura::cases())->first(fn (StatusLeitura $case) => $case->value === $status);
        $progressos = $this->userProgress($request, $validStatus)->values();

        return view('Yomi::library', [
            'progressos' => $progressos,
            'activeStatus' => $validStatus?->value ?? 'todos',
            'counts' => $this->statusCounts($request),
            'cover' => fn (?Manga $manga): ?string => $this->coverUrl($manga),
        ]);
    }

    public function stats(Request $request): View
    {
        $userId = $request->user()->id;
        $readCount = UsuarioCapitulo::where('user_id', $userId)->count();
        $finished = ProgressoUsuario::where('user_id', $userId)->where('status', StatusLeitura::Concluido->value)->count();
        $progressos = $this->userProgress($request);
        $genres = $progressos->flatMap(fn (ProgressoUsuario $progress) => $progress->manga?->generos ?? collect())
            ->groupBy('id')->map(fn (Collection $items) => ['name' => $items->first()->nome, 'value' => $items->count()])
            ->sortByDesc('value')->take(5)->values();

        return view('Yomi::stats', [
            'readCount' => $readCount,
            'finished' => $finished,
            'streak' => $this->readingStreak($request),
            'hours' => round($readCount * 4.5 / 60, 1),
            'genres' => $genres,
            'weekly' => $this->weeklyActivity($request),
            'cover' => fn (?Manga $manga): ?string => $this->coverUrl($manga),
        ]);
    }

    public function show(Request $request, Manga $manga): View
    {
        $progress = $this->progressService->getForUser($request->user()->id, $manga->id);
        $chapters = $manga->capitulos()->orderByDesc('numero')->get();
        $readIds = UsuarioCapitulo::where('user_id', $request->user()->id)
            ->where('manga_id', $manga->id)
            ->pluck('capitulo_id')
            ->flip();

        return view('Yomi::show', [
            'manga' => $manga->load(['generos', 'criadores', 'midias']),
            'progress' => $progress,
            'chapters' => $chapters,
            'readIds' => $readIds,
            'cover' => $this->coverUrl($manga),
        ]);
    }

    public function showExternal(Request $request, string $provider, string $externalId): View|RedirectResponse
    {
        $providerName = ProviderName::tryFrom($provider);

        abort_unless($providerName !== null, 404);

        $existing = $this->mangaRepository->findByExternalId($provider, $externalId);

        if ($existing !== null) {
            return redirect()->route('yomi.mangas.show', $existing);
        }

        try {
            $external = $this->syncManager->findExternal($externalId, $providerName);
        } catch (YomiSyncException) {
            abort(502, 'Não foi possível carregar os detalhes da obra no provedor no momento.');
        }

        abort_if($external === null, 404);

        return view('Yomi::discover-detail', [
            'external' => $external,
            'provider' => $providerName->value,
            'externalId' => $externalId,
        ]);
    }

    public function discover(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $error = null;

        try {
            $results = $query !== ''
                ? $this->syncManager->search($query)
                : $this->syncManager->discover((int) config('yomi.discover.limit', 12));
        } catch (YomiSyncException $exception) {
            $error = $exception->getMessage();
            $results = [];
        }

        return view('Yomi::discover', [
            'query' => $query,
            'results' => $results,
            'existing' => $this->existingLocalIds($results),
            'error' => $error,
        ]);
    }

    public function registerFromDiscover(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::enum(ProviderName::class)],
            'external_id' => ['required', 'string', 'max:64'],
            'status' => ['nullable', Rule::enum(StatusLeitura::class)],
        ]);

        $provider = $data['provider'];
        $externalId = $data['external_id'];
        $status = isset($data['status']) ? StatusLeitura::from($data['status']) : StatusLeitura::PretendoLer;
        $existing = $this->mangaRepository->findByExternalId($provider, $externalId);

        if ($existing !== null) {
            $this->progressService->setStatus($request->user()->id, $existing->id, $status);

            return redirect()->route('yomi.mangas.show', $existing)
                ->with('status', 'Obra já estava na estante.');
        }

        try {
            $result = $this->syncManager->getOrSync(externalId: $externalId, provider: $provider);
        } catch (YomiSyncException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->progressService->setStatus($request->user()->id, $result->manga->id, $status);

        return redirect()->route('yomi.mangas.show', $result->manga)
            ->with('status', 'Obra adicionada à biblioteca.');
    }

    public function markNext(Request $request, Manga $manga): RedirectResponse
    {
        $progress = $this->progressService->getForUser($request->user()->id, $manga->id);
        $next = $manga->capitulos()->when($progress?->ultimo_capitulo_lido !== null, fn ($query) => $query->where('numero', '>', $progress->ultimo_capitulo_lido))->orderBy('numero')->first();

        if ($next !== null) {
            $this->progressService->markChapterRead($request->user()->id, $manga->id, $next->id);
        }

        return back()->with('status', $next ? 'Capítulo marcado como lido.' : 'Não há capítulos novos para marcar.');
    }

    public function removeFromLibrary(Request $request, Manga $manga): RedirectResponse
    {
        $removed = $this->progressService->removeFromLibrary($request->user()->id, $manga->id);

        if (! $removed) {
            return redirect()->route('yomi.mangas.show', $manga)
                ->with('status', 'Esta obra não estava na sua estante.');
        }

        if (ProgressoUsuario::where('manga_id', $manga->id)->doesntExist()) {
            $manga->delete();
        }

        return redirect()->route('yomi.library')
            ->with('status', 'Obra removida da sua biblioteca.');
    }

    private function userProgress(Request $request, ?StatusLeitura $status = null): Collection
    {
        return $this->progressService->library($request->user()->id, $status)
            ->load(['manga.generos', 'manga.criadores', 'manga.midias']);
    }

    /**
     * Mapeia identificadores externos de resultados à obra local correspondente.
     *
     * @param  array<int, ExternalManga>  $results
     * @return array<string, int>
     */
    private function existingLocalIds(array $results): array
    {
        $pairs = [];

        foreach ($results as $external) {
            foreach ($external->externalIds as $id) {
                if (isset($id['provider'], $id['external_id'])) {
                    $pairs[] = [$id['provider'], (string) $id['external_id']];
                }
            }
        }

        if ($pairs === []) {
            return [];
        }

        return MangaExternalId::query()
            ->where(function (Builder $query) use ($pairs) {
                foreach ($pairs as [$provider, $externalId]) {
                    $query->orWhere(fn (Builder $or) => $or
                        ->where('provider', $provider)
                        ->where('external_id', $externalId));
                }
            })
            ->get(['manga_id', 'provider', 'external_id'])
            ->mapWithKeys(fn (MangaExternalId $row): array => [$row->provider.':'.$row->external_id => $row->manga_id])
            ->all();
    }

    private function recentChapters(Request $request): Collection
    {
        return UsuarioCapitulo::query()->where('user_id', $request->user()->id)->with(['capitulo.manga'])->latest('read_at')->take(5)->get();
    }

    private function statusCounts(Request $request): array
    {
        $query = ProgressoUsuario::where('user_id', $request->user()->id);

        return collect(['todos' => $query->count()])->merge(collect(StatusLeitura::cases())->mapWithKeys(fn (StatusLeitura $status) => [$status->value => (clone $query)->where('status', $status->value)->count()]))->all();
    }

    private function readingStreak(Request $request): int
    {
        return UsuarioCapitulo::where('user_id', $request->user()->id)->where('read_at', '>=', now()->subDays(30))->distinct('read_at')->count('read_at') ?: 0;
    }

    private function weeklyActivity(Request $request): array
    {
        return collect(range(6, 0))->map(fn (int $days) => UsuarioCapitulo::where('user_id', $request->user()->id)->whereDate('read_at', now()->subDays($days))->count())->all();
    }

    private function coverUrl(?Manga $manga): ?string
    {
        return $manga?->midias?->firstWhere('type', MidiaTipo::Capa->value)?->displayUrl();
    }
}
