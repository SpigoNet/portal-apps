# Assistente de Planejamento Alimentar Residencial

## 1. Visão geral

O sistema tem como objetivo auxiliar no planejamento alimentar de uma residência, centralizando:

- controle de estoque de alimentos;
- controle de validade dos produtos;
- planejamento das refeições da semana;
- sugestão automática de receitas;
- consideração das pessoas que irão consumir cada refeição;
- consideração de restrições e preferências alimentares;
- controle das comidas favoritas da família;
- geração automática da lista de compras;
- aproveitamento prioritário dos alimentos já disponíveis;
- planejamento das refeições de acordo com a rotina da residência.

A ideia central é que o sistema funcione como um **assistente culinário doméstico**.

Em vez de o usuário precisar decidir diariamente:

> "O que vamos comer hoje?"

o sistema deverá conseguir responder:

> "Você tem estes ingredientes em casa, estas pessoas estarão presentes nestas refeições, existem estas restrições e preferências. Portanto, estas são as melhores opções para a semana e estes são os produtos que precisam ser comprados."

---

# 2. Objetivos

## 2.1 Objetivo principal

Reduzir o esforço necessário para decidir, comprar e preparar as refeições da residência, utilizando de forma inteligente os alimentos disponíveis.

## 2.2 Objetivos secundários

O sistema deverá:

1. Reduzir desperdício de alimentos.
2. Reduzir compras desnecessárias.
3. Facilitar o planejamento semanal.
4. Ajudar a manter uma alimentação variada.
5. Considerar restrições alimentares.
6. Priorizar alimentos próximos do vencimento.
7. Considerar preferências individuais.
8. Facilitar a montagem das lancheiras.
9. Gerar automaticamente listas de compras.
10. Permitir ajustes manuais no cardápio.
11. Aprender progressivamente com as escolhas da família.

---

# 3. Conceito principal

O sistema será baseado em cinco grandes módulos:

```text
                    ┌─────────────────────┐
                    │     RESIDÊNCIA      │
                    │ Pessoas / Rotina    │
                    └──────────┬──────────┘
                               │
                               ▼
┌─────────────────┐    ┌─────────────────────┐
│     ESTOQUE     │───▶│     PLANEJADOR      │
│ Dispensa        │    │     DE REFEIÇÕES    │
│ Geladeira       │    └──────────┬──────────┘
│ Freezer         │               │
└─────────────────┘               ▼
                         ┌───────────────────┐
                         │     CARDÁPIO      │
                         │      SEMANAL      │
                         └─────────┬─────────┘
                                   │
                                   ▼
                         ┌───────────────────┐
                         │ LISTA DE COMPRAS  │
                         └───────────────────┘
```

Além disso, existe uma base de conhecimento de receitas:

```text
                       ┌─────────────────┐
                       │    RECEITAS     │
                       └────────┬────────┘
                                │
              ┌─────────────────┼─────────────────┐
              ▼                 ▼                 ▼
        Ingredientes       Restrições        Preferências
```

---

# 4. Usuários da residência

O sistema deve permitir cadastrar as pessoas que fazem parte da rotina alimentar da residência.

Exemplo:

| Pessoa    | Tipo    | Observação                 |
| --------- | ------- | -------------------------- |
| Gustavo   | Adulto  | Come todas as refeições    |
| Esposa    | Adulto  | Evita muita lactose        |
| Filho     | Criança | Leva lancheira para escola |
| Empregada | Adulto  | Almoça segunda-feira       |

Cada pessoa poderá possuir:

- nome;
- idade;
- categoria;
- refeições habituais;
- restrições;
- preferências;
- alimentos favoritos;
- alimentos que não gosta;
- alimentos proibidos;
- observações.

---

# 5. Rotina alimentar

A residência deverá possuir uma configuração de rotina.

Exemplo:

### Segunda-feira

| Refeição      | Pessoas                           |
| ------------- | --------------------------------- |
| Café da manhã | Gustavo, esposa, filho            |
| Almoço        | Gustavo, esposa, filho, empregada |
| Lanche        | Filho                             |
| Jantar        | Gustavo, esposa, filho            |

### Terça-feira

| Refeição      | Pessoas                |
| ------------- | ---------------------- |
| Café da manhã | Gustavo, esposa, filho |
| Almoço        | Gustavo, esposa, filho |
| Lanche        | Filho                  |
| Jantar        | Gustavo, esposa, filho |

E assim sucessivamente.

Isso permite que o sistema calcule corretamente a quantidade necessária de alimentos.

---

# 6. Refeições

O sistema deverá permitir configurar quais tipos de refeição fazem parte do planejamento.

Tipos inicialmente sugeridos:

- Café da manhã
- Lanche da manhã
- Almoço
- Lanche da tarde
- Jantar
- Ceia
- Lancheira escolar

O usuário poderá ativar ou desativar cada tipo.

Exemplo:

```text
☑ Café da manhã
☑ Almoço
☐ Lanche da manhã
☑ Lanche da tarde
☑ Jantar
☐ Ceia
☑ Lancheira
```

---

# 7. Lancheira

A lancheira merece um tratamento específico.

O sistema deverá permitir definir:

- dias da semana;
- quantidade de itens;
- quantidade de crianças;
- restrições;
- alimentos permitidos;
- alimentos proibidos;
- variedade desejada.

Exemplo:

```text
Lancheira do filho

Segunda:
- Fruta
- Sanduíche
- Bebida

Terça:
- Fruta
- Bolo
- Bebida
```

O sistema poderá sugerir automaticamente combinações.

Também poderá evitar repetir o mesmo alimento em dias consecutivos.

---

# 8. Estoque

