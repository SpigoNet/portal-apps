create table spigo594_apps.ant_alunos
(
    id         bigint unsigned auto_increment
        primary key,
    ra         varchar(13)     not null,
    nome       varchar(255)    not null,
    user_id    bigint unsigned null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_alunos_ra_unique
        unique (ra),
    constraint ant_alunos_user_id_unique
        unique (user_id),
    constraint ant_alunos_user_id_foreign
        foreign key (user_id) references spigo594_apps.users (id)
            on delete set null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_configuracoes
(
    id             bigint unsigned auto_increment
        primary key,
    semestre_atual varchar(6)                        not null comment 'Ex: 2025-2',
    admins         text                              null,
    prompt_agente  text                              null comment 'Prompt do sistema/persona da IA',
    ia_driver      varchar(50) default 'pollination' not null comment 'pollination | lm_studio',
    ia_url         varchar(255)                      null comment 'URL para LM Studio ou outra API local',
    ia_key         varchar(255)                      null comment 'Chave de API para Gemini ou OpenAI',
    created_at     timestamp                         null,
    updated_at     timestamp                         null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_links
(
    id         bigint unsigned auto_increment
        primary key,
    grupo      varchar(100)         not null,
    nome       varchar(100)         not null,
    link       text                 null,
    is_video   tinyint(1) default 0 not null,
    created_at timestamp            null,
    updated_at timestamp            null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_materias
(
    id         bigint unsigned auto_increment
        primary key,
    nome       varchar(255) not null,
    nome_curto varchar(100) not null,
    created_at timestamp    null,
    updated_at timestamp    null,
    constraint ant_materias_nome_curto_unique
        unique (nome_curto)
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_aluno_materia
(
    id         bigint unsigned auto_increment
        primary key,
    aluno_ra   varchar(13)     not null,
    materia_id bigint unsigned not null,
    semestre   varchar(6)      not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_aluno_materia_aluno_ra_materia_id_semestre_unique
        unique (aluno_ra, materia_id, semestre),
    constraint ant_aluno_materia_aluno_ra_foreign
        foreign key (aluno_ra) references spigo594_apps.ant_alunos (ra)
            on update cascade on delete cascade,
    constraint ant_aluno_materia_materia_id_foreign
        foreign key (materia_id) references spigo594_apps.ant_materias (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_pesos
(
    id         bigint unsigned auto_increment
        primary key,
    semestre   varchar(6)      not null,
    materia_id bigint unsigned not null,
    grupo      varchar(100)    not null comment 'Ex: P1, Trabalhos',
    valor      double          not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_pesos_materia_id_foreign
        foreign key (materia_id) references spigo594_apps.ant_materias (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_professor_materia
(
    id         bigint unsigned auto_increment
        primary key,
    user_id    bigint unsigned not null,
    materia_id bigint unsigned not null,
    semestre   varchar(6)      not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_professor_materia_materia_id_foreign
        foreign key (materia_id) references spigo594_apps.ant_materias (id)
            on delete cascade,
    constraint ant_professor_materia_user_id_foreign
        foreign key (user_id) references spigo594_apps.users (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_questoes
(
    id               bigint unsigned auto_increment
        primary key,
    enunciado        text                 not null,
    database_name    varchar(100)         null,
    query_correta    text                 null,
    dissertativa     tinyint(1) default 0 not null,
    multipla_escolha tinyint(1) default 0 not null,
    created_at       timestamp            null,
    updated_at       timestamp            null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_alternativas
(
    id         bigint unsigned auto_increment
        primary key,
    questao_id bigint unsigned not null,
    texto      text            not null,
    correta    tinyint(1)      not null,
    explicacao text            null comment 'Justificativa do porquê está certa ou errada',
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_alternativas_questao_id_foreign
        foreign key (questao_id) references spigo594_apps.ant_questoes (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_tipos_trabalho
(
    id         bigint unsigned auto_increment
        primary key,
    descricao  varchar(255) not null,
    arquivos   varchar(255) not null comment 'Extensões permitidas ex: pdf|zip|link',
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_trabalhos
(
    id               bigint unsigned auto_increment
        primary key,
    semestre         varchar(6)      not null,
    nome             varchar(255)    not null,
    descricao        text            not null,
    dicas_correcao   text            null comment 'Instruções para a IA corrigir este trabalho específico',
    materia_id       bigint unsigned not null,
    tipo_trabalho_id bigint unsigned not null,
    prazo            date            not null,
    maximo_alunos    int default 1   not null,
    peso_id          bigint unsigned not null,
    created_at       timestamp       null,
    updated_at       timestamp       null,
    constraint ant_trabalhos_materia_id_foreign
        foreign key (materia_id) references spigo594_apps.ant_materias (id)
            on delete cascade,
    constraint ant_trabalhos_peso_id_foreign
        foreign key (peso_id) references spigo594_apps.ant_pesos (id),
    constraint ant_trabalhos_tipo_trabalho_id_foreign
        foreign key (tipo_trabalho_id) references spigo594_apps.ant_tipos_trabalho (id)
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_entregas
(
    id                   bigint unsigned auto_increment
        primary key,
    trabalho_id          bigint unsigned not null,
    aluno_ra             varchar(13)     not null,
    arquivos             text            null comment 'JSON com caminhos ou link',
    comentario_aluno     text            null,
    data_entrega         datetime        not null,
    nota                 double          null,
    comentario_professor text            null,
    created_at           timestamp       null,
    updated_at           timestamp       null,
    constraint ant_entregas_trabalho_id_aluno_ra_unique
        unique (trabalho_id, aluno_ra),
    constraint ant_entregas_aluno_ra_foreign
        foreign key (aluno_ra) references spigo594_apps.ant_alunos (ra)
            on update cascade on delete cascade,
    constraint ant_entregas_trabalho_id_foreign
        foreign key (trabalho_id) references spigo594_apps.ant_trabalhos (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_provas
(
    id          bigint unsigned auto_increment
        primary key,
    descricao   text                 not null,
    disponivel  tinyint(1) default 0 not null,
    trabalho_id bigint unsigned      null,
    created_at  timestamp            null,
    updated_at  timestamp            null,
    constraint ant_provas_trabalho_id_foreign
        foreign key (trabalho_id) references spigo594_apps.ant_trabalhos (id)
            on delete set null
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_prova_questoes
(
    id         bigint unsigned auto_increment
        primary key,
    prova_id   bigint unsigned not null,
    questao_id bigint unsigned not null,
    ordem      int             not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint ant_prova_questoes_prova_id_questao_id_unique
        unique (prova_id, questao_id),
    constraint ant_prova_questoes_prova_id_foreign
        foreign key (prova_id) references spigo594_apps.ant_provas (id)
            on delete cascade,
    constraint ant_prova_questoes_questao_id_foreign
        foreign key (questao_id) references spigo594_apps.ant_questoes (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table spigo594_apps.ant_prova_respostas
(
    id            bigint unsigned auto_increment
        primary key,
    prova_id      bigint unsigned                     not null,
    aluno_ra      varchar(13)                         not null,
    questao_id    bigint unsigned                     not null,
    resposta      text                                null,
    pre_avaliacao varchar(200)                        null,
    pontuacao     int                                 null,
    quando        timestamp default CURRENT_TIMESTAMP not null,
    created_at    timestamp                           null,
    updated_at    timestamp                           null,
    constraint ant_prova_resp_unique
        unique (prova_id, aluno_ra, questao_id),
    constraint ant_prova_respostas_aluno_ra_foreign
        foreign key (aluno_ra) references spigo594_apps.ant_alunos (ra)
            on update cascade on delete cascade,
    constraint ant_prova_respostas_prova_id_foreign
        foreign key (prova_id) references spigo594_apps.ant_provas (id)
            on delete cascade,
    constraint ant_prova_respostas_questao_id_foreign
        foreign key (questao_id) references spigo594_apps.ant_questoes (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

