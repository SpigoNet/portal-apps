<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Modules\MundosDeMim\Services\DailyPhotoService;
use Illuminate\Http\Request;

class AdminUserGalleryController extends Controller
{
    /**
     * Lista usuários que possuem gerações diárias.
     */
    public function index()
    {
        $users = User::whereHas('mundosDeMimAiSetting') // Ou podemos usar DailyGeneration para filtrar
            ->withCount(['mundosDeMimAiSetting as generations_count' => function ($query) {
                // Como não temos a relação direta no User ainda, vamos usar uma subquery ou join
            }])
            ->orderBy('name')
            ->paginate(20);

        // Ajuste: Vamos pegar os IDs de usuários que realmente têm gerações
        $userIdsWithGenerations = DailyGeneration::distinct()->pluck('user_id');
        
        $users = User::whereIn('id', $userIdsWithGenerations)
            ->withCount(['mundosDeMimAiSetting']) // Apenas para ter algo, mas vamos contar gerações manualmente se necessário
            ->orderBy('name')
            ->paginate(20);

        // Adicionando contagem de gerações manualmente para cada usuário na coleção
        foreach ($users as $user) {
            $user->generations_count = DailyGeneration::where('user_id', $user->id)->count();
        }

        return view('MundosDeMim::admin.user-gallery.index', compact('users'));
    }

    /**
     * Exibe a galeria de um usuário específico.
     */
    public function show(User $user)
    {
        $generations = DailyGeneration::where('user_id', $user->id)
            ->with('theme')
            ->orderBy('reference_date', 'desc')
            ->paginate(12);

        return view('MundosDeMim::admin.user-gallery.show', compact('user', 'generations'));
    }

    /**
     * Remove uma geração específica.
     */
    public function destroy($id)
    {
        $generation = DailyGeneration::findOrFail($id);
        $generation->delete();

        return back()->with('success', 'Foto removida da galeria com sucesso.');
    }

    /**
     * Reenvia a foto para o usuário.
     */
    public function send($id, DailyPhotoService $service)
    {
        $generation = DailyGeneration::findOrFail($id);
        $userAttr = UserAttribute::where('user_id', $generation->user_id)->first();

        if (!$userAttr) {
            return back()->with('error', 'Atributos do usuário não encontrados para envio.');
        }

        $preference = $userAttr->notification_preference;
        if (!$preference || $preference === 'none') {
            return back()->with('error', 'O usuário não possui preferência de notificação configurada.');
        }

        $result = $service->sendNotification($userAttr, $generation->image_url, $preference);

        if ($result) {
            return back()->with('success', 'Foto enviada com sucesso para o usuário!');
        }

        return back()->with('error', 'Falha ao enviar a foto.');
    }
}