O estoque é um dos módulos centrais do sistema.

Deverá ser possível cadastrar alimentos em:

- despensa;
- geladeira;
- freezer;
- outros locais.

## 8.1 Produto

Cada item deverá possuir:

- nome;
- categoria;
- quantidade;
- unidade;
- local;
- data de validade;
- data de entrada;
- marca opcional;
- observações;
- quantidade mínima;
- quantidade ideal;
- lote opcional.

Exemplo:

```text
Produto:
Arroz

Quantidade:
2 kg

Local:
Despensa

Validade:
15/08/2027

Quantidade mínima:
2 kg

Quantidade ideal:
5 kg
```

---

# 9. Controle de estoque por quantidade

O sistema deverá compreender diferentes unidades.

Exemplos:

### Peso

- g
- kg

### Volume

- ml
- L

### Quantidade

- unidade
- pacote
- lata
- caixa
- pote
- garrafa
- dúzia

É importante que internamente o sistema possua uma unidade base para facilitar cálculos.

Exemplo:

```text
1 kg = 1000 g
1 L = 1000 ml
1 dúzia = 12 unidades
```

---

# 10. Alimentos consumíveis parcialmente

O sistema deverá permitir situações como:

```text
Queijo
Quantidade: aproximadamente 350 g
```

ou:

```text
Leite
Quantidade: 700 ml
```

Isso é importante porque o estoque doméstico raramente possui precisão absoluta.

Poderá existir uma opção:

```text
Quantidade aproximada
```

---

# 11. Validade

O sistema deverá considerar validade dos produtos.

Classificação:

### Normal

Produto longe do vencimento.

### Atenção

Produto próximo do vencimento.

### Urgente

Produto que deve ser utilizado rapidamente.

### Vencido

Produto cuja validade já passou.

Exemplo:

```text
⚠ Tomate — vence em 2 dias
⚠ Creme de leite — vence em 4 dias
✓ Arroz — vence em 240 dias
```

O planejador deverá dar preferência a ingredientes próximos do vencimento.

---

# 12. Receitas

Uma receita deverá possuir:

- nome;
- descrição;
- ingredientes;
- quantidades;
- unidade de cada ingrediente;
- modo de preparo;
- tempo de preparo;
- dificuldade;
- categorias;
- refeições compatíveis;
- número de porções;
- restrições;
- ingredientes substituíveis;
- ingredientes opcionais;
- frequência de utilização.

Exemplo:

```text
Receita:
Strogonoff de frango

Porções:
4

Ingredientes:

500 g de peito de frango
200 g de creme de leite
100 g de molho de tomate
1 cebola
2 dentes de alho
20 ml de óleo
```

---

# 13. Ingredientes substituíveis

Esse é um recurso importante para o planejamento inteligente.

Exemplo:

```text
Receita:
Panqueca

Ingrediente:
Leite

Substituições:
- Leite sem lactose
- Bebida vegetal
```

Outro exemplo:

```text
Creme de leite

Pode substituir por:
- Creme de leite sem lactose
- Creme vegetal
```

O sistema poderá considerar substituições quando uma pessoa possuir restrição.

---

# 14. Restrições alimentares

As restrições deverão ser associadas às pessoas.

Exemplo:

```text
Esposa

Restrição:
Evitar excesso de lactose
```

É importante diferenciar:

### Alergia

O ingrediente não pode ser utilizado.

### Intolerância

O ingrediente deve ser evitado ou substituído.

### Preferência

O ingrediente pode ser consumido, mas não é desejável.

### Objetivo

Exemplo:

- reduzir lactose;
- reduzir açúcar;
- aumentar proteína;
- comer mais vegetais.

O sistema não deve tratar todos esses casos como "proibido".

---

# 15. Preferências

Cada pessoa poderá possuir preferências.

Exemplo:

```text
Esposa

Gosta muito:
- massas
- saladas
- frango

Gosta:
- peixe
- arroz
- legumes

Neutro:
- carne bovina

Não gosta:
- fígado
- sardinha
```

Cada preferência poderá possuir um peso.

Exemplo:

```text
Muito gosta       +5
Gosta             +3
Neutro             0
Não gosta         -5
Odeia             -10
```

---

# 16. Comidas favoritas da família

Além das preferências individuais, o sistema deverá permitir marcar receitas como favoritas.

Exemplo:

```text
⭐ Lasanha
⭐ Strogonoff
⭐ Frango assado
⭐ Macarrão à bolonhesa
⭐ Panqueca
```

Essas receitas deverão receber maior pontuação durante a geração do cardápio.

Porém, o sistema não deve simplesmente repetir os favoritos indefinidamente.

Deverá existir um mecanismo de variedade.

Exemplo:

```text
Strogonoff:
Última utilização: 3 dias atrás

Pontuação reduzida devido à repetição.
```

---

# 17. Frequência desejada

O usuário poderá configurar:

```text
Receita: Strogonoff

Frequência:
Alta

Receita: Peixe assado

Frequência:
Média

Receita: Sopa

Frequência:
Baixa
```

Também poderá definir categorias:

```text
Comida favorita
Comida saudável
Comida rápida
Comida para final de semana
Comida para dias corridos
```

---

# 18. Planejamento semanal

O usuário deverá conseguir criar um planejamento para determinada semana.

Exemplo:

```text
Semana:
12/10/2026 a 18/10/2026
```

O sistema deverá gerar uma grade:

| Dia | Café | Almoço | Lanche | Jantar |
| --- | ---- | ------ | ------ | ------ |
| Seg | ...  | ...    | ...    | ...    |
| Ter | ...  | ...    | ...    | ...    |
| Qua | ...  | ...    | ...    | ...    |
| Qui | ...  | ...    | ...    | ...    |
| Sex | ...  | ...    | ...    | ...    |
| Sáb | ...  | ...    | ...    | ...    |
| Dom | ...  | ...    | ...    | ...    |

