# 21. Requisitos Não Funcionais

O sistema deverá:

- manter o domínio Yomi desacoplado de APIs externas específicas;
- permitir substituição ou inclusão de provedores;
- utilizar processamento assíncrono para operações potencialmente lentas;
- manter alta disponibilidade dos dados locais;
- preservar dados locais durante indisponibilidade externa;
- possuir índices adequados para consultas frequentes;
- possuir mecanismos de retry;
- possuir logs suficientes para diagnóstico;
- evitar duplicação de dados;
- manter integridade referencial;
- ser preparado para expansão futura do catálogo;
- permitir futura integração com novos fornecedores sem alteração significativa do domínio.