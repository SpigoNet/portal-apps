# **Documentação de Projeto: Mundos de Mim**

## **1\. Visão Geral do Produto**

O **Mundos de Mim** é um serviço de assinatura de arte digital personalizada. Através de Inteligência Artificial generativa, o sistema cria diariamente uma nova representação do usuário (e seus entes queridos) em cenários fantásticos, históricos ou de lifestyle, entregando a arte diretamente via WhatsApp ou Telegram.

### **Diferenciais Competitivos**

* **Consistência de Identidade:** Uso de fotos de referência para manter o autorreconhecimento.  
* **Precisão Biométrica:** Integração de dados de altura, peso e medidas para corpos mais realistas.  
* **Inclusão Social:** Suporte para fotos em casal ou duplas.  
* **Segurança Etária:** Filtro rigoroso de conteúdo baseado na idade do usuário.

## **2\. Conceitos Comerciais e Planos**

### **Estratégia de Preços (Brasil 2026\)**

| Recurso | Plano Eco (Telegram) | Plano Prime (WhatsApp) |
| :---- | :---- | :---- |
| **Entrega** | Bot Telegram (Grátis) | WhatsApp API (Pago) |
| **Valor Sugerido** | R$ 14,90 / mês | R$ 34,90 / mês |
| **Público** | Tech-savvy, Jovens | Pais, Público Geral, Negócios |

### **Temas Sazonais (Oportunidade de Upsell)**

O sistema ativa automaticamente temas de calendário:

* **Dezembro:** Especial de Natal (Vilarejos nevados, ajudantes do Papai Noel).  
* **Outubro:** Halloween (Estilo Tim Burton, Fantasia Gótica \- respeitando a faixa etária).  
* **Junho:** Festa Junina (Arraial estilizado).  
* **Sazonalidade:** Opção de comprar "Packs" de prompts exclusivos por tempo limitado.

## **3\. Arquitetura de Dados (MySQL)**

A modelagem foi expandida para suportar as novas regras de negócio.

### **Tabela: users**

* id, name, email, password, birth\_date (para cálculo de idade).  
* subscription\_plan, delivery\_method (whatsapp/telegram).

### **Tabela: user\_attributes (Novo)**

* user\_id: FK.  
* height: float (opcional).  
* weight: float (opcional).  
* body\_type: string (ex: 'atletico', 'slim').  
* eye\_color, hair\_type: string.

### **Tabela: related\_people (Novo)**

* id, user\_id: FK.  
* name, relationship (namorado, filho, etc).  
* photo\_path: string (caminho da foto de rosto).  
* is\_active: boolean.

### **Tabela: themes**

* id, name, slug.  
* age\_rating: enum('kids', 'teen', 'adult').  
* is\_seasonal: boolean.  
* starts\_at, ends\_at: date (nullable).

### **Tabela: prompts**

* id, theme\_id: FK.  
* prompt\_text: text.  
* is\_couple\_prompt: boolean (se deve incluir a pessoa relacionada).

## **4\. Implementação Técnica (Laravel)**

### **4.1. Lógica de Filtragem Etária e Sazonal**

Ao buscar o próximo prompt, o sistema aplica os filtros:

1. **Idade:** Se user.age \< 12, ignora temas adult.  
2. **Sazonalidade:** Se now() estiver entre starts\_at e ends\_at, prioriza esses prompts.  
3. **Casal:** Se o prompt for is\_couple\_prompt, verifica se o usuário tem uma related\_person ativa.

### **4.2. O "Prompt Builder" Dinâmico**

O serviço injeta as características físicas para refinar o resultado da IA:

public function buildPrompt($user, $promptBase, $relatedPerson \= null)   
{  
    $attributes \= $user-\>attributes;  
    $physicalDetails \= "";

    if ($attributes) {  
        $physicalDetails \= "Person is {$attributes-\>height}cm tall, {$attributes-\>body\_type} body type. ";  
    }

    $finalPrompt \= "Realistic portrait of a person. " . $physicalDetails . $promptBase;

    if ($relatedPerson) {  
        $finalPrompt .= " AND a second person next to them, " . $relatedPerson-\>relationship . " style.";  
    }

    return $finalPrompt;  
}

## **5\. Fluxo de Sistema (Backend)**

1. **Trigger:** O Laravel Scheduler roda o Job ProcessDailyGenerations às 07:00.  
2. **Seleção:**  
   * O sistema identifica o perfil do usuário.  
   * Sorteia um tema compatível com a idade.  
   * Verifica se há temas sazonais ativos no momento.  
   * Seleciona um prompt do tema que **não conste** na user\_prompt\_history.  
3. **Geração:**  
   * Envia o prompt montado para a API (Gemini/Pollinations).  
   * O sistema aguarda o retorno da URL da imagem.  
4. **Entrega:**  
   * Salva o registro na daily\_generations.  
   * Dispara o driver de notificação (Telegram ou WhatsApp).

## **6\. Segurança e Privacidade**

* **Filtro NSFW:** Ativação obrigatória dos Safety Settings da API do Gemini para evitar gerações inadequadas.  
* **Isolamento de Dados:** Fotos de related\_people seguem o mesmo padrão de criptografia e isolamento do Storage das fotos principais.  
* **Direito ao Esquecimento:** Botão de "Limpar Minha Identidade" que remove fotos, histórico e atributos físicos.

## **7\. Escalabilidade e Performance**

* **Queue Database:** Uso de filas para não travar o servidor durante as gerações simultâneas.  
* **Storage Externo:** Recomendado o uso de Cloudflare R2 ou S3 para as imagens geradas, mantendo o banco de dados MySQL leve.