---

# 19. Geração automática do cardápio

O usuário deverá poder clicar:

**"Gerar cardápio da semana"**

O sistema deverá analisar:

1. Estoque disponível.
2. Validade dos produtos.
3. Pessoas presentes.
4. Refeições configuradas.
5. Restrições alimentares.
6. Preferências.
7. Favoritos.
8. Histórico de refeições.
9. Necessidade de variedade.
10. Tempo disponível para preparo.
11. Produtos que precisam ser utilizados.
12. Produtos que precisam ser comprados.

E gerar uma proposta.

---

# 20. Estratégia de pontuação das receitas

O algoritmo pode utilizar um sistema de pontuação.

Exemplo:

```text
Pontuação da receita =
    Aproveitamento do estoque
  + Produtos próximos do vencimento
  + Preferências
  + Favoritos
  + Adequação à refeição
  + Adequação às pessoas
  + Facilidade de preparo
  - Repetição recente
  - Ingredientes faltantes
  - Restrições
```

Uma possível fórmula:

```text
Score =
  estoque * 30
+ validade * 20
+ preferência * 15
+ favorito * 15
+ variedade * 10
+ praticidade * 10
- repetição * 15
- compras * 10
```

Os pesos deverão ser configuráveis futuramente.

---

# 21. Aproveitamento do estoque

Uma das principais prioridades do algoritmo deverá ser utilizar aquilo que já existe na residência.

Exemplo:

Estoque:

```text
2 kg arroz
1 kg feijão
1 kg peito de frango
1 kg batata
6 tomates
1 cebola
```

O sistema poderá gerar:

```text
Segunda:
Frango assado + arroz + feijão + batata

Terça:
Arroz + feijão + frango desfiado

Quarta:
Escondidinho de frango
```

Dessa maneira, o planejamento aproveita ingredientes compartilhados entre várias receitas.

---

# 22. Ingredientes compartilhados

O algoritmo deverá considerar ingredientes que aparecem em várias receitas.

Exemplo:

```text
Segunda:
Frango assado

Terça:
Salada de frango

Quarta:
Torta de frango
```

Em vez de comprar três tipos de carne, o sistema pode identificar:

```text
Frango necessário:
Segunda: 700 g
Terça: 300 g
Quarta: 500 g

Total:
1,5 kg
```

E comparar:

```text
Estoque:
1 kg

Compra:
500 g
```

---

# 23. Lista de compras

Após gerar o cardápio, o sistema deverá gerar automaticamente a lista de compras.

Exemplo:

## Hortifruti

- 1 kg tomate
- 2 kg batata
- 1 unidade alface
- 6 bananas

## Carnes

- 500 g peito de frango
- 1 kg carne moída

## Laticínios

- 2 L leite sem lactose
- 1 pote de iogurte

## Mercearia

- 1 pacote macarrão
- 1 molho de tomate

---

# 24. Aproveitamento do estoque na lista de compras

A lista deverá mostrar claramente:

```text
Frango

Necessário:
1,5 kg

Em estoque:
1 kg

Comprar:
500 g
```

Nunca simplesmente:

```text
Comprar 1,5 kg de frango
```

Isso evita compras duplicadas.

---

# 25. Produtos recorrentes

Além dos ingredientes das receitas, o usuário poderá cadastrar produtos que normalmente precisam ser comprados.

Exemplo:

```text
Produtos recorrentes:

☑ Papel higiênico
☑ Papel toalha
☑ Detergente
☑ Café
☑ Leite
☑ Ovos
☑ Frutas
```

Esses produtos poderão aparecer automaticamente na lista de compras.

---

# 26. Lista de compras manual

O usuário poderá adicionar itens manualmente.

Exemplo:

```text
+ Adicionar item

"Comprar chocolate para o final de semana"
```

Também deverá ser possível:

- marcar como comprado;
- remover;
- alterar quantidade;
- agrupar por categoria;
- mover para a próxima compra.

---

# 27. Compra e atualização do estoque

Um recurso importante será permitir:

```text
Lista de compras
       ↓
Produto comprado
       ↓
Adicionar ao estoque
```

Exemplo:

```text
Compra:

2 kg arroz
1 kg frango
12 ovos
1 L leite
```

Ao finalizar a compra:

```text
[✓] Adicionar produtos comprados ao estoque
```

O sistema atualiza automaticamente o estoque.

---

# 28. Consumo de alimentos

Quando uma receita for marcada como preparada/consumida, o sistema deverá poder descontar os ingredientes do estoque.

Exemplo:

```text
Receita:
Macarrão à bolonhesa

Ingredientes:
500 g macarrão
500 g carne
1 molho de tomate
```

Ao marcar como consumida:

```text
Estoque anterior:
Macarrão: 2 kg
Carne: 1 kg
Molho: 3 unidades

Estoque posterior:
Macarrão: 1,5 kg
Carne: 500 g
Molho: 2 unidades
```

---

# 29. Sobras

O sistema deverá futuramente tratar sobras como um tipo de estoque.

Exemplo:

```text
Sobra de frango assado
Quantidade estimada: 400 g
Validade estimada: 2 dias
```

Essa sobra poderá ser utilizada como ingrediente em:

- sanduíches;
- arroz de forno;
- saladas;
- tortas;
- recheios.

Isso pode ser um dos recursos mais interessantes para reduzir desperdício.

---

# 30. Planejamento por ingredientes

O sistema deverá conseguir pensar além de receitas isoladas.

Exemplo:

