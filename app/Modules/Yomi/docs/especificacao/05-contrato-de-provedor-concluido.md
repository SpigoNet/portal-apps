# 5. Contrato de Provedor

Criar uma abstração comum para todos os provedores externos.

```php
interface MangaProvider
{
    public function findById(string $id): ?ExternalManga;
    public function search(string $query): array;
    public function getChapters(string $id): array;
}
```

Os provedores deverão devolver DTOs ou objetos normalizados do domínio externo, evitando que estruturas específicas das APIs vazem para o restante da aplicação.

A arquitetura deve permitir futuramente a inclusão de novos provedores sem necessidade de alterações significativas no domínio.

```text
MangaProvider
    |
    +-- JikanProvider
    |
    +-- AniListProvider
    |
    +-- FuturoProvider
```