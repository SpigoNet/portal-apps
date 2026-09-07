# 14. Segurança e Integridade

A implementação deve:

- não confiar em URLs externas para definir caminhos arbitrários no Storage;
- validar arquivos baixados;
- limitar tamanho dos downloads;
- validar MIME type;
- utilizar timeout de conexão e leitura;
- aplicar retry com backoff;
- validar dados recebidos das APIs;
- utilizar foreign keys;
- utilizar unique constraints;
- criar índices adequados;
- impedir duplicidade de progresso por usuário/obra;
- proteger operações administrativas de sincronização;
- evitar que falhas externas comprometam dados locais.