```text
Tenho:
1 kg de frango

Quero aproveitar ao máximo.

Planejamento:

Segunda:
Frango assado

Terça:
Sanduíche de frango

Quarta:
Torta de frango
```

Isso representa uma evolução importante do sistema:

**Planejamento orientado ao estoque.**

---

# 31. Planejamento por restrições

O algoritmo deverá validar se uma receita atende todas as pessoas daquela refeição.

Exemplo:

```text
Almoço:
Gustavo
Esposa
Filho
Empregada
```

Se a esposa possui restrição à lactose, uma receita que utiliza creme de leite comum poderá:

### Opção A

Ser descartada.

### Opção B

Ser adaptada.

```text
Creme de leite
↓
Creme de leite sem lactose
```

A segunda opção é preferível quando possível.

---

# 32. Receitas adaptáveis

Cada receita poderá informar se permite adaptações.

Exemplo:

```text
Strogonoff

Ingrediente:
Creme de leite

Alternativas:
- Creme de leite sem lactose
- Creme vegetal
```

O sistema poderá apresentar:

> "Esta receita pode ser preparada normalmente para todos utilizando creme de leite sem lactose."

---

# 33. Refeições coletivas e individuais

Uma refeição poderá ter participantes diferentes.

Exemplo:

```text
Almoço de segunda:
4 pessoas

Jantar de segunda:
3 pessoas

Lancheira:
1 pessoa
```

Isso deve impactar diretamente a quantidade necessária.

---

# 34. Quantidade de pessoas

O sistema deverá permitir definir:

### Padrão

```text
Adultos: 2
Crianças: 1
```

Mas também permitir alterações por dia.

Exemplo:

```text
Segunda-feira:
Almoço = 4 pessoas

Terça-feira:
Almoço = 3 pessoas
```

---

# 35. Eventos especiais

O usuário poderá cadastrar eventos que alterem o planejamento.

Exemplos:

```text
Sábado:
Churrasco

Domingo:
Almoço na casa dos avós

Sexta:
Pizza

Feriado:
Viagem
```

Quando houver evento externo, o sistema deverá considerar que aquela refeição não precisa ser planejada.

---

# 36. Ausências

Também será possível cadastrar períodos de ausência.

Exemplo:

```text
Gustavo:
Viaja de 15/10 até 18/10
```

Durante esse período, o sistema não deverá considerar Gustavo no cálculo das refeições.

---

# 37. Histórico

O sistema deverá armazenar:

- refeições planejadas;
- refeições realmente realizadas;
- receitas utilizadas;
- ingredientes consumidos;
- produtos comprados;
- alterações feitas no cardápio;
- receitas rejeitadas;
- receitas favoritas.

Isso permitirá melhorar as sugestões ao longo do tempo.

---

# 38. Sistema de aprendizado de preferências

O sistema poderá aprender com as ações do usuário.

Exemplo:

O sistema sugere:

```text
Peixe assado
```

Usuário:

```text
"Não quero essa receita."
```

O sistema registra um sinal negativo.

Se isso acontecer repetidamente, a receita deverá aparecer menos.

Da mesma forma:

```text
Usuário escolheu lasanha 5 vezes.
```

O sistema pode aumentar a preferência por lasanha.

---

# 39. Feedback do usuário

Após uma refeição, poderá existir:

```text
Como foi essa receita?

★★★★★
```

E opções:

```text
Gostamos muito
Gostamos
Normal
Não gostamos
Não fazer novamente
```

Esse feedback será usado pelo algoritmo.

---

# 40. Interface principal

A tela inicial deverá funcionar como um painel da situação atual da residência.

Exemplo:

```text
┌─────────────────────────────────────────┐
│       ASSISTENTE CULINÁRIO              │
├─────────────────────────────────────────┤
│                                         │
│ Hoje                                    │
│                                         │
│ Almoço                                  │
│ Frango assado + arroz + salada          │
│                                         │
│ Jantar                                  │
│ Macarrão à bolonhesa                    │
│                                         │
├─────────────────────────────────────────┤
│ ALERTAS                                 │
│                                         │
│ ⚠ 3 produtos próximos do vencimento     │
│ 🛒 8 itens para comprar                 │
│ 🍲 Cardápio da próxima semana pendente  │
└─────────────────────────────────────────┘
```

---

# 41. Dashboard

O dashboard deverá apresentar:

### Hoje

- refeições do dia;
- pessoas que irão comer;
- receitas;
- ingredientes necessários.

### Estoque

- quantidade de produtos;
- produtos próximos do vencimento;
- produtos em falta.

### Planejamento

- semana atual;
- próxima semana;
- refeições planejadas.

### Compras

- lista atual;
- itens pendentes.

---

# 42. Fluxo principal de utilização

O fluxo ideal do sistema será:

```text
1. Cadastrar residência
        ↓
2. Cadastrar pessoas
        ↓
3. Configurar rotina
        ↓
4. Cadastrar restrições
        ↓
5. Cadastrar preferências
        ↓
6. Cadastrar estoque
        ↓
7. Cadastrar/importar receitas
        ↓
8. Solicitar planejamento semanal
        ↓
9. Sistema gera cardápio
        ↓
10. Usuário ajusta
        ↓
11. Sistema calcula compras
        ↓
12. Usuário realiza compras
        ↓
13. Produtos entram no estoque
        ↓
14. Refeições são realizadas
        ↓
15. Estoque é atualizado
        ↓
16. Histórico alimenta futuras sugestões
```

---

# 43. Modelo conceitual de dados

Principais entidades:

```text
Residencia
 ├── Pessoas
 ├── Rotinas
 ├── Estoque
 ├── Receitas
 ├── Planejamentos
 ├── ListaCompras
 └── Preferencias
```

## Entidades

### Residence

```text
id
name
created_at
updated_at
```

### Person

```text
id
residence_id
name
birth_date
type
active
```

### FoodPreference

```text
id
person_id
ingredient_id
preference_type
weight
```

### DietaryRestriction

```text
id
person_id
ingredient_id
restriction_type
severity
```

### Ingredient

```text
id
name
category_id
base_unit
```

### Category

```text
id
name
```

### InventoryItem

```text
id
residence_id
ingredient_id
location
quantity
unit
expiration_date
minimum_quantity
ideal_quantity
```

### Recipe

```text
id
name
description
yield
preparation_time
difficulty
```

### RecipeIngredient

```text
id
recipe_id
ingredient_id
quantity
unit
is_optional
```

### IngredientSubstitution

```text
id
ingredient_id
substitute_ingredient_id
compatibility
```

### Meal

```text
id
name
type
```

### WeeklyPlan

```text
id
residence_id
week_start
status
```

### PlannedMeal

```text
id
weekly_plan_id
date
meal_type
recipe_id
people_count
```

### PlannedMealPerson

```text
planned_meal_id
person_id
```

### ShoppingList

```text
id
residence_id
weekly_plan_id
status
```

### ShoppingItem

```text
id
shopping_list_id
ingredient_id
required_quantity
stock_quantity
purchase_quantity
unit
purchased
```

### MealHistory

```text
id
planned_meal_id
consumed_at
rating
feedback
```

---

# 44. Relacionamentos principais

```text
Residence
    │
    ├── Person
    │      ├── Preference
    │      └── Restriction
    │
    ├── InventoryItem
    │      └── Ingredient
    │
    ├── WeeklyPlan
    │      └── PlannedMeal
    │              ├── Recipe
    │              └── Person
    │
    └── ShoppingList
            └── ShoppingItem
                    └── Ingredient

Recipe
    └── RecipeIngredient
            └── Ingredient
```

---

# 45. Configuração da rotina

A rotina deverá ser modelada separadamente do planejamento.

Exemplo:

```text
RoutineRule

day_of_week = MONDAY
meal = LUNCH
person = EMPREGADA
active = true
```

Assim podemos representar:

```text
Segunda:
Empregada participa do almoço.

Terça:
Não participa.

Quarta:
Não participa.
```

Isso evita cadastrar manualmente as pessoas todas as semanas.

---

# 46. Regras de negócio

## RN01 — Restrição alimentar

Uma receita incompatível com uma restrição obrigatória não poderá ser sugerida.

## RN02 — Substituição

Se existir substituto compatível, a receita poderá ser sugerida utilizando o substituto.

## RN03 — Estoque

Ingredientes existentes deverão reduzir a quantidade necessária para compra.

## RN04 — Validade

Ingredientes próximos do vencimento deverão receber maior prioridade.

## RN05 — Favoritos

Receitas favoritas deverão receber maior pontuação.

## RN06 — Repetição

Receitas utilizadas recentemente deverão perder pontuação.

## RN07 — Variedade

O algoritmo deverá evitar repetir excessivamente a mesma receita ou ingrediente principal.

## RN08 — Pessoas

A quantidade necessária deverá considerar as pessoas presentes naquela refeição.

## RN09 — Lancheira

A lancheira deverá ser considerada como uma refeição independente.

## RN10 — Compras

A lista deverá representar somente o déficit entre necessidade e estoque.

## RN11 — Estoque negativo

O sistema não deverá permitir estoque negativo.

## RN12 — Sobras

Sobras poderão ser cadastradas e utilizadas em receitas futuras.

---

# 47. Algoritmo de geração do cardápio

Uma implementação inicial pode seguir este processo:

```text
INPUT

Semana
Pessoas
Rotina
Estoque
Receitas
Preferências
Restrições
Histórico
Eventos
```

↓

### Etapa 1 — Determinar refeições

```text
Segunda almoço
Segunda jantar
Terça almoço
...
```

↓

### Etapa 2 — Determinar participantes

```text
Segunda almoço:
Gustavo
Esposa
Filho
Empregada
```

↓

### Etapa 3 — Filtrar receitas incompatíveis

Eliminar:

- receitas proibidas;
- receitas incompatíveis com alergias;
- receitas sem possibilidade de adaptação.

↓

### Etapa 4 — Calcular pontuação

Para cada receita:

```text
+ aproveitamento de estoque
+ proximidade do vencimento
+ preferência
+ favorito
+ adequação ao horário
+ praticidade
+ variedade

- repetição
- quantidade de compras
- incompatibilidade parcial
```

↓

### Etapa 5 — Selecionar receitas

Escolher a combinação que maximize a pontuação da semana como um todo.

↓

### Etapa 6 — Calcular ingredientes

Somar todos os ingredientes necessários.

↓

### Etapa 7 — Deduzir estoque

```text
necessário - disponível = comprar
```

↓

### Etapa 8 — Apresentar proposta

O usuário poderá:

```text
ACEITAR
```

ou

```text
EDITAR
```

---

# 48. Importante: otimização semanal

O sistema não deve escolher cada refeição isoladamente.

Esse é um ponto importante da arquitetura.

Imagine:

```text
Segunda:
Lasanha

Terça:
Macarrão

Quarta:
Pizza
```

Individualmente todas são boas opções.

Porém, juntas podem exigir:

```text
Queijo
Queijo
Queijo
```

e gerar desperdício.

O algoritmo deverá tentar otimizar o conjunto da semana.

Exemplo:

```text
Segunda:
Frango assado

Terça:
Arroz de frango

Quarta:
Torta de frango
```

Assim um ingrediente comprado pode ser aproveitado em várias refeições.

---

# 49. Explicação das sugestões

O sistema não deve apenas dizer qual receita escolheu.

Ele deverá explicar.

Exemplo:

> **Frango assado**
>
> Escolhido porque:
>
> - você já possui 1,2 kg de frango;
> - o frango vence em 3 dias;
> - é uma comida favorita da família;
> - pode ser utilizado posteriormente em outras refeições;
> - atende às restrições alimentares cadastradas.

Isso aumenta muito a confiança do usuário no sistema.

---

# 50. Alteração manual do cardápio

O usuário deverá poder clicar em uma refeição:

```text
Segunda — Jantar

Macarrão à bolonhesa

[Trocar receita]
```

E receber:

```text
Sugestões alternativas:

1. Frango grelhado
2. Lasanha
3. Omelete
4. Arroz de forno
```

As alternativas devem considerar o contexto daquela semana.

---

# 51. Regeneração parcial

Também deverá ser possível:

```text
"Não gostei do jantar de quarta."
```

E regenerar apenas aquela refeição.

Isso é melhor do que gerar o cardápio inteiro novamente.

---

# 52. Regeneração inteligente

Quando uma refeição for alterada, o sistema deverá recalcular:

- estoque;
- compras;
- utilização dos ingredientes;
- possíveis sobras;
- impacto nas outras refeições.

Exemplo:

```text
Trocar:

Frango assado

por:

Lasanha
```

pode alterar:

```text
+ queijo necessário
+ massa necessária
- frango utilizado
```

Consequentemente a lista de compras deverá ser recalculada.

---

# 53. Interface de estoque

Uma interface simples poderia ser:

```text
ESTOQUE

🔎 Buscar alimento

[Despensa] [Geladeira] [Freezer]

--------------------------------

Arroz
2 kg
✓ Disponível

Frango
800 g
⚠ Vence em 3 dias

Leite
1 L
⚠ Estoque baixo

Tomate
5 unidades
✓ Disponível
```

---

# 54. Entrada rápida de estoque

Como cadastrar estoque manualmente pode ser cansativo, o sistema deverá priorizar rapidez.

Exemplo:

```text
+ Adicionar

Arroz
[ 5 ] [kg]

Local:
[Despensa]

Validade:
[__/__/____]
```

Futuramente poderá haver:

- leitura de código de barras;
- OCR de nota fiscal;
- importação de compras;
- comandos por voz.

---

# 55. Entrada por lista de compras

Após uma compra:

```text
Compra concluída

[✓] Arroz 5 kg
[✓] Frango 2 kg
[✓] Ovos 12 un
[✓] Banana 1 kg
```

Botão:

**Adicionar tudo ao estoque**

---

# 56. Notificações

O sistema poderá gerar notificações como:

> "Seu cardápio da próxima semana ainda não foi planejado."

> "O frango vence em 2 dias. Há 3 receitas que podem utilizá-lo."

> "Você precisa comprar leite para o cardápio de amanhã."

> "Amanhã haverá uma pessoa a mais no almoço."

---

# 57. MVP

Para evitar que o projeto fique gigantesco logo no início, recomendo dividir o desenvolvimento.

## MVP — Versão 1

Implementar:

### Cadastro

- residência;
- pessoas;
- ingredientes;
- categorias;
- receitas.

### Estoque

- adicionar;
- editar;
- remover;
- quantidade;
- local;
- validade.

### Preferências

- gosta;
- não gosta;
- favorito;
- restrição.

### Planejamento

- semana;
- refeições;
- participantes;
- seleção de receitas.

### Lista de compras

- cálculo de ingredientes;
- desconto do estoque;
- geração da lista.

---

# 58. Versão 2

Adicionar:

- geração automática de cardápio;
- pontuação das receitas;
- substituição de ingredientes;
- controle de sobras;
- histórico;
- avaliação das receitas;
- favoritos;
- repetição inteligente.

---

# 59. Versão 3

Adicionar:

- aprendizagem de preferências;
- otimização global da semana;
- planejamento orientado a desperdício;
- sugestões nutricionais;
- previsão de consumo;
- produtos recorrentes;
- eventos;
- ausências;
- lancheiras inteligentes.

---

# 60. Versão 4 — Assistente com IA

Uma camada de IA poderá permitir comandos naturais.

Exemplos:

> "O que posso fazer para o jantar hoje?"

> "Tenho frango, batata e tomate. O que dá para fazer?"

> "Monte o cardápio da semana tentando gastar o mínimo possível."

> "Minha esposa quer evitar lactose essa semana."

> "Meu filho não gostou da banana na lancheira. Não coloque mais."

> "Quero aproveitar aquele frango que está para vencer."

> "Vou receber meus pais domingo. Seremos seis pessoas."

A IA não deverá substituir as regras do sistema.

Ela deverá atuar como uma camada de interação sobre dados estruturados.

---

# 61. Arquitetura recomendada

Uma arquitetura inicial poderia ser:

```text
                  FRONTEND
                     │
                     ▼
              REST / GraphQL API
                     │
          ┌──────────┴──────────┐
          │                     │
          ▼                     ▼
     REGRAS DE NEGÓCIO      IA / LLM
          │                     │
          └──────────┬──────────┘
                     ▼
                 DATABASE
```

A IA não deveria ser responsável diretamente por:

- calcular estoque;
- controlar quantidade;
- calcular compras;
- validar restrições;
- alterar banco de dados livremente.

Essas funções devem ficar em regras determinísticas.

A IA pode interpretar comandos e explicar resultados.

---

# 62. Separação entre IA e regras

Exemplo:

Usuário:

> "Minha esposa não pode consumir lactose."

A IA interpreta:

```text
person = esposa
restriction = lactose
severity = avoid
```

Mas quem aplica a regra é o sistema.

Da mesma forma:

Usuário:

> "Tenho 500g de frango."

A IA pode transformar isso em:

```text
ingredient = frango
quantity = 500
unit = g
```

Mas o estoque é alterado pelo serviço de estoque.

Isso evita que a IA se torne uma fonte de inconsistência.

---

# 63. Possível arquitetura de serviços

```text
User Service
    │
Residence Service
    │
    ├── Person Service
    ├── Preference Service
    ├── Restriction Service
    │
    ├── Inventory Service
    │
    ├── Recipe Service
    │
    ├── Meal Planning Service
    │
    ├── Shopping Service
    │
    └── Recommendation Engine
```

Para um projeto pessoal, entretanto, não há necessidade de começar com microsserviços.

Um **monólito modular** provavelmente será uma escolha muito melhor inicialmente.

---

# 64. Monólito modular recomendado

Estrutura conceitual:

```text
src/
 ├── residence/
 ├── people/
 ├── inventory/
 ├── ingredients/
 ├── recipes/
 ├── preferences/
 ├── restrictions/
 ├── meal-planning/
 ├── shopping/
 ├── history/
 └── recommendation/
```

Isso mantém o projeto simples sem sacrificar uma futura separação dos módulos.

---

# 65. Princípio mais importante do sistema

O sistema deve tentar otimizar simultaneamente:

```text
             ┌───────────────┐
             │   QUALIDADE   │
             └───────┬───────┘
                     │
       ┌─────────────┼─────────────┐
       ▼             ▼             ▼
   Preferências   Estoque       Variedade
       │             │             │
       └─────────────┼─────────────┘
                     ▼
              CARDÁPIO IDEAL
                     │
                     ▼
              MENOR DESPERDÍCIO
                     │
                     ▼
              MENOR COMPRA
```

O objetivo não é simplesmente encontrar receitas.

É encontrar o **melhor conjunto de refeições para aquela residência naquela semana**.

---

# 66. Exemplo completo de funcionamento

Suponha:

### Pessoas

```text
Gustavo
Esposa
Filho
Empregada às segundas-feiras
```

### Restrições

```text
Esposa:
Evitar muita lactose
```

### Lancheira

```text
Segunda a sexta:
Filho
```

### Favoritos

```text
Lasanha
Strogonoff
Frango assado
Macarrão
```

### Estoque

```text
2 kg arroz
1 kg feijão
1,2 kg frango
500 g carne moída
1 kg batata
6 tomates
1 pacote macarrão
1 creme de leite sem lactose
```

O usuário solicita:

> "Planejar minha próxima semana."

O sistema identifica:

```text
35 refeições individuais
```

ou a quantidade correspondente à rotina cadastrada.

Depois avalia as receitas.

Resultado:

```text
SEGUNDA

Almoço:
Frango assado
Arroz
Feijão
Salada

Motivo:
Frango próximo do vencimento.

Jantar:
Macarrão à bolonhesa

Motivo:
Utiliza carne moída disponível.
```

```text
TERÇA

Almoço:
Strogonoff de frango
Arroz
Batata palha

Adaptação:
Creme de leite sem lactose.
```

E assim por diante.

Ao final:

```text
LISTA DE COMPRAS

Hortifruti
- 1 kg tomate
- 1 kg banana
- 1 alface

Carnes
- 1 kg carne bovina

Laticínios
- 2 L leite sem lactose

Mercearia
- 1 pacote batata palha
```

O sistema ainda poderá explicar:

> "Você economizou uma compra de 1 kg de frango porque já possui quantidade suficiente no estoque."

---

# 67. Métricas interessantes

Futuramente o sistema poderá mostrar:

### Desperdício

```text
Alimentos vencidos este mês:
R$ 32,50
```

### Aproveitamento

```text
Ingredientes utilizados antes do vencimento:
87%
```

### Economia

```text
Compras evitadas utilizando estoque:
R$ 180,00
```

### Preferências

```text
Receitas favoritas mais utilizadas:
1. Strogonoff
2. Lasanha
3. Frango assado
```

### Variedade

```text
Ingredientes principais consumidos:

Frango: 5 refeições
Carne: 3 refeições
Peixe: 2 refeições
Vegetariano: 4 refeições
```

---

# 68. Evolução futura: planejamento nutricional

Em uma versão posterior, cada receita poderá possuir informações nutricionais:

```text
Calorias
Proteínas
Carboidratos
Gorduras
Fibras
Sódio
```

Isso permitiria estabelecer objetivos como:

```text
Quero mais proteínas.
Quero reduzir açúcar.
Quero mais vegetais.
```

Porém, essa funcionalidade deve ser considerada separadamente das restrições médicas. O sistema não deverá assumir o papel de nutricionista.

---

# 69. Evolução futura: custo das refeições

Se o usuário cadastrar preços dos produtos:

```text
Frango:
R$ 12,90/kg

Carne:
R$ 38,90/kg

Arroz:
R$ 6,50/kg
```

o sistema poderá calcular:

```text
Custo estimado da semana:
R$ 287,40
```

E futuramente sugerir:

> "Uma alternativa ao cardápio atual reduziria o custo estimado em R$ 42,00."

---

# 70. Evolução futura: planejamento financeiro

Com histórico suficiente, o sistema poderá estimar:

```text
Consumo médio mensal
Custo médio mensal
Categorias mais caras
Produtos mais desperdiçados
```

E responder:

> "Os produtos que mais impactaram suas compras no último mês foram carnes, laticínios e frutas."

---

