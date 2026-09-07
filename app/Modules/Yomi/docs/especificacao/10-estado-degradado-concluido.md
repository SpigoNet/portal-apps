# 10. Estado Degradado

## Obra local + API indisponível

Retornar dados locais, registrar a falha e permitir nova sincronização posteriormente.

## Obra inexistente + Jikan indisponível

Tentar AniList; em caso de sucesso, normalizar e persistir.

## Ambos indisponíveis

Não criar registro inválido sem dados mínimos, retornar erro controlado e registrar a ocorrência.

## Falha de mídia

Não bloquear o cadastro da obra. Registrar o erro, agendar retry e preservar mídia existente.