# 71. Visão final do produto

O sistema pode evoluir de:

```text
CONTROLE DE DESPENSA
```

para:

```text
              ASSISTENTE DOMÉSTICO
                       │
       ┌───────────────┼────────────────┐
       │               │                │
       ▼               ▼                ▼
    ESTOQUE        ALIMENTAÇÃO       COMPRAS
       │               │                │
       │               ▼                │
       │           CARDÁPIO             │
       │               │                │
       └───────────────┼────────────────┘
                       ▼
                  OTIMIZAÇÃO
                       │
                       ▼
                 MENOS DESPERDÍCIO
                 MENOS TRABALHO
                 MENOS COMPRAS
                 MAIS VARIEDADE
```

A visão de produto, portanto, não deve ser "um CRUD de receitas + estoque".

O diferencial está no **motor de planejamento**.

O sistema deve conhecer:

> **quem come + quando come + o que gosta + o que não pode comer + o que existe em casa + o que está vencendo + o que já foi consumido + o que precisa ser comprado**

e, a partir disso, tomar uma decisão de planejamento.

---

# 72. Backlog inicial

## Epic 1 — Configuração da residência

- [ ] Criar residência
- [ ] Cadastrar pessoas
- [ ] Definir tipos de pessoa
- [ ] Configurar rotina semanal
- [ ] Configurar refeições

## Epic 2 — Preferências

- [ ] Cadastrar preferências
- [ ] Cadastrar alimentos rejeitados
- [ ] Cadastrar favoritos
- [ ] Cadastrar restrições
- [ ] Definir intensidade da preferência

## Epic 3 — Estoque

- [ ] Cadastrar ingrediente
- [ ] Cadastrar estoque
- [ ] Editar quantidade
- [ ] Remover produto
- [ ] Definir local
- [ ] Definir validade
- [ ] Alertar estoque baixo
- [ ] Alertar validade

## Epic 4 — Receitas

- [ ] Cadastrar receita
- [ ] Cadastrar ingredientes
- [ ] Definir porções
- [ ] Definir substituições
- [ ] Definir categorias
- [ ] Marcar favorito

## Epic 5 — Planejamento

- [ ] Criar semana
- [ ] Adicionar refeições
- [ ] Definir participantes
- [ ] Sugerir receitas
- [ ] Trocar receita
- [ ] Regenerar refeição
- [ ] Regenerar dia
- [ ] Regenerar semana

## Epic 6 — Compras

- [ ] Calcular ingredientes necessários
- [ ] Deduzir estoque
- [ ] Gerar lista
- [ ] Agrupar produtos
- [ ] Marcar como comprado
- [ ] Adicionar compra ao estoque

## Epic 7 — Histórico

- [ ] Registrar refeição
- [ ] Registrar consumo
- [ ] Avaliar receita
- [ ] Registrar feedback
- [ ] Calcular frequência

## Epic 8 — Inteligência

- [ ] Pontuação de receitas
- [ ] Aproveitamento de estoque
- [ ] Controle de repetição
- [ ] Aproveitamento de validade
- [ ] Otimização semanal
- [ ] Sugestão de substituições
- [ ] Aprendizado de preferências

## Epic 9 — IA

- [ ] Comandos em linguagem natural
- [ ] Assistente de planejamento
- [ ] Explicação das sugestões
- [ ] Consulta do estoque
- [ ] Sugestão de receitas
- [ ] Alteração do cardápio via conversa

---

# 73. Requisito fundamental de experiência

O sistema precisa ser extremamente fácil de alimentar.

Esse talvez seja o requisito mais importante de todos.

Se cadastrar o estoque der muito trabalho, depois de duas semanas ninguém mais vai atualizar.

Portanto, o sistema deverá favorecer:

```text
Poucos cliques
+
Entrada rápida
+
Sugestões automáticas
+
Comandos naturais
+
Atualização automática
```

O ideal é que a pessoa possa dizer:

> "Comprei 2 kg de frango, 1 dúzia de ovos e 5 bananas."

e o sistema consiga transformar isso em alterações estruturadas no estoque.

Da mesma forma:

> "Acabou o arroz."

deveria ser suficiente para atualizar:

```text
Arroz:
0 kg
```

---

# 74. Definição resumida do produto

**Nome provisório:** Assistente Culinário Residencial

**Problema resolvido:** dificuldade de administrar alimentos disponíveis, decidir refeições e saber o que comprar.

**Entrada:**

- pessoas;
- rotina;
- preferências;
- restrições;
- estoque;
- receitas;
- eventos;
- histórico.

**Processamento:**

- recomendação;
- otimização;
- planejamento;
- cálculo de consumo;
- cálculo de compras.

**Saída:**

- cardápio semanal;
- receitas;
- lista de compras;
- alertas;
- recomendações;
- informações de estoque.

**Principal diferencial:**

> Planejar a alimentação da residência considerando simultaneamente pessoas, rotina, estoque, validade, preferências, restrições, variedade e compras.

---

# 75. Princípio arquitetural final

O sistema deve ser pensado como um **problema de otimização com restrições**, e não apenas como um sistema de receitas.

As receitas são apenas uma das fontes de dados.

O verdadeiro objeto que estamos tentando encontrar é:

```text
CARDÁPIO SEMANAL ÓTIMO
```

dado:

```text
Pessoas
+
Rotina
+
Restrições
+
Preferências
+
Estoque
+
Validade
+
Histórico
+
Orçamento
+
Disponibilidade
```

Essa decisão de modelagem é importante porque permitirá que o produto cresça bastante sem precisar ser refeito quando você quiser adicionar, por exemplo, custo, nutrição, desperdício, compras recorrentes ou IA.
