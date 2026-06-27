# Calculator Investment

**Calculadora de investimentos em renda fixa brasileira (CDB, LCI, LCA)**

Aplicação em PHP (CLI + API REST) que calcula o retorno de investimentos em CDB, LCI e LCA, utilizando taxas oficiais do **Banco Central do Brasil** (CDI/Selic) via API SGS (Sistema Gerenciador de Séries Temporais). Realiza cálculos de juros compostos diários em dias úteis, aplica tributação regressiva de IR e IOF conforme a legislação brasileira, e gera relatórios detalhados de simulação.

---

## Índice

- [Configuração do Banco de Dados (MySQL)](#configuração-do-banco-de-dados-mysql)
- [Como Usar](#como-usar)
  - [CLI (modo interativo)](#cli-modo-interativo)
  - [API REST (modo HTTP)](#api-rest-modo-http)
- [Exemplos](#exemplos)
- [Fluxo de Dados](#fluxo-de-dados)
  - [CLI](#cli)
  - [API REST](#api-rest)
- [Fórmulas](#fórmulas)
- [Tecnologias](#tecnologias)
- [Fontes de Dados (Banco Central do Brasil)](#fontes-de-dados-banco-central-do-brasil)
- [Conceitos Econômicos](#conceitos-econômicos)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Service Providers (SOLID)](#service-providers-solid)
- [Catálogo de Métodos](#catálogo-de-métodos)

---

## Configuração do Banco de Dados (MySQL)

A API REST utiliza MySQL como persistência adicional para a rota `POST /api/calculate`.

### Pré-requisitos

- MySQL 5.7+ ou 8.0+
- Servidor MySQL rodando em `localhost:3306`

### Criando o banco de dados

```bash
mysql -u root -p < sql/calculator_investment.sql
```

Senha padrão: `root`

### Configuração de ambiente

Copie o arquivo de exemplo e ajuste se necessário:

```bash
cp .env.example .env
```

Conteúdo do `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=calculator_investment
DB_USER=root
DB_PASS=root
DB_CHARSET=utf8mb4
```

### Estrutura das tabelas

O schema (`sql/calculator_investment.sql`) define 4 tabelas:

| Tabela | Descrição |
|--------|-----------|
| `investments` | Dados principais do investimento |
| `investment_estimate` | Projeção diária detalhada |
| `cdi_rates` | Histórico de taxas CDI obtidas da API do BCB |
| `selic_rates` | Histórico de taxas Selic utilizadas |

**`investments`:**

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT AUTO_INCREMENT PK | ID único |
| `investment_type` | ENUM('cdb','lci','lca') | Tipo de investimento |
| `rate_type` | ENUM('pre','pos') | Tipo de taxa |
| `initial_capital` | DECIMAL(16,2) | Capital inicial |
| `cdi_percentage` | DECIMAL(8,2) | Percentual do CDI |
| `months` | INT | Prazo em meses |
| `application_date` | DATE | Data de aplicação |
| `redemption_date` | DATE | Data de resgate |
| `pre_fixed_annual_rate` | DECIMAL(8,4) | Taxa pré-fixada anual |
| `selic_meta` | DECIMAL(8,2) | Taxa Selic Meta |
| `selic_is_over` | TINYINT(1) | Se a Selic informada já é Over |
| `cdi_over` | VARCHAR(20) | Taxa CDI Over anual |
| `is_isento` | TINYINT(1) | GENERATED ALWAYS AS (investment_type != 'cdb') |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**`investment_estimate`:**

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT AUTO_INCREMENT PK | ID único |
| `investment_id` | INT FK → investments.id (UNIQUE, ON DELETE CASCADE) | Investimento pai |
| `amount_bruto` | DECIMAL(16,2) | Montante bruto final |
| `amount_liquid` | DECIMAL(16,2) | Montante líquido final |
| `profit_bruto` | DECIMAL(16,2) | Lucro bruto |
| `profit_liquid` | DECIMAL(16,2) | Lucro líquido |
| `iof_value` | DECIMAL(16,2) | IOF calculado |
| `ir_tax_amount` | DECIMAL(16,2) | IR calculado |
| `monthly_profit_liquid` | DECIMAL(16,2) | Lucro líquido mensal |
| `daily_profit_display` | DECIMAL(16,2) | Lucro líquido diário |
| `is_isento` | TINYINT(1) | Investimento isento (LCI/LCA) |
| `days` | INT | Dias corridos |
| `business_days` | INT | Dias úteis |
| `ir_aliquot` | DECIMAL(5,2) | Alíquota de IR aplicada (ex: 22.5, 20, 17.5, 15) |
| `profit_percentage` | DECIMAL(10,6) | Percentual de lucro sobre o capital inicial |
| `created_at` | TIMESTAMP | Data de criação |

### Nota sobre migração

O schema SQL é aplicado apenas uma vez via `mysql < sql/calculator_investment.sql`. Não há script de migração incremental — em caso de alterações, edite o schema diretamente e reimporte.

Para adicionar as colunas `ir_aliquot` e `profit_percentage` em bancos existentes:

```sql
ALTER TABLE investment_estimate 
ADD COLUMN ir_aliquot DECIMAL(5,2) NOT NULL DEFAULT 0,
ADD COLUMN profit_percentage DECIMAL(10,6) NOT NULL DEFAULT 0;
```

---

## Como Usar

### CLI (modo interativo)

```bash
php index.php
```

O programa detecta automaticamente se está em modo CLI ou HTTP. No terminal, opera em **modo interativo**:

```
1 - CDB
2 - LCI
3 - LCA
Escolha o tipo de investimento (1, 2, 3) [1]:
```

Também é possível passar argumentos via linha de comando:

```bash
php index.php --investment-type=1 --rate-type=2 --application-date=2026-01-01 --months=6 --capital=10000 --cdi=110 --selic-meta=14.25
```

### API REST (modo HTTP)

Execute com o servidor embutido do PHP:

```bash
php -S localhost:8000
```

#### Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/` | **Lista todas** as rotas disponíveis da API |
| `GET` | `/api/selic` | **Consulta** Selic Meta atual (BCB) |
| `GET` | `/api/calculate?page=1&per_page=10` | **Lista** investimentos com paginação |
| `GET` | `/api/calculate?investment_type=cdb&rate_type=pos&capital=10000&cdi=110&application_date=2026-01-01&months=6` | **Calcula estimativa** (sem persistência) via query string |
| `GET` | `/api/calculate?id=1` | **Busca** investimento por ID (atalho para `/api/calculate/1`) |
| `POST` | `/api/calculate` | **Cadastra** novo investimento via body JSON (persiste e retorna ID) |
| `GET` | `/api/calculate/{id}` | **Busca** investimento por ID |
| `PUT` | `/api/calculate/{id}` | **Atualiza/Recalcula** substituindo registro existente |
| `DELETE` | `/api/calculate/{id}` | **Remove** registro por ID |

**Exemplo POST:**

```bash
curl -X POST http://localhost:8000/api/calculate \
  -H "Content-Type: application/json" \
  -d '{
    "investment_type": "cdb",
    "rate_type": "pos",
    "capital": 10000,
    "cdi": 110,
    "application_date": "2026-01-05",
    "months": 6
  }'
```

**Exemplo GET / (lista de rotas):**

```bash
curl http://localhost:8000/
```

**Exemplo GET /api/calculate (com paginação):**

```bash
curl "http://localhost:8000/api/calculate?page=1&per_page=10"
```

Resposta:

```json
{
    "data": [
        {
            "id": 5,
            "input": {
                "investment_type": "cdb",
                "rate_type": "pos",
                "initial_capital": 10000,
                ...
            },
            "result": {
                "amount_bruto": 10728.99,
                "amount_liquid": 10583.19,
                ...
            }
        }
    ],
    "pagination": {
        "total": 25,
        "per_page": 10,
        "current_page": 1,
        "last_page": 3,
        "has_more": true
    }
}
```

**Exemplo GET /api/selic:**

```bash
curl http://localhost:8000/api/selic
```

Resposta:

```json
{
    "selic_meta": 14.25,
    "date": "05/08/2026",
    "source": "BCB/SGS Séries Temporais"
}
```

### Opções CLI / Parâmetros da API

> **Nota sobre nomenclatura:** Na CLI, use `--investment-type` (hífen). Na API, use `investment_type` (underscore).

| Parâmetro (API) | Parâmetro (CLI) | Descrição | Valores |
|------------------|------------------|-----------|---------|
| `investment_type` | `--investment-type` | Tipo de investimento | `cdb`, `lci`, `lca` |
| `rate_type` | `--rate-type` | Tipo de taxa | `pre` (pré-fixado), `pos` (pós-fixado) |
| `application_date` | `--application-date` | Data de aplicação | `YYYY-MM-DD` |
| `months` | `--months` | Prazo em meses | Número inteiro positivo |
| `capital` | `--capital` | Capital inicial | Número decimal positivo |
| `cdi` | `--cdi` | Percentual do CDI (pós) | Ex: `110` = 110% do CDI |
| `pre_rate` | `--pre-rate` | Taxa pré-fixada anual (pré) | Ex: `11.50` = 11,50% ao ano |
| `selic_meta` | `--selic-meta` | Taxa Selic Meta | Ex: `14.25` |
| `cdi_annual` | `--cdi-annual` | Taxa CDI anual manual (opcional) | Ex: `13.65` |

---

## Exemplos

### Exemplo 1: CDB Pós-fixado (110% do CDI, 6 meses, R$ 10.000)

Entrada interativa:
```
1 - CDB
2 - LCI
3 - LCA
Escolha o tipo de investimento (1, 2, 3) [1]: 1

1 - Pre-fixado
2 - Pos-fixado
Escolha o tipo de taxa (1, 2) [2]: 2

Data de aplicacao (YYYY-MM-DD) [2026-01-05]: 2026-01-05
Prazo em meses [6]: 6
Capital inicial (R$) [10000.00]: 10000.00
Percentual do CDI (%) [100]: 110
Taxa Selic Meta (%) [14.25]: 14.25
```

Saída:
```
===================================================================
         Calculo de investimento pos-fixado - CDB, LCI, LCA
===================================================================

Tipo de investimento:  CDB
Tipo de taxa:          Pos-fixado
Capital inicial:       R$ 10.000,00
Rendimento:            110,00% do CDI
Taxa CDI:              13,65% a.a.
Selic Meta:            14,25% - Selic Over: 14,44%
Data aplicacao:        05-01-2026
Data resgate:          05-07-2026
Prazo:                 6 Meses (0 anos 6 meses)
Dias corridos:         181
Dias uteis:            126
Dias uteis por mes:    21

------------------- Valores Brutos ------------------------
Lucro Bruto:                          R$ 728,99
Lucro Bruto Diario (R$):              R$ 5,79
Montante Bruto:                       R$ 10.728,99

---------------------- Impostos ---------------------------
IOF:                                  R$ 0,00
IR (20,0% para 181 dias):            R$ 145,80

------------------- Valores Liquidos ----------------------
Lucro Liquido:                        R$ 583,19
Lucro Liquido Mensal:                 R$ 97,20
Lucro Liquido Diario:                 R$ 4,63
Montante Liquido:                     R$ 10.583,19
===================================================================
```

### Exemplo 2: LCI Pós-fixado (100% do CDI, 12 meses, R$ 50.000)

```
1 - CDB
2 - LCI
3 - LCA
Escolha o tipo de investimento (1, 2, 3) [1]: 2

1 - Pre-fixado
2 - Pos-fixado
Escolha o tipo de taxa (1, 2) [2]: 2

Data de aplicacao (YYYY-MM-DD) [2026-01-05]: 2026-01-05
Prazo em meses [6]: 12
Capital inicial (R$) [10000.00]: 50000.00
Percentual do CDI (%) [100]: 100
Taxa Selic Meta (%) [14.25]: 14.25
```

Saída:
```
===================================================================
         Calculo de investimento pos-fixado - CDB, LCI, LCA
===================================================================

Tipo de investimento:  LCI
Tipo de taxa:          Pos-fixado
Capital inicial:       R$ 50.000,00
Rendimento:            100,00% do CDI
Taxa CDI:              13,65% a.a.
Selic Meta:            14,25% - Selic Over: 14,44%
Data aplicacao:        05-01-2026
Data resgate:          05-01-2027
Prazo:                 12 Meses (1 anos 0 meses)
Dias corridos:         365
Dias uteis:            252
Dias uteis por mes:    21

------------------- Valores Brutos ------------------------
Lucro Bruto:                          R$ 7.142,93
Lucro Bruto Diario (R$):              R$ 28,34
Montante Bruto:                       R$ 57.142,93

---------------------- Impostos ---------------------------
LCI/LCA - ISENTO DE IR/IOF

------------------- Valores Liquidos ----------------------
Lucro Liquido:                        R$ 7.142,93
Lucro Liquido Mensal:                 R$ 595,24
Lucro Liquido Diario:                 R$ 28,34
Montante Liquido:                     R$ 57.142,93
===================================================================
```

### Exemplo 3: CDB Pré-fixado (11,50% a.a., 3 meses, R$ 5.000)

```
1 - CDB
2 - LCI
3 - LCA
Escolha o tipo de investimento (1, 2, 3) [1]: 1

1 - Pre-fixado
2 - Pos-fixado
Escolha o tipo de taxa (1, 2) [2]: 1

Data de aplicacao (YYYY-MM-DD) [2026-01-05]: 2026-03-01
Prazo em meses [6]: 3
Capital inicial (R$) [10000.00]: 5000.00
Taxa prefixada anual (%) [11.50]: 11.50
```

Saída:
```
===================================================================
         Calculo de investimento pre-fixado - CDB, LCI, LCA
===================================================================

Tipo de investimento:  CDB
Tipo de taxa:          Pre-fixado
Capital inicial:       R$ 5.000,00
Rendimento:            11,50% a.a.
Data aplicacao:        01-03-2026
Data resgate:          01-06-2026
Prazo:                 3 Meses (0 anos 3 meses)
Dias corridos:         92
Dias uteis:            65
Dias uteis por mes:    22

------------------- Valores Brutos ------------------------
Lucro Bruto:                          R$ 120,00
Lucro Bruto Diario (R$):              R$ 1,85
Montante Bruto:                       R$ 5.120,00

---------------------- Impostos ---------------------------
IOF:                                  R$ 0,00
IR (22,5% para 92 dias):             R$ 27,00

------------------- Valores Liquidos ----------------------
Lucro Liquido:                        R$ 93,00
Lucro Liquido Mensal:                 R$ 31,00
Lucro Liquido Diario:                 R$ 1,43
Montante Liquido:                     R$ 5.093,00
===================================================================
```

---

## Fluxo de Dados

### CLI

```
Usuario (CLI)
  |
  v
index.php --> bootstrap.php (Container + AppServiceProvider)
  |
  v
CliController --> CliApplication
  |
  +--> CalculateController
  |      +--> InvestmentInputFactory
  |      |      +--> CdiRateService::fetchCdiAnnual()
  |      |      |      +--> BCB SGS API (serie 12 ou 4390)
  |      |      |      +--> Fallback: Selic Meta + spread
  |      |      +--> InvestmentInput (ValueObject)
  |      |
  |      +--> CalculateInvestmentUseCase
  |             +--> InvestmentService
  |                    +--> RateCalculationService
  |                    +--> BusinessDayService
  |                    +--> InvestmentCalculationHelper
  |                    +--> ProfitCalculationService
  |                    +--> TaxCalculationService
  |                    +--> AmountFormatterService
  |                    +--> InMemoryInvestmentRepository
  |
  +--> InvestmentResultController (exibe resultado formatado)
  +--> DailyReportService (tabela dia-a-dia do IOF)
```

### API REST

```
Cliente (curl/Postman/Insomnia)
  |
  v
index.php --> bootstrap.php (Container + AppServiceProvider)
  |
  v
HttpApplication (roteamento)
  |
  +--> GET /api/selic
  |      +--> SelicController::execute()
  |             +--> SelicUseCase::execute()
  |                    +--> SelicService::execute()
  |                           +--> CdiApiClient (API BCB série 432)
  |
   +--> GET /api/calculate (sem params de investimento — paginação)
    |      +--> ListInvestmentsController::execute()
    |             +--> parse page/per_page dos query params
    |             +--> ListInvestmentsUseCase::paginated(page, perPage)
    |             +--> ListInvestmentService::paginated(page, perPage)
    |                    +--> ListInvestmentRepository::paginated() (MySQL - tenta primeiro)
    |                    |      +--> COUNT(*) + SELECT ... LIMIT/OFFSET
    |                    +--> JsonFileInvestmentRepository::paginated() (fallback se MySQL falhar)
    |             +--> Retorna { data: [...], pagination: { total, per_page, current_page, last_page, has_more } }
   |
   +--> GET /api/calculate/{id}
   |      +--> ShowInvestmentController::execute()
   |             +--> ShowInvestmentUseCase
   |             +--> ShowInvestmentService (fallback: MySQL → JSON)
   |                    +--> ShowInvestmentRepository (MySQL - tenta primeiro)
   |                    +--> JsonFileInvestmentRepository (fallback se MySQL falhar)
   |
   +--> GET /api/calculate (com params de investimento — estimativa sem persistência)
   |      +--> CalculateInvestmentEstimateController::execute()
   |             +--> HttpInputFactory::create()  (converte JSON em InvestmentInput)
   |             |      +--> CdiRateService::fetchCdiAnnual()
   |             +--> CalculateInvestmentUseCase::recalculate() → retorna Investment (sem persistir)
   |
   +--> POST /api/calculate (com params de investimento — persiste)
   |      +--> CreateInvestmentController::execute()
   |             +--> HttpInputFactory::create()  (converte JSON em InvestmentInput)
   |             |      +--> CdiRateService::fetchCdiAnnual()
   |             +--> CalculateInvestmentUseCase::execute() → retorna Investment
   |             |      +--> InvestmentService::handle()
   |             |             +--> CreateInvestmentRepository::insertInvestment()  → INSERT na tabela `investments` (gera ID no MySQL)
   |             |             +--> CreateInvestmentRepository::insertEstimate()    → INSERT na tabela `investment_estimate`
   |             |             +--> JsonFileInvestmentRepository::save()            → salva no JSON usando o mesmo ID do MySQL
   |             +--> CalculateInvestmentUseCase::getLastId() → retorna o ID gerado (MySQL)
  |
  +--> PUT /api/calculate/{id}
   |      +--> UpdateInvestmentController::execute()
   |             +--> ShowInvestmentUseCase::execute() (busca existente)
   |             |      +--> ShowInvestmentService (fallback: MySQL → JSON)
   |             +--> HttpInputFactory::inputToParams() + create() (merge de dados)
   |             +--> CalculateInvestmentUseCase::recalculateUpdate() → recalcula + persiste
  |
  +--> DELETE /api/calculate/{id}
         +--> DeleteInvestmentController::execute()
                +--> DeleteInvestmentUseCase
                +--> DeleteInvestmentService
                       +--> JsonFileInvestmentRepository (exclui do JSON)
                       +--> DeleteInvestmentRepository (exclui do MySQL independentemente)
```

---

## Fórmulas

### 1. Obtenção da Taxa CDI (API BCB)

**Série 12 (CDI diário) — primária:**

Seja $$d$$ a taxa diária em percentual obtida da série 12 do BCB:

$$
r_{anual} = \left( \left(1 + \frac{d}{100}\right)^{252} - 1 \right) \times 100
$$

Onde 252 é o número de dias úteis padrão do mercado brasileiro.

**Série 4390 (CDI mensal) — fallback:**

Seja $$m$$ o valor obtido da série 4390:

- Se $$m > 5$$: $$r_{anual} = m$$ (já é taxa anual)
- Se $$m \leq 5$$: $$r_{anual} = \left( \left(1 + \frac{m}{100}\right)^{12} - 1 \right) \times 100$$

### 2. Fallback Offline

Quando a API do BCB está indisponível:

$$
r_{CDI} = r_{SelicMeta} + spread
$$

Onde $$spread \approx -0,10$$ a $$+0,19$$ pontos percentuais.

### 3. Conversão Selic Meta → Selic Over

$$
r_{Over} = r_{SelicMeta} + 0{,}19355938
$$

### 4. Taxa Efetiva Anual (Pós-fixado)

$$
r_{efetiva} = \frac{r_{CDI}}{100} \times p_{CDI}
$$

Onde $$p_{CDI}$$ é o percentual do CDI contratado (ex: 110 para 110%).

### 5. Taxa Diária (Taxa Over)

$$
r_{diaria} = \left( \left(1 + \frac{r_{anual}}{100}\right)^{\frac{1}{252}} - 1 \right) \times 100
$$

### 6. Capitalização Diária em Dias Úteis

Para cada dia útil $$i$$ de $$1$$ a $$n$$ (onde $$n$$ = total de dias úteis):

$$
M_i = M_{i-1} + \left( M_{i-1} \times \frac{r_{diaria}}{100} \right)
$$

Com $$M_0 = C$$ (capital inicial) e $$M_n$$ arredondado para 2 casas decimais a cada passo.

### 7. Taxa Proporcional Simples (Meses)

$$
r_{periodo} = \frac{r_{anual} \times meses}{12}
$$

### 8. Taxa Composta (Meses)

$$
r_{composta} = \left( \left(1 + \frac{r_{anual}}{100}\right)^{\frac{meses}{12}} - 1 \right) \times 100
$$

### 9. Lucro Bruto

$$
L_{bruto} = M_{bruto} - C
$$

### 10. Lucro Líquido (após IR e IOF)

$$
L_{liquido} = M_{liquido} - C
$$

### 11. IOF (Imposto sobre Operações Financeiras)

Se $$dias \leq 30$$:

$$
IOF = L_{bruto} \times a_{dias}
$$

Onde $$a_{dias}$$ é a alíquota regressiva:

| Dia | Alíquota | Dia | Alíquota |
|-----|----------|-----|----------|
| 1 | 96% | 2 | 93% |
| 3 | 90% | 4 | 86% |
| 5 | 83% | 6 | 80% |
| 7 | 76% | 8 | 73% |
| 9 | 70% | 10 | 66% |
| 11 | 63% | 12 | 60% |
| 13 | 56% | 14 | 53% |
| 15 | 50% | 16 | 46% |
| 17 | 43% | 18 | 40% |
| 19 | 36% | 20 | 33% |
| 21 | 30% | 22 | 26% |
| 23 | 23% | 24 | 20% |
| 25 | 16% | 26 | 13% |
| 27 | 10% | 28 | 6% |
| 29 | 3% | 30 | 0% |

Se $$dias > 30$$: $$IOF = 0$$

### 12. IR (Imposto de Renda)

Base de cálculo após dedução do IOF:

$$
IR = (L_{bruto} - IOF) \times aliquotaIR
$$

Alíquotas regressivas por prazo:

| Prazo | Alíquota |
|-------|----------|
| Até 180 dias | 22,5% |
| De 181 a 360 dias | 20,0% |
| De 361 a 720 dias | 17,5% |
| Acima de 720 dias | 15,0% |

**Montante líquido final:**

$$
M_{liquido} = C + (L_{bruto} - IOF - IR)
$$

### 13. Investimentos Isentos (LCI/LCA)

Para LCI e LCA: $$IOF = 0$$, $$IR = 0$$.

$$
M_{liquido} = M_{bruto}
$$
$$
L_{liquido} = L_{bruto}
$$

### 14. Lucro Líquido Diário

**Tributado (CDB):**

$$
L_{diario} = \frac{L_{liquido}}{diasUteis}
$$

**Isento (LCI/LCA):**

$$
L_{diario} = \frac{L_{bruto}}{diasUteis}
$$

### 15. Lucro Líquido Mensal

**Tributado (CDB):**

$$
L_{mensal} = \frac{L_{liquido}}{meses}
$$

**Isento (LCI/LCA):**

$$
L_{mensal} = \frac{L_{bruto}}{meses}
$$

### 16. Dias Úteis

Contagem de dias de $$D_{aplicacao}$$ até $$D_{resgate}$$, excluindo:
- Sábados e domingos
- Feriados nacionais brasileiros:
  - Fixos: 01/01, 21/04, 01/05, 07/09, 12/10, 02/11, 15/11, 20/11, 25/12
  - Móveis: Carnaval (-48d Páscoa), Quarta de Cinzas (-47d), Sexta-feira Santa (-2d), Corpus Christi (+60d)

### 17. Cálculo da Páscoa (Algoritmo de Gauss)

Seja $$ano$$ o ano desejado:

$$
\begin{aligned}
a &= ano \bmod 19 \\
b &= \lfloor ano / 100 \rfloor \\
c &= ano \bmod 100 \\
d &= b \bmod 4 \\
e &= (19a + b - \lfloor b/4 \rfloor - \lfloor (b - \lfloor (b+8)/25 \rfloor + 1)/3 \rfloor + 15) \bmod 30 \\
f &= (2(b \bmod 4) + 2 \lfloor c/4 \rfloor - c \bmod 4 - e + 577) \bmod 7 \\
g &= \lfloor (11e + 4f) / 29 \rfloor \\
dia &= e + f - g + 1 \\
mes &= \lceil (dia + 10) / 31 \rceil \\
dia &= dia - 31 \times (mes - 1) + 21
\end{aligned}
$$

---

## Tecnologias

- **Linguagem:** PHP 8.0+ (propriedades `readonly`, named arguments, `match`)
- **Gerenciador de dependências:** Composer (PSR-4)
- **Cliente HTTP:** cURL nativo
- **Matemática:** BC Math (`bcadd`, `bcsub`, `bcmul`, `bcdiv`, `bccomp`) + `pow()` para juros compostos
- **Datas:** `DateTimeImmutable`, `DatePeriod`, `DateInterval`
- **Arquitetura:** Container DI, Service Providers (SOLID), camada de serviços, presenters, repositories
- **Persistência:**
  - Arquivo JSON (`data/investments.json`) via `JsonFileInvestmentRepository` (padrão, usado por CLI e todas as rotas)
  - MySQL via `CreateInvestmentRepository` (INSERT na rota `POST /api/calculate`), `DeleteInvestmentRepository` (DELETE), `ListInvestmentRepository` (SELECT), `ShowInvestmentRepository` (SELECT por ID)
  - Services com fallback: `ListInvestmentService` e `ShowInvestmentService` tentam MySQL primeiro; se falhar, usam JSON
  - IDs sincronizados: MySQL gera o ID via AUTO_INCREMENT, e o mesmo ID é usado como chave no JSON
- **Banco de Dados:** MySQL 5.7+ / 8.0+ com PDO (`App\Core\Database`)
- **Ambiente:** `.env` para configuração — lido via `App\Core\Config` com métodos tipados (`Config::dbHost()`, `Config::bcbCdiDailyUrl()`, etc.)
- **Modos de execução:** CLI interativo + API REST (auto-detectado via `PHP_SAPI`)

---

## Fontes de Dados (Banco Central do Brasil)

### API SGS (Sistema Gerenciador de Séries Temporais)

| Série | Endpoint | Descrição | Uso |
|-------|----------|-----------|-----|
| **12** | `https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados/ultimos/5?formato=json` | CDI diário (% ao dia) | Fonte primária CDI. Validado como $$0 < valor < 1$$ (percentual diário) e anualizado via $$(1 + taxaDiaria/100)^{252} - 1$$ |
| **4390** | `https://api.bcb.gov.br/dados/serie/bcdata.sgs.4390/dados/ultimos/5?formato=json` | CDI mensal/acumulado | Fallback CDI. Se $$valor > 5$$, tratado como taxa anual; senão, mensal → anualizado via $$(1 + valor/100)^{12} - 1$$ |
| **11** | `https://api.bcb.gov.br/dados/serie/bcdata.sgs.11/dados/ultimos/5?formato=json` | Selic Over diária (% ao dia) | Usada por `CdiRateService::fetchSelicAnnual()` como valor padrão de Selic Meta em requisições API. Anualizada via $$(1 + d/100)^{252} - 1$$ |

### Fallback Offline

Quando a API do BCB está indisponível (falha de cURL, resposta vazia, dados inválidos), o sistema calcula o CDI como:

```
CDI = Selic Meta + spread (~ -0,10 a +0,19 pontos percentuais)
```

---

## Conceitos Econômicos

| Indicador | Papel no Projeto |
|-----------|------------------|
| **CDI** (Certificado de Depósito Interbancário) | Taxa benchmark central. Investimentos pós-fixados são calculados como percentual do CDI. Taxa obtida ao vivo da API do BCB ou informada manualmente. |
| **SELIC Meta** | Taxa básica de juros definida pelo COPOM. Usada como fallback quando a API do BCB está indisponível. Combinada com um spread para estimar o CDI Over. |
| **SELIC Over** | Taxa efetiva diária da Selic. O projeto converte Selic Meta para Over adicionando um spread (default ~0,1936 pp). |
| **IPCA** | Não referenciado diretamente no código atual. |
| **IOF** (Imposto sobre Operações Financeiras) | Tabela regressiva: 96% no dia 1 até 0% no dia 30. Aplicado apenas para investimentos não-isentos com prazo ≤ 30 dias. |
| **IR** (Imposto de Renda) | Alíquotas regressivas: 22,5% (0-180d), 20% (181-360d), 17,5% (361-720d), 15% (>720d). LCI e LCA são isentos. |

### Calendário de Feriados Brasileiros

Feriados fixos e móveis considerados no cálculo de dias úteis:

- **Fixos:** 01-01 (Ano Novo), 21-04 (Tiradentes), 01-05 (Dia do Trabalho), 07-09 (Independência), 12-10 (Nossa Senhora Aparecida), 02-11 (Finados), 15-11 (Proclamação da República), 20-11 (Consciência Negra), 25-12 (Natal)
- **Móveis** (relativos à Páscoa): Carnaval (-48d), Quarta-feira de Cinzas (-47d), Sexta-feira Santa (-2d), Corpus Christi (+60d)
- **Cálculo da Páscoa:** Algoritmo de Gauss

---

## Estrutura do Projeto

```
calculatorInvestment/
├── .env                                   # Credenciais do banco de dados
├── .env.example                           # Template do .env
├── bootstrap.php                          # Autoload, Container, AppServiceProvider + Providers
├── index.php                              # Entry point (CLI + HTTP)
├── composer.json
├── composer.lock
├── .htaccess                              # Apache rewrite rules
├── data/
│   └── investments.json                   # Persistência JSON (gerado automaticamente)
├── sql/
│   └── calculator_investment.sql          # Schema MySQL (4 tabelas + indexes)
├── App/
│   ├── Application/
│   │   ├── CliApplication.php             # Orquestrador CLI
│   │   └── HttpApplication.php            # Roteamento HTTP (API REST)
│   ├── Console/
│   │   └── ConsoleInput.php               # Leitura de argumentos CLI e interativo
│   ├── Contracts/                         # Interfaces
│   │   ├── CalculatesRateInterface.php
│   │   ├── CalculatesTaxInterface.php
│   │   ├── CalculatesProfitInterface.php
│   │   ├── CountsBusinessDaysInterface.php
│   │   ├── FormatsAmountInterface.php
│   │   ├── ControllerInterface.php
│   │   ├── InvestmentRepositoryInterface.php
│   │   └── ProviderInterface.php          # Contrato para Service Providers
│   ├── Controllers/
│   │   ├── CliController.php              # Controller CLI
│   │   ├── CalculateController.php        # Logica de calculo (CLI)
│   │   ├── InvestmentResultController.php # Exibicao de resultado (CLI)
│   │   ├── BaseApiController.php          # Controller base (JSON response, buildPayload)
│   │   ├── CreateInvestmentController.php # Cria investimento (JSON + MySQL)
│   │   ├── ListInvestmentsController.php  # Lista todos
│   │   ├── CalculateInvestmentEstimateController.php # Calcula estimativa sem persistir (GET)
│   │   ├── ShowInvestmentController.php   # Busca por ID
│   │   ├── UpdateInvestmentController.php # Atualiza por ID
│   │   ├── SelicController.php            # Consulta Selic Meta atual (GET /api/selic)
│   │   └── DeleteInvestmentController.php # Deleta por ID
│   ├── Core/
│   │   ├── Container.php                  # Container de injecao de dependencia
│   │   ├── Database.php                   # Conexao PDO singleton com MySQL
│   │   ├── AppServiceProvider.php         # Coordenador de providers (5 linhas)
│   │   └── Providers/
│   │       ├── ProviderInterface.php      # Contrato: register(Container)
│   │       ├── CoreServiceProvider.php    # Infra: JSON repo, services base
│   │       ├── CalculationServiceProvider.php # CdiRate, InvestmentService, UseCases
│   │       ├── RepositoryServiceProvider.php  # MySQL repos + CRUD services
│   │       ├── HttpServiceProvider.php    # Controllers HTTP + factories
│   │       └── CliServiceProvider.php     # Controllers CLI + factories
│   ├── Factories/
│   │   ├── BaseFactory.php                # Validacao, feriados, datas
│   │   ├── InvestmentInputFactory.php     # Cria InvestmentInput (CLI)
│   │   └── HttpInputFactory.php           # Cria InvestmentInput via JSON (API)
│   ├── Helpers/
│   │   ├── InvestmentCalculationHelper.php
│   │   └── DefaultInvestmentCalculationHelper.php
│   ├── Presenters/
│   │   ├── AbstractInvestmentPresenter.php
│   │   └── InvestmentPresenter.php
│   ├── Repositories/
│   │   ├── InMemoryInvestmentRepository.php      # Em memoria (para testes)
│   │   ├── JsonFileInvestmentRepository.php      # Persistente em arquivo JSON (padrao)
│   │   ├── CreateInvestmentRepository.php        # Persistencia MySQL (POST /api/calculate)
│   │   ├── DeleteInvestmentRepository.php        # Exclusao MySQL (DELETE /api/calculate/{id})
│   │   ├── ListInvestmentRepository.php          # Leitura MySQL (lista investimentos)
│   │   └── ShowInvestmentRepository.php          # Leitura MySQL (busca por ID)
│   ├── Services/
│   │   ├── ServiceBase.php                # Constantes: escala, precisao, tabela IOF, feriados
│   │   ├── AmountFormatterService.php     # Formatacao de valores monetarios
│   │   ├── BusinessDayService.php         # Contagem de dias uteis (feriados brasileiros)
│   │   ├── CdiApiClient.php              # Cliente HTTP cURL para API do BCB
│   │   ├── CdiRateCalculator.php         # Validacao e anualizacao de taxas CDI
│   │   ├── CdiRateService.php            # Obtencao da taxa CDI do BCB com fallback
│   │   ├── DailyReportService.php        # Relatorio diario de simulacao IOF
│   │   ├── DeleteInvestmentService.php   # Exclusao combinada JSON + MySQL
│   │   ├── InvestmentService.php         # Servico central de calculo do investimento
│   │   ├── ListInvestmentService.php     # Listagem com fallback MySQL → JSON
│   │   ├── ProfitCalculationService.php  # Calculo de lucros
│   │   ├── RateCalculationService.php    # Operacoes matematicas de taxas
│   │   ├── SelicService.php              # Obtencao da Selic Meta do BCB
│   │   ├── ShowInvestmentService.php     # Busca por ID com fallback MySQL → JSON
│   │   └── TaxCalculationService.php     # Calculo de IR e IOF
│   ├── UseCases/
│   │   ├── CalculateInvestmentUseCase.php
│   │   ├── ListInvestmentsUseCase.php
│   │   ├── SelicUseCase.php
│   │   ├── ShowInvestmentUseCase.php
│   │   └── DeleteInvestmentUseCase.php
│   └── ValueObjects/
│       ├── InvestmentInput.php           # Dados de entrada do investimento
│       └── Investment.php                # Resultado do investimento
│
└── vendor/                                # Composer dependencies
```

---

## Service Providers (SOLID)

O registro de dependências é dividido em 5 providers especializados, cada um responsável por uma camada da arquitetura. O `AppServiceProvider` atua como coordenador, delegando para cada provider.

### Princípios aplicados

| Princípio | Aplicação |
|-----------|-----------|
| **S** - Single Responsibility | Cada provider tem 1 responsabilidade: infra, cálculo, persistência, HTTP ou CLI |
| **O** - Open/Closed | Para adicionar novo provider, cria um arquivo novo sem alterar os existentes |
| **L** - Liskov Substitution | `ProviderInterface` — qualquer provider pode ser substituído por outro que implemente o mesmo contrato |
| **I** - Interface Segregation | `ProviderInterface` tem 1 método (`register`) — não força imports desnecessários |
| **D** - Dependency Inversion | Container resolve dependências via contrato, não via implementação concreta |

### Diagrama de dependências

```
AppServiceProvider (coordenador)
  ├── CoreServiceProvider          ← serviços base (sem dependências externas)
  ├── CalculationServiceProvider   ← cálculos + use cases (depende de Core)
  ├── RepositoryServiceProvider    ← MySQL repos + CRUD (depende de Core)
  ├── HttpServiceProvider          ← controllers HTTP (depende de Calculation + Repository)
  └── CliServiceProvider           ← controllers CLI (depende de Calculation)
```

### Como adicionar um novo provider

1. Crie o arquivo em `App/Core/Providers/MeuNovoProvider.php`
2. Implemente `ProviderInterface`
3. Adicione o `register()` com seus bindings
4. Registre no `AppServiceProvider::register()`:

```php
(new MeuNovoProvider())->register($container);
```

### Comparação: antes vs depois

| Aspecto | Antes (monolítico) | Depois (providers) |
|---------|--------------------|--------------------|
| Arquivos | 1 arquivo, 293 linhas | 6 arquivos, ~50 linhas cada |
| Imports | 42 imports em 1 arquivo | ~8-10 imports por arquivo |
| Responsabilidades | 5 misturadas | 1 por provider |
| Adicionar service | Buscar no arquivo grande | Ir no provider correto |
| Testabilidade | Difícil isolar camada | Mockar provider específico |
| Manutenção | Editar o mesmo arquivo sempre | Editar só o provider da camada |

---

## Catálogo de Métodos

### App\Core\Container

Container de injeção de dependência (singleton).

#### `getContainer(): self`
- **Descrição:** Retorna a instância única do Container (singleton).
- **Parâmetros:** Nenhum.
- **Retorno:** `self` — Instância do Container.

#### `getInstancia(string $key): mixed`
- **Descrição:** Resolve uma dependência registrada pelo nome da chave. Retorna instância única se registrada como singleton.
- **Parâmetros:**
  - `$key` (`string`) — Nome da classe/interface a ser resolvida.
- **Retorno:** `mixed` — Instância resolvida.

#### `bind(string $key, callable $resolver, bool $singleton = false): void`
- **Descrição:** Registra um resolvedor (factory) para uma chave.
- **Parâmetros:**
  - `$key` (`string`) — Nome do binding.
  - `$resolver` (`callable`) — Função factory que cria a instância.
  - `$singleton` (`bool`, default `false`) — Se `true`, cacheia a instância após a primeira criação.

---

### App\Core\AppServiceProvider

Coordenador que registra todos os providers no Container. Segue o princípio de Single Responsibility — cada provider é responsável por uma camada da arquitetura.

#### `register(Container $container): void`
- **Descrição:** Delega o registro para 5 providers especializados:
  - `CoreServiceProvider` — Repositório JSON, services base (AmountFormatter, BusinessDay, Rate, Tax, Profit, InvestmentCalculation)
  - `CalculationServiceProvider` — CdiRateService, InvestmentService, UseCases, DailyReport, Selic
  - `RepositoryServiceProvider` — Repositórios MySQL (List, Show, Delete) + CRUD services + UseCases
  - `HttpServiceProvider` — HttpInputFactory, 7 controllers HTTP, HttpApplication
  - `CliServiceProvider` — InvestmentInputFactory, CalculateController, Presenter, CliApplication
- **Parâmetros:**
  - `$container` (`Container`) — Instância do Container de DI.
- **Retorno:** `void`

---

### App\Contracts\ProviderInterface

Contrato que todos os providers devem implementar.

#### `register(Container $container): void`
- **Descrição:** Registra bindings no Container de DI.
- **Parâmetros:**
  - `$container` (`Container`) — Instância do Container.
- **Retorno:** `void`

---

### App\Core\Providers\CoreServiceProvider

Provider de infraestrutura. Registra repositório JSON e services base (sem dependências externas).

**Bindings:** `InvestmentRepositoryInterface` → `JsonFileInvestmentRepository`, `AmountFormatterService`, `BusinessDayService`, `InvestmentCalculation` → `DefaultInvestmentCalculation`, `RateCalculationService`, `TaxCalculationService`, `ProfitCalculationService`

---

### App\Core\Providers\CalculationServiceProvider

Provider de cálculo. Registra services que dependem de infraestrutura (Database) e use cases.

**Bindings:** `CdiRateService`, `InvestmentService`, `CalculateInvestmentUseCase`, `DailyReportService`, `SelicService`, `SelicUseCase`

---

### App\Core\Providers\RepositoryServiceProvider

Provider de persistência MySQL. Registra repositórios MySQL (singleton=false) e services de CRUD com fallback MySQL → JSON.

**Bindings:** `ListInvestmentRepository`, `ShowInvestmentRepository`, `ListInvestmentService`, `ShowInvestmentService`, `DeleteInvestmentService`, `ListInvestmentsUseCase`, `ShowInvestmentUseCase`, `DeleteInvestmentUseCase`

---

### App\Core\Providers\HttpServiceProvider

Provider HTTP. Registra factories, controllers e a aplicação HTTP.

**Bindings:** `HttpInputFactory`, `ListInvestmentsController`, `ShowInvestmentController`, `CreateInvestmentController`, `CalculateInvestmentEstimateController`, `UpdateInvestmentController`, `DeleteInvestmentController`, `SelicController`, `HttpApplication`

---

### App\Core\Providers\CliServiceProvider

Provider CLI. Registra factories, controllers e a aplicação CLI.

**Bindings:** `InvestmentInputFactory`, `InvestmentPresenter`, `InvestmentResultController`, `CalculateController`, `CliApplication`, `CliController`

---

### App\Core\Config

Carregador de variáveis de ambiente do arquivo `.env`. Provê métodos tipados para acesso a cada configuração.

#### `load(?string $envDir = null): void`
- **Descrição:** Carrega variáveis do arquivo `.env` no formato `CHAVE=valor` para `$_ENV`, `putenv()` e cache interno.
- **Parâmetros:**
  - `$envDir` (`?string`, default `null`) — Diretório onde está o `.env`. Padrão: 2 níveis acima do diretório da classe.
- **Retorno:** `void`

#### Métodos de acesso tipado

| Método | Retorno | Chave `.env` |
|--------|---------|--------------|
| `timezone()` | `string` | `APP_TIMEZONE` |
| `dbHost()` | `string` | `DB_HOST` |
| `dbPort()` | `int` | `DB_PORT` |
| `dbName()` | `string` | `DB_NAME` |
| `dbUser()` | `string` | `DB_USER` |
| `dbPass()` | `string` | `DB_PASS` |
| `dbCharset()` | `string` | `DB_CHARSET` |
| `bcbCdiDailyUrl()` | `string` | `BCB_CDI_DAILY_URL` |
| `bcbCdiAnnualUrl()` | `string` | `BCB_CDI_ANNUAL_URL` |
| `bcbSelicDailyUrl()` | `string` | `BCB_SELIC_DAILY_URL` |

- **Nota:** Substitui os antigos métodos genéricos `get()`, `getString()`, `getInt()`, `getFloat()`, `getBool()` por métodos específicos com tipo definido na assinatura.

---

### App\Core\Database

Conexão PDO singleton com MySQL. Utiliza `App\Core\Config` para obter as credenciais via métodos tipados (`Config::dbHost()`, `Config::dbPort()`, etc.).

#### `getConnection(): \PDO`
- **Descrição:** Retorna a instância única da conexão PDO. Cria a conexão na primeira chamada usando `Config::dbHost()`, `Config::dbPort()`, `Config::dbName()`, `Config::dbUser()`, `Config::dbPass()`, `Config::dbCharset()`.
- **Parâmetros:** Nenhum.
- **Retorno:** `\PDO` — Instância da conexão PDO.
- **Exceções:** `\PDOException` se a conexão falhar.

#### `disconnect(): void`
- **Descrição:** Fecha a conexão PDO (atribui `null` à instância singleton).
- **Parâmetros:** Nenhum.
- **Retorno:** `void`

---

### App\Repositories\ListInvestmentRepository

Repositório MySQL para listagem de investimentos. Executa `SELECT ... JOIN` entre `investments` e `investment_estimate`, retornando array de `['id' => int, 'input' => InvestmentInput, 'result' => Investment]`.

#### `__construct(PDO $pdo)`
- **Parâmetros:**
  - `$pdo` (`PDO`) — Conexão PDO com MySQL.

#### `execute(): array`
- **Descrição:** Lista todos os investimentos com JOIN na tabela `investment_estimate`, ordenados por `id DESC`.
- **Retorno:** `array` — Array de itens com `id`, `input` (`InvestmentInput`), `result` (`Investment`).

#### `count(): int`
- **Descrição:** Retorna o total de registros na tabela `investments`.
- **Retorno:** `int` — Quantidade total de investimentos.

#### `paginated(int $page, int $perPage): array`
- **Descrição:** Lista investimentos com paginação usando `LIMIT` e `OFFSET`.
- **Parâmetros:**
  - `$page` (`int`) — Número da página (1-indexed).
  - `$perPage` (`int`) — Quantidade de registros por página.
- **Retorno:** `array` — `['data' => array, 'total' => int]` onde `data` contém os itens da página e `total` o total de registros.

---

### App\Repositories\ShowInvestmentRepository

Repositório MySQL para busca de investimento por ID. Mesma estrutura JOIN que `ListInvestmentRepository`, mas com filtro `WHERE i.id = :id`.

#### `__construct(PDO $pdo)`
- **Parâmetros:**
  - `$pdo` (`PDO`) — Conexão PDO com MySQL.

#### `execute(int|string $id): ?array`
- **Descrição:** Busca investimento por ID. Retorna `null` se não encontrado.
- **Retorno:** `?array` — `['id' => int, 'input' => InvestmentInput, 'result' => Investment]` ou `null`.

---

### App\Repositories\CreateInvestmentRepository

Repositório de persistência MySQL focado nas rotas `POST /api/calculate` e `PUT /api/calculate/{id}`. Não implementa `InvestmentRepositoryInterface` — possui métodos de única responsabilidade, cada um executando um único INSERT ou UPDATE.

#### `insertInvestment(array $input): int`
- **Descrição:** Insere um registro na tabela `investments` com os dados do investimento.
- **Parâmetros:**
  - `$input` (`array`) — Dados: `initial_capital`, `investment_type`, `rate_type`, `cdi_percentage`, `selic_meta`, `pre_fixed_annual_rate`, `application_date`, `redemption_date`, `months`, `selic_is_over`, `cdi_over`.
- **Retorno:** `int` — O ID auto-incremental gerado.
- **Exceções:** `\PDOException` em caso de erro de inserção.

#### `insertEstimate(int $investmentId, array $result): void`
- **Descrição:** Insere um registro na tabela `investment_estimate` com o resultado consolidado do investimento.
- **Parâmetros:**
  - `$investmentId` (`int`) — ID do investimento pai (FK `investments.id`, UNIQUE).
  - `$result` (`array`) — Dados: `amount_bruto`, `amount_liquid`, `profit_bruto`, `profit_liquid`, `iof_value`, `ir_tax_amount`, `monthly_profit_liquid`, `daily_profit_display`, `is_isento`, `days`, `business_days`, `ir_aliquot`, `profit_percentage`.

#### `updateInvestment(int $id, array $input): void`
- **Descrição:** Atualiza um registro existente na tabela `investments`.
- **Parâmetros:**
  - `$id` (`int`) — ID do investimento.
  - `$input` (`array`) — Mesmos campos do `insertInvestment`.

#### `updateEstimate(int $investmentId, array $result): void`
- **Descrição:** Atualiza o registro na tabela `investment_estimate` para um investimento existente.
- **Parâmetros:**
  - `$investmentId` (`int`) — ID do investimento pai.
  - `$result` (`array`) — Mesmos campos do `insertEstimate`: `amount_bruto`, `amount_liquid`, `profit_bruto`, `profit_liquid`, `iof_value`, `ir_tax_amount`, `monthly_profit_liquid`, `daily_profit_display`, `is_isento`, `days`, `business_days`, `ir_aliquot`, `profit_percentage`.

---

### App\Console\ConsoleInput

Utilitário para leitura de argumentos CLI e entrada interativa.

#### `isInteractive(): bool`
- **Descrição:** Verifica se a execução está em modo interativo (STDIN é um TTY).
- **Parâmetros:** Nenhum.
- **Retorno:** `bool`

#### `option(array $argv, string $name, string $default = ''): string`
- **Descrição:** Extrai o valor de uma opção da linha de comando (ex: `--cdi=12.5`).
- **Parâmetros:**
  - `$argv` (`array`) — Array de argumentos da linha de comando.
  - `$name` (`string`) — Nome da opção (ex: `"cdi"`).
  - `$default` (`string`, default `''`) — Valor padrão caso a opção não exista.
- **Retorno:** `string`

#### `prompt(string $message, string $default = ''): string`
- **Descrição:** Exibe uma mensagem e lê a entrada do usuário no terminal.
- **Parâmetros:**
  - `$message` (`string`) — Texto exibido ao usuário.
  - `$default` (`string`, default `''`) — Valor padrão.
- **Retorno:** `string`

#### `askOption(string $message, array $allowed, string $default): string`
- **Descrição:** Exibe mensagem e repete até que o usuário digite uma opção válida.
- **Parâmetros:**
  - `$message` (`string`) — Texto do prompt.
  - `$allowed` (`array`) — Array de valores permitidos.
  - `$default` (`string`) — Valor padrão.
- **Retorno:** `string`

#### `normalizeRateType(string $value): string`
- **Descrição:** Normaliza o tipo de taxa para `"pre"` ou `"pos"`.
- **Parâmetros:**
  - `$value` (`string`) — Valor bruto do tipo de taxa.
- **Retorno:** `string`

#### `normalizeInvestmentType(string $value): string`
- **Descrição:** Normaliza o tipo de investimento para `"cdb"`, `"lci"` ou `"lca"`.
- **Parâmetros:**
  - `$value` (`string`) — Valor bruto do tipo de investimento.
- **Retorno:** `string`

#### `showInvestmentDefaults(): void`
- **Descrição:** Exibe no console os valores padrão do investimento.
- **Parâmetros:** Nenhum.
- **Retorno:** `void`

---

### App\Contracts\CalculatesRateInterface

Interface para operações matemáticas de taxas.

#### `calculateByCDI(string $cdiCurrentRate, string $cdiPercentage): string`
- **Descrição:** Calcula a taxa anual efetiva com base no percentual do CDI.
- **Fórmula:** $$r_{efetiva} = \dfrac{r_{CDI}}{100} \times p_{CDI}$$
- **Exemplo:** CDI = 13,65%, Percentual = 110% → $$(13,65 / 100) \times 110 = 15,015\%$$ a.a.
- **Relação BCB:** A taxa CDI é obtida da API do Banco Central (série 12 ou 4390).
- **Parâmetros:**
  - `$cdiCurrentRate` (`string`) — Taxa CDI anual atual em percentual (ex: `"13.65"`).
  - `$cdiPercentage` (`string`) — Percentual do CDI contratado (ex: `"110"` para 110% do CDI).
- **Retorno:** `string` — Taxa anual efetiva em percentual.

#### `calculateRateByMonths(string $annualRate, int $months): string`
- **Descrição:** Calcula a taxa proporcional simples para um período em meses.
- **Fórmula:** $$r_{periodo} = \dfrac{r_{anual} \times meses}{12}$$
- **Exemplo:** 15,015% a.a. por 6 meses → $$(15,015 \times 6) / 12 = 7,5075\%$$
- **Parâmetros:**
  - `$annualRate` (`string`) — Taxa anual em percentual (ex: `"15.015"`).
  - `$months` (`int`) — Número de meses.
- **Retorno:** `string` — Taxa proporcional em percentual (2 casas decimais).

#### `calculateRateByMonthsCompound(string $annualRate, int $months): string`
- **Descrição:** Calcula a taxa composta para um período em meses.
- **Fórmula:** $$r_{composta} = \left( \left(1 + \dfrac{r_{anual}}{100}\right)^{\frac{meses}{12}} - 1 \right) \times 100$$
- **Exemplo:** 15,015% a.a. por 6 meses → $$((1 + 0,15015)^{0,5} - 1) \times 100 = 7,24\%$$
- **Parâmetros:**
  - `$annualRate` (`string`) — Taxa anual em percentual.
  - `$months` (`int`) — Número de meses.
- **Retorno:** `string` — Taxa composta em percentual (12 casas decimais).

#### `calculateDailyRateFromAnnual(string $annualRate): string`
- **Descrição:** Converte taxa anual para taxa diária considerando 252 dias úteis.
- **Fórmula:** $$r_{diaria} = \left( \left(1 + \dfrac{r_{anual}}{100}\right)^{\frac{1}{252}} - 1 \right) \times 100$$
- **Exemplo:** 15,015% a.a. → $$((1 + 0,15015)^{1/252} - 1) \times 100 = 0,0555\%$$ ao dia
- **Relação BCB:** O padrão de 252 dias úteis é definido pelo Banco Central para mercado financeiro brasileiro.
- **Parâmetros:**
  - `$annualRate` (`string`) — Taxa anual em percentual.
- **Retorno:** `string` — Taxa diária em percentual (12 casas decimais).

#### `calculateAmountByBusinessDays(string $initialCapital, string $dailyRatePercent, int $businessDays): string`
- **Descrição:** Calcula o montante final pela capitalização diária da taxa over ao longo dos dias úteis.
- **Fórmula:** Para cada dia útil $$i$$ de 1 até $$n$$: $$M_i = M_{i-1} + \left( M_{i-1} \times \dfrac{r_{diaria}}{100} \right)$$, arredondado para 2 casas decimais a cada passo.
- **Exemplo:** Capital = R\$ 10.000, taxa diária = 0,0555%, 126 dias úteis → Montante ≈ R\$ 10.728,99
- **Relação BCB:** A capitalização em dias úteis segue a convenção do mercado brasileiro (taxa over), regulamentada pelo Banco Central.
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial (ex: `"10000.00"`).
  - `$dailyRatePercent` (`string`) — Taxa diária em percentual.
  - `$businessDays` (`int`) — Número de dias úteis.
- **Retorno:** `string` — Montante bruto final.

#### `calculateAmountBruto(string $initialCapital, string $cdiCurrentRate): string`
- **Descrição:** Calcula o montante bruto usando taxa diretamente.
- **Fórmula:** $$M = C + \left( C \times \dfrac{r_{CDI}}{100} \right)$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$cdiCurrentRate` (`string`) — Taxa CDI em percentual.
- **Retorno:** `string` — Montante bruto.

#### `convertSelicMetaToOver(string $selicMeta, bool $isOver = false, string $spread = '0.19335938'): string`
- **Descrição:** Converte a taxa Selic Meta para Selic Over adicionando um spread.
- **Fórmula:** $$r_{Over} = r_{SelicMeta} + spread$$ (se $$isOver = false$$)
- **Exemplo:** Selic Meta = 14,25% → $$14,25 + 0,19335938 = 14,44335938\%$$
- **Relação BCB:** A Selic Meta é definida pelo COPOM. A Selic Over é a taxa efetiva praticada no mercado. O spread converte uma na outra conforme aproximação do mercado.
- **Parâmetros:**
  - `$selicMeta` (`string`) — Taxa Selic Meta em percentual (ex: `"14.25"`).
  - `$isOver` (`bool`, default `false`) — Se `true`, retorna o próprio valor sem conversão.
  - `$spread` (`string`, default `'0.19335938'`) — Spread em pontos percentuais para conversão.
- **Retorno:** `string` — Taxa Selic Over em percentual.

---

### App\Contracts\CalculatesTaxInterface

Interface para cálculos de impostos (IR e IOF).

#### `calculateIR(string $initialCapital, string $amountBruto, int $days, bool $isIsento = false, ?string $iofValueOverride = null): string`
- **Descrição:** Calcula o montante líquido após dedução do IR e IOF.
- **Fórmula:**
  $$
  \begin{aligned}
  L_{bruto} &= M_{bruto} - C \\
  L_{posIOF} &= L_{bruto} - IOF \\
  IR &= L_{posIOF} \times aliquota(dias) \\
  M_{liquido} &= C + L_{posIOF} - IR
  \end{aligned}
  $$
  Onde a alíquota de IR é regressiva por prazo:
  - $$\leq 180$$ dias: $$22,5\%$$
  - $$181$$ a $$360$$ dias: $$20,0\%$$
  - $$361$$ a $$720$$ dias: $$17,5\%$$
  - $$> 720$$ dias: $$15,0\%$$
- **Exemplo:** C = R\$ 10.000, M\_bruto = R\$ 10.728,99, 181 dias, IOF = 0:
  $$L_{bruto} = 728,99$$
  $$IR = 728,99 \times 20\% = 145,80$$
  $$M_{liquido} = 10.000 + (728,99 - 145,80) = 10.583,19$$
- **Relação BCB:** As alíquotas de IR seguem a legislação brasileira para investimentos de renda fixa. A isenção de LCI/LCA é definida pelo CMN (Conselho Monetário Nacional), ligado ao BCB.
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial investido.
  - `$amountBruto` (`string`) — Montante bruto final.
  - `$days` (`int`) — Dias corridos do investimento.
  - `$isIsento` (`bool`, default `false`) — Se `true`, investimento isento de IR (LCI/LCA).
  - `$iofValueOverride` (`?string`, default `null`) — Valor do IOF pré-calculado para dedução.
- **Retorno:** `string` — Montante líquido final após impostos.

#### `calculateIOFValue(string $lucroBruto, int $days): string`
- **Descrição:** Calcula o valor do IOF sobre o lucro bruto.
- **Fórmula:**
  - Se $$days > 30$$: $$IOF = 0$$
  - Senão: $$IOF = L_{bruto} \times aliquota_{dias}$$
  - Tabela de alíquotas (ver seção [Fórmulas > IOF](#11-iof-imposto-sobre-operações-financeiras))
- **Exemplo:** Lucro bruto = R\$ 100,00, dia 5 → $$aliquota = 84\%$$ → $$IOF = 100 \times 0,84 = 84,00$$
- **Relação BCB:** A tabela regressiva de IOF segue o regulamento do Banco Central para operações financeiras.
- **Parâmetros:**
  - `$lucroBruto` (`string`) — Lucro bruto do investimento.
  - `$days` (`int`) — Dias decorridos desde a aplicação.
- **Retorno:** `string` — Valor do IOF.

---

### App\Contracts\CalculatesProfitInterface

Interface para cálculos de lucro.

#### `calculateProfitBruto(string $initialCapital, string $amountBruto): string`
- **Descrição:** Calcula o lucro bruto.
- **Fórmula:** $$L_{bruto} = M_{bruto} - C$$
- **Exemplo:** C = R\$ 10.000, M\_bruto = R\$ 10.728,99 → $$L_{bruto} = R\$ 728,99$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountBruto` (`string`) — Montante bruto final.
- **Retorno:** `string` — Lucro bruto.

#### `calculateProfitLiquid(string $initialCapital, string $amountLiquid): string`
- **Descrição:** Calcula o lucro líquido (após impostos).
- **Fórmula:** $$L_{liquido} = M_{liquido} - C$$
- **Exemplo:** C = R\$ 10.000, M\_liquido = R\$ 10.583,19 → $$L_{liquido} = R\$ 583,19$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountLiquid` (`string`) — Montante líquido final.
- **Retorno:** `string` — Lucro líquido.

#### `calculateDailyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $businessDays): string`
- **Descrição:** Calcula o lucro líquido diário para investimentos tributados (CDB).
- **Cálculo:** Calcula IR+IOF sobre o lucro bruto internamente e divide pelos dias úteis.
- **Fórmula:** $$L_{diario} = \dfrac{L_{liquido}}{diasUteis}$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountBruto` (`string`) — Montante bruto.
  - `$days` (`int`) — Dias corridos.
  - `$businessDays` (`int`) — Dias úteis.
- **Retorno:** `string` — Lucro líquido diário (4 casas decimais).

#### `calculateDailyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $businessDays): string`
- **Descrição:** Calcula o lucro líquido diário para investimentos isentos (LCI/LCA).
- **Fórmula:** $$L_{diario} = \dfrac{M_{bruto} - C}{diasUteis}$$
- **Exemplo:** C = R\$ 50.000, M\_bruto = R\$ 57.142,93, 252 dias úteis → $$(57.142,93 - 50.000) / 252 = R\$ 28,34$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountBruto` (`string`) — Montante bruto.
  - `$businessDays` (`int`) — Dias úteis.
- **Retorno:** `string` — Lucro líquido diário.

#### `calculateMonthlyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $months): string`
- **Descrição:** Calcula o lucro líquido mensal para investimentos tributados.
- **Fórmula:** $$L_{mensal} = \dfrac{L_{liquido}}{meses}$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountBruto` (`string`) — Montante bruto.
  - `$days` (`int`) — Dias corridos.
  - `$months` (`int`) — Meses do investimento.
- **Retorno:** `string` — Lucro líquido mensal (2 casas decimais).

#### `calculateMonthlyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $months): string`
- **Descrição:** Calcula o lucro líquido mensal para investimentos isentos.
- **Fórmula:** $$L_{mensal} = \dfrac{M_{bruto} - C}{meses}$$
- **Exemplo:** C = R\$ 50.000, M\_bruto = R\$ 57.142,93, 12 meses → $$(57.142,93 - 50.000) / 12 = R\$ 595,24$$
- **Parâmetros:**
  - `$initialCapital` (`string`) — Capital inicial.
  - `$amountBruto` (`string`) — Montante bruto.
  - `$months` (`int`) — Meses do investimento.
- **Retorno:** `string` — Lucro líquido mensal.

---

### App\Services\CdiApiClient

Cliente HTTP para a API SGS do Banco Central do Brasil.

#### `fetchLatestRecord(string $url): ?array`
- **Descrição:** Busca o registro mais recente da API SGS do BCB e retorna seu valor e data.
- **Relação BCB:** Comunica-se diretamente com a API do Banco Central (`api.bcb.gov.br`), séries 12 (CDI diário) ou 4390 (CDI mensal).
- **Tratamento:** Ignora registros com data futura. Retorna o último registro válido.
- **Parâmetros:**
  - `$url` (`string`) — URL completa do endpoint da API SGS.
- **Retorno:** `?array` — Array associativo com chaves `'valor'` (`float`) e `'data'` (`string`), ou `null` em caso de falha.

#### `parseDate(string $date): ?\DateTimeImmutable`
- **Descrição:** Converte uma data no formato brasileiro (`d/m/Y`) para `DateTimeImmutable`.
- **Parâmetros:**
  - `$date` (`string`) — Data no formato `d/m/Y`.
- **Retorno:** `?\DateTimeImmutable`

#### `fetchUrl(string $url): ?string`
- **Descrição:** Executa requisição HTTP GET via cURL para a URL fornecida.
- **Configuração:** Timeout de 5 segundos, header `Accept: application/json`, verificação SSL.
- **Parâmetros:**
  - `$url` (`string`) — URL para requisição.
- **Retorno:** `?string` — Corpo da resposta ou `null` em caso de falha.

---

### App\Services\CdiRateCalculator

Validação e anualização de taxas CDI.

#### `isAnnualRateValid(float $value): bool`
- **Descrição:** Verifica se a taxa CDI anual é válida (entre 1% e 100%).
- **Parâmetros:**
  - `$value` (`float`) — Taxa a ser validada.
- **Retorno:** `bool` — `true` se $$1 < valor < 100$$.

#### `isDailyRateValid(float $value): bool`
- **Descrição:** Verifica se a taxa CDI diária é válida (entre 0% e 1%).
- **Parâmetros:**
  - `$value` (`float`) — Taxa diária em percentual.
- **Retorno:** `bool` — `true` se $$0 < valor < 1$$.

#### `annualizeDailyRate(float $dailyRate): float`
- **Descrição:** Anualiza uma taxa diária considerando 252 dias úteis.
- **Fórmula:** $$r_{anual} = \left( \left(1 + \dfrac{d}{100}\right)^{252} - 1 \right) \times 100$$
- **Exemplo:** Taxa diária = 0,0523% → $$((1 + 0,000523)^{252} - 1) \times 100 \approx 14,09\%$$
- **Relação BCB:** Os 252 dias úteis são o padrão do mercado financeiro brasileiro (regulado pelo BCB).
- **Parâmetros:**
  - `$dailyRate` (`float`) — Taxa diária em percentual (ex: `0.0523`).
- **Retorno:** `float` — Taxa anual em percentual.

---

### App\Services\CdiRateService

Serviço de obtenção da taxa CDI do Banco Central do Brasil com fallback.

#### `__construct(?CdiApiClient $apiClient, ?CdiRateCalculator $calculator)`
- **Descrição:** Construtor com dependências opcionais (injetadas ou criadas internamente).

#### `fetchCdiAnnual(string $selicMetaFallback, string $spreadFallback = '-0.10'): array`
- **Descrição:** Obtém a taxa CDI anual prioritariamente da API do BCB.
- **Relação BCB:**
  1. Tenta série 12 (CDI diário) — busca últimos 5 registros, valida ($$0 < valor < 1$$), anualiza via $$(1 + d/100)^{252} - 1$$
  2. Se falhar, tenta série 4390 (CDI mensal) — se $$valor > 5$$ é taxa anual; senão, anualiza via $$(1 + valor/100)^{12} - 1$$
  3. Se ambas falharem, usa fallback offline: $$CDI = Selic Meta + spread$$
- **Parâmetros:**
  - `$selicMetaFallback` (`string`) — Taxa Selic Meta para fallback (ex: `"14.25"`).
  - `$spreadFallback` (`string`, default `'-0.10'`) — Spread para fallback.
- **Retorno:** `array` — `['rate' => string, 'source' => string]`, onde `source` é `'bcb_daily'`, `'bcb_monthly'` ou `'fallback'`.

#### `fetchSelicAnnual(): string`
- **Descrição:** Obtém a taxa Selic anualizada. Tenta API BCB → banco de dados → `.env`.
- **Fluxo:**
  1. Tenta série 11 (Selic diária) da API BCB — anualiza via $$(1 + d/100)^{252} - 1$$ e salva no banco `selic_rates`
  2. Se falhar, consulta última taxa armazenada no banco `selic_rates`
  3. Se banco vazio, usa `DEFAULT_SELIC_META` do `.env`
- **Retorno:** `string` — Taxa Selic anual em percentual.

#### `getDisplaySelic(): string`
- **Descrição:** Retorna a taxa Selic formatada com 2 casas decimais para exibição.

#### `fallbackCdi(string $selicMeta, string $spread, string $reason): array`
- **Descrição:** Calcula CDI via fallback offline quando a API do BCB está indisponível.
- **Fórmula:** $$CDI = Selic Meta + spread$$
- **Relação BCB:** Aproximação do CDI Over a partir da Selic Meta definida pelo COPOM.
- **Parâmetros:**
  - `$selicMeta` (`string`) — Taxa Selic Meta.
  - `$spread` (`string`) — Spread a ser adicionado.
  - `$reason` (`string`) — Motivo do fallback (para logging/debug).
- **Retorno:** `array` — `['rate' => string, 'source' => string]`.

---

### App\Services\RateCalculationService

Implementação concreta de `CalculatesRateInterface` com precisão BC Math.

#### `__construct(int $scale = 14)`
- **Descrição:** Define a escala (casas decimais) para operações BC Math.
- **Parâmetros:**
  - `$scale` (`int`, default `14`) — Precisão matemática.

Métodos implementados (ver `CalculatesRateInterface` para detalhes):

| Método | Precisão | Detalhes do Cálculo |
|--------|----------|---------------------|
| `calculateByCDI` | BC Math scale 14 | `bcmul(bcdiv(cdiRate, 100, 14), cdiPercentage, 14)` → normalizado para 2 casas |
| `calculateRateByMonths` | 2 casas | Multiplicação e divisão com BC Math |
| `calculateRateByMonthsCompound` | 12 casas | Usa `pow()` nativo do PHP (float) |
| `calculateDailyRateFromAnnual` | 12 casas | `pow(1 + annual/100, 1/252) - 1` |
| `calculateAmountByBusinessDays` | 2 casas | Loop iterativo: `saldo += saldo * taxaDiaria / 100` |
| `calculateAmountBruto` | BC Math | `bcadd(capital, bcdiv(bcmul(capital, taxa), 100), 2)` |
| `convertSelicMetaToOver` | 2 casas | Se `isOver=true`, retorna Selic Meta; senão `bcadd(selicMeta, spread)` |

---

### App\Services\TaxCalculationService

Implementação concreta de `CalculatesTaxInterface`.

#### `__construct(int $scale = 6, ?array $iofTable = null)`
- **Descrição:** Usa a tabela IOF definida em `ServiceBase::IOF_TABLE` (30 dias de alíquotas regressivas).

Métodos implementados (ver `CalculatesTaxInterface` para detalhes):

| Método | Precisão | Detalhes do Cálculo |
|--------|----------|---------------------|
| `calculateIR` | scale 6 | Lucro bruto → dedução IOF → aplicação alíquota IR regressiva por prazo |
| `calculateIOFValue` | scale 6 | `lucroBruto * aliquota` da tabela regressiva. IOF = 0 se dias > 30 |

---

### App\Services\ProfitCalculationService

Implementação concreta de `CalculatesProfitInterface`.

#### `__construct(CalculatesTaxInterface $taxService)`

Métodos implementados (ver `CalculatesProfitInterface` para detalhes):

| Método | Precisão | Detalhes do Cálculo |
|--------|----------|---------------------|
| `calculateProfitBruto` | BC Math | `bcsub(amountBruto, initialCapital)` |
| `calculateProfitLiquid` | BC Math | `bcsub(amountLiquid, initialCapital)` |
| `calculateDailyProfitLiquid` | 4 casas | Lucro líquido (via `TaxCalculationService`) $$\div$$ diasUteis |
| `calculateDailyProfitLiquidIsento` | 4 casas | $$(amountBruto - capital) / diasUteis$$ |
| `calculateMonthlyProfitLiquid` | 2 casas | Lucro líquido $$\div$$ meses (float) |
| `calculateMonthlyProfitLiquidIsento` | 2 casas | $$(amountBruto - capital) / meses$$ |

---

### App\Services\AmountFormatterService

Implementação concreta de `FormatsAmountInterface`.

#### `__construct(int $precision = 2)`

#### `normalizeAmount(string $amount): string`
- **Descrição:** Arredonda para 2 casas decimais e formata com precisão.
- **Cálculo:** $$round(valor, 2) \rightarrow number\_format(valor, 2, '.', '')$$

#### `normalizeAmountRounded(string $amount): string`
- **Descrição:** Mesmo que `normalizeAmount`.

#### `normalizeAmountTruncated(string $amount): string`
- **Descrição:** Trunca (floor) para 2 casas decimais.
- **Cálculo:** $$\lfloor valor \times 100 \rfloor / 100$$ → formatado.

---

### App\Services\BusinessDayService

Implementação concreta de `CountsBusinessDaysInterface`. Conta dias úteis considerando feriados brasileiros.

#### `__construct(array $fixedHolidays = self::FIXED_HOLIDAYS, array $easterOffsets = self::EASTER_OFFSETS)`
- **Parâmetros:**
  - `$fixedHolidays` (`array`) — Feriados fixos brasileiros.
  - `$easterOffsets` (`array`) — Offsets relativos à Páscoa para feriados móveis.

#### `isBusinessDay(string $date): bool`
- **Descrição:** Verifica se uma data é dia útil (segunda a sexta, não feriado brasileiro).
- **Relação BCB:** Considera o calendário de feriados nacionais definido pelo Banco Central.
- **Parâmetros:**
  - `$date` (`string`) — Data no formato `Y-m-d`.
- **Retorno:** `bool`

#### `countBusinessDays(string $startDate, string $endDate): int`
- **Descrição:** Conta dias úteis entre duas datas (exclusive startDate, inclusive endDate).
- **Relação BCB:** Contagem de dias úteis conforme convenção do mercado brasileiro (252 dias/ano).
- **Parâmetros:**
  - `$startDate` (`string`) — Data inicial (`Y-m-d`).
  - `$endDate` (`string`) — Data final (`Y-m-d`).
- **Retorno:** `int`

#### `getHolidaysForYear(int $year): array`
- **Descrição:** Gera todos os feriados brasileiros para um dado ano.
- **Feriados fixos:** 01-01, 21-04, 01-05, 07-09, 12-10, 02-11, 15-11, 20-11, 25-12
- **Feriados móveis (relativos à Páscoa):** Carnaval (-48d), Quarta-feira de Cinzas (-47d), Sexta-feira Santa (-2d), Corpus Christi (+60d)
- **Relação BCB:** Calendário de feriados conforme definido pelo Banco Central do Brasil.
- **Parâmetros:**
  - `$year` (`int`) — Ano para gerar feriados.
- **Retorno:** `array` — Array de strings no formato `Y-m-d`.

#### `calculateEasterDate(int $year): \DateTimeImmutable`
- **Descrição:** Calcula a data da Páscoa usando o algoritmo de Gauss.
- **Parâmetros:**
  - `$year` (`int`) — Ano.
- **Retorno:** `\DateTimeImmutable`

---

### App\Services\ListInvestmentService

Service de listagem com fallback: tenta ler do MySQL primeiro; se falhar (conexão indisponível, tabelas ausentes), cai no repositório JSON.

#### `__construct(InvestmentRepositoryInterface $jsonRepository, ListInvestmentRepository $mysqlRepository)`
- **Parâmetros:**
  - `$jsonRepository` — Repositório JSON (`InvestmentRepositoryInterface`).
  - `$mysqlRepository` — Repositório MySQL (`ListInvestmentRepository`).

#### `execute(): array`
- **Descrição:** Tenta `$mysqlRepository->execute()` dentro de um `try/catch`. Se lançar exceção, retorna `$jsonRepository->all()`.
- **Retorno:** `array`

#### `paginated(int $page, int $perPage): array`
- **Descrição:** Tenta `$mysqlRepository->paginated()` dentro de um `try/catch`. Se lançar exceção, retorna `$jsonRepository->paginated()`.
- **Parâmetros:**
  - `$page` (`int`) — Número da página.
  - `$perPage` (`int`) — Registros por página.
- **Retorno:** `array` — `['data' => array, 'total' => int]`

---

### App\Services\ShowInvestmentService

Service de busca por ID com fallback: tenta MySQL primeiro; se falhar, usa JSON.

#### `__construct(InvestmentRepositoryInterface $jsonRepository, ShowInvestmentRepository $mysqlRepository)`
- **Parâmetros:**
  - `$jsonRepository` — Repositório JSON.
  - `$mysqlRepository` — Repositório MySQL (`ShowInvestmentRepository`).

#### `execute(string $id): ?array`
- **Descrição:** Tenta `$mysqlRepository->execute($id)`. Se lançar exceção, retorna `$jsonRepository->findById($id)`.
- **Retorno:** `?array`

---

### App\Services\InvestmentService

Serviço central que orquestra todo o cálculo do investimento.

#### `__construct(CalculatesRateInterface, CalculatesTaxInterface, CalculatesProfitInterface, CountsBusinessDaysInterface, FormatsAmountInterface, InvestmentCalculation, InvestmentRepositoryInterface)`

#### `handle(InvestmentInput $input): Investment`
- **Descrição:** Executa `process()`, persiste no MySQL primeiro (`CreateInvestmentRepository`), depois salva no JSON (`JsonFileInvestmentRepository`) usando o mesmo ID gerado pelo MySQL. O ID fica acessível via `getLastId()` e `getLastSavedId()`.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

#### `recalculate(InvestmentInput $input): Investment`
- **Descrição:** Recalcula o investimento executando `process()` sem persistir em nenhum repositório. Usado pelo fluxo de estimativa (`GET /api/calculate?params...`).
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

#### `getLastSavedId(): ?int`
- **Descrição:** Retorna o ID do último investimento salvo via `handle()` (mesmo valor de `getLastId()`).
- **Retorno:** `?int`

#### `getLastId(): ?int`
- **Descrição:** Retorna o ID gerado pelo MySQL na última execução de `handle()`.
- **Retorno:** `?int`

#### `recalculateUpdate(int|string $id, InvestmentInput $input): Investment`
- **Descrição:** Recalcula o investimento (`process()`) e persiste a atualização no repositório. Usado pelo fluxo de update.
- **Parâmetros:**
  - `$id` (`int|string`) — ID do investimento a atualizar.
  - `$input` (`InvestmentInput`) — Dados de entrada atualizados.
- **Retorno:** `Investment`

#### `process(InvestmentInput $input): Investment`
- **Descrição:** Núcleo do cálculo do investimento. Executa:
  1. Resolução de dias corridos e dias úteis
  2. Resolução do percentual de rendimento (pré ou pós-fixado)
  3. Cálculo da taxa diária via $$(1 + r_{anual}/100)^{1/252} - 1$$
  4. Cálculo dos valores brutos via capitalização diária em dias úteis
  5. Cálculo dos lucros (bruto, líquido, mensal, diário)
  6. Cálculo dos impostos (IR e IOF)
  7. Montagem do objeto `Investment` de resultado
- **Relação BCB:** Integra taxa CDI do BCB, taxa Selic, dias úteis, feriados brasileiros, tributação IR/IOF.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

#### `resolveDays(\DateTimeImmutable $app, \DateTimeImmutable $red): int`
- **Descrição:** Calcula dias corridos entre aplicação e resgate.
- **Cálculo:** Diferença em dias entre as duas datas.
- **Parâmetros:**
  - `$app` (`\DateTimeImmutable`) — Data de aplicação.
  - `$red` (`\DateTimeImmutable`) — Data de resgate.
- **Retorno:** `int`

#### `resolveBusinessDays(InvestmentInput $input): int`
- **Descrição:** Calcula dias úteis entre aplicação e resgate via `BusinessDayService`.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `int`

#### `calculateTaxValues(InvestmentInput, string $profitBrutoRaw, string $amountBrutoRaw, int $days, \DateTimeImmutable $app, \DateTimeImmutable $red): array`
- **Descrição:** Calcula valores de IOF e IR, considerando período de carência de 30 dias para IOF.
- **Relação BCB:** IOF segue tabela regressiva do BCB; IR segue alíquotas regressivas da legislação brasileira.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$profitBrutoRaw` (`string`) — Lucro bruto (valor cru).
  - `$amountBrutoRaw` (`string`) — Montante bruto (valor cru).
  - `$days` (`int`) — Dias corridos.
  - `$app` (`\DateTimeImmutable`) — Data de aplicação.
  - `$red` (`\DateTimeImmutable`) — Data de resgate.
- **Retorno:** `array` — `['iofValue' => string, 'amountLiquid' => string, 'iofRaw' => string]`

---

### App\Services\DailyReportService

Gera relatório diário detalhado de simulação (período de IOF).

#### `generate(InvestmentInput $input, Investment $result): void`
- **Descrição:** Gera e exibe uma tabela dia-a-dia da aplicação até o resgate (limitado a 30 dias — período de IOF).
- **Relação BCB:** Simula a incidência diária de IOF conforme tabela regressiva do BCB.
- **Exemplo de saída:**
  ```
  ========================================================================================================
                Simulação diária (período de cobrança do IOF)
  ========================================================================================================
  Data           Dias  Dias Úteis      % Mês        Bruto    Lucro Bruto          IOF    Lucro Líq.
  --------------------------------------------------------------------------------------------------------
  05/01/2026        0           0       0,00%     10.000,00          0,00         0,00          0,00
  06/01/2026        1           1       3,33%     10.005,55          5,55         5,33          0,22
  07/01/2026        2           2       6,67%     10.011,11         11,11        10,33          0,78
  ...
  ```
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada do investimento.
  - `$result` (`Investment`) — Resultado do investimento.

#### `getLoopEndDate(\DateTimeImmutable $red, \DateTimeImmutable $iofPeriodEnd): \DateTimeImmutable`
- **Descrição:** Retorna a menor data entre resgate e fim do período de IOF (30 dias).
- **Parâmetros:**
  - `$red` (`\DateTimeImmutable`) — Data de resgate.
  - `$iofPeriodEnd` (`\DateTimeImmutable`) — Fim do período de IOF.

#### `getDailyPercentage(InvestmentInput $input): float`
- **Descrição:** Obtém percentual anual de rendimento e converte para taxa diária (252 dias úteis).
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.

#### `printHeader(): void`
- **Descrição:** Exibe cabeçalho da tabela "Simulação diária (período de cobrança do IOF)".

#### `printFooter(InvestmentInput $input): void`
- **Descrição:** Exibe rodapé com notas sobre isenção de LCI/LCA.

#### `printDayRow(...): void`
- **Descrição:** Formata e exibe uma linha da tabela: data, dias, dias úteis, percentual do mês, montante bruto, lucro bruto, IOF, lucro líquido.

#### `shouldSkipDay(\DateTimeImmutable $day, int $displayDay): bool`
- **Descrição:** Verifica se o dia deve ser pulado (não é dia útil, exceto dia 0 que sempre é exibido).

#### `getIofValue(InvestmentInput, string $ProfitBruto, int $displayDay): string`
- **Descrição:** Calcula o valor do IOF para um dia específico.
- **Relação BCB:** Utiliza tabela regressiva de IOF do BCB.

#### `getLiquidAmount(InvestmentInput, string $AmountRaw, int $displayDay): string`
- **Descrição:** Calcula o montante líquido após IR e IOF para um dia específico.

#### `getMonthProgress(int $displayDay, int $currentBusinessDays, int $businessDaysInPeriod): float`
- **Descrição:** Percentual de progresso do mês (capado em 100%).

---

### App\Helpers\InvestmentCalculationHelper

Classe abstrata auxiliar para cálculos de investimento.

#### `resolveDisplayPercentage(InvestmentInput $input, CalculatesRateInterface $rateService): string`
- **Descrição:** Resolve o percentual anual de rendimento para exibição/cálculo.
- **Cálculo:**
  - **Pré-fixado:** Retorna `input.preFixedAnnualRate`
  - **Pós-fixado:** $$r_{efetiva} = \dfrac{r_{CDI}}{100} \times p_{CDI}$$
- **Relação BCB:** Para pós-fixado, depende da taxa CDI obtida da API do BCB ou da Selic Meta.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$rateService` (`CalculatesRateInterface`) — Serviço de cálculo de taxas.
- **Retorno:** `string` — Percentual anual de rendimento.

#### `calculateGrossValues(InvestmentInput $input, string $dailyPercentage, \DateTimeImmutable $redemptionDT, CountsBusinessDaysInterface, CalculatesRateInterface, CalculatesProfitInterface, FormatsAmountInterface): array`
- **Descrição:** Calcula os valores brutos do investimento: conta dias úteis, capitaliza diariamente.
- **Cálculo:**
  1. Conta dias úteis entre aplicação e resgate
  2. Capitalização diária: $$M_n = M_0 \times \prod_{i=1}^{n} (1 + r_{diaria}/100)$$ (implementado como loop iterativo)
  3. Lucro bruto: $$L_{bruto} = M_n - C$$
- **Relação BCB:** Dias úteis consideram feriados brasileiros (BCB). Capitalização segue convenção de taxa over do mercado brasileiro.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$dailyPercentage` (`string`) — Percentual diário.
  - `$redemptionDT` (`\DateTimeImmutable`) — Data de resgate.
  - `$businessDayService` (`CountsBusinessDaysInterface`) — Serviço de dias úteis.
  - `$rateService` (`CalculatesRateInterface`) — Serviço de taxas.
  - `$profitService` (`CalculatesProfitInterface`) — Serviço de lucros.
  - `$formatter` (`FormatsAmountInterface`) — Formatador de valores.
- **Retorno:** `array` — `['amountBrutoRaw', 'amountBruto', 'profitBrutoRaw', 'profitBruto']`

---

### App\Factories\BaseFactory

Classe base para factories com validação de dados e feriados brasileiros.

#### `normalizeDateOrFail(string $value): string`
- **Descrição:** Valida e retorna data no formato `Y-m-d`. Lança exceção se inválida.
- **Parâmetros:**
  - `$value` (`string`) — Data a validar.

#### `askValidDate(string $message, string $default): string`
- **Descrição:** Solicita data interativamente até que uma data válida seja fornecida.
- **Parâmetros:**
  - `$message` (`string`) — Mensagem do prompt.
  - `$default` (`string`) — Valor padrão.

#### `askValidBusinessDay(string $message, string $default): string`
- **Descrição:** Solicita data interativamente, validando se é dia útil (seg-sex, não feriado).
- **Relação BCB:** Feriados conforme calendário do Banco Central.
- **Parâmetros:**
  - `$message` (`string`) — Mensagem do prompt.
  - `$default` (`string`) — Valor padrão.

#### `ensureIsBusinessDay(string $date, string $label = 'Data de aplicação'): void`
- **Descrição:** Valida se a data é dia útil. Lança `\InvalidArgumentException` se cair em fim de semana ou feriado.
- **Relação BCB:** Calendário de feriados brasileiros conforme BCB.
- **Parâmetros:**
  - `$date` (`string`) — Data no formato `Y-m-d`.
  - `$label` (`string`) — Rótulo para mensagem de erro.

#### `askPositiveNumber(string $message, string $default, string $label): string`
- **Descrição:** Solicita número positivo interativamente.
- **Parâmetros:**
  - `$message` (`string`) — Mensagem.
  - `$default` (`string`) — Padrão.
  - `$label` (`string`) — Rótulo para mensagem de erro.

#### `askPositiveInteger(string $message, string $default, string $label): string`
- **Descrição:** Solicita inteiro positivo interativamente.
- **Parâmetros:**
  - `$message` (`string`) — Mensagem.
  - `$default` (`string`) — Padrão.
  - `$label` (`string`) — Rótulo para mensagem de erro.

#### `normalizePositiveNumberOrFail(string $value, string $label): string`
- **Descrição:** Valida número positivo. Lança exceção se inválido.
- **Parâmetros:**
  - `$value` (`string`) — Valor a validar.
  - `$label` (`string`) — Rótulo.

#### `normalizePositiveIntegerOrFail(string $value, string $label): string`
- **Descrição:** Valida inteiro positivo. Lança exceção se inválido.
- **Parâmetros:**
  - `$value` (`string`) — Valor.
  - `$label` (`string`) — Rótulo.

#### `calculateRedemptionDateByMonths(string $applicationDate, int $months): string`
- **Descrição:** Calcula data de resgate somando meses à data de aplicação, ajustando para trás se cair em fim de semana ou feriado brasileiro.
- **Relação BCB:** Ajuste de feriados segue calendário do BCB.
- **Parâmetros:**
  - `$applicationDate` (`string`) — Data de aplicação (`Y-m-d`).
  - `$months` (`int`) — Número de meses.
- **Retorno:** `string` — Data de resgate (`Y-m-d`).

#### `isWeekendOrHoliday(\DateTimeImmutable $date): bool`
- **Descrição:** Verifica se a data é sábado, domingo ou feriado brasileiro.
- **Relação BCB:** Feriados conforme calendário do Banco Central.
- **Parâmetros:**
  - `$date` (`\DateTimeImmutable`) — Data a verificar.
- **Retorno:** `bool`

#### `calculateEaster(int $year): \DateTimeImmutable`
- **Descrição:** Calcula Páscoa pelo algoritmo de Gauss.
- **Parâmetros:**
  - `$year` (`int`) — Ano.
- **Retorno:** `\DateTimeImmutable`

---

### App\Factories\InvestmentInputFactory

Factory que cria `InvestmentInput` a partir de argumentos CLI ou entrada interativa.

#### `__construct(CdiRateService $cdiRateService)`

#### `create(array $argv): InvestmentInput`
- **Descrição:** Cria um objeto `InvestmentInput` completo. Se interativo, solicita tipo de investimento, tipo de taxa, data de aplicação, prazo, capital, percentual CDI/taxa pré-fixada e Selic Meta.
- **Relação BCB:** Durante a criação, aciona `CdiRateService::fetchCdiAnnual()` para obter a taxa CDI ao vivo da API do BCB.
- **Parâmetros:**
  - `$argv` (`array`) — Argumentos da linha de comando.
- **Retorno:** `InvestmentInput`

---

### App\Presenters\AbstractInvestmentPresenter

Classe base para apresentação dos resultados.

#### `__construct(int $width = 60)`
- **Parâmetros:**
  - `$width` (`int`, default `60`) — Largura da tabela de exibição.

#### `renderHeader(string $rateType): void`
- **Descrição:** Exibe cabeçalho: "Cálculo de investimento [pre/pos-fixado] - CDB, LCI, LCA".

#### `renderInvestmentDetails(InvestmentInput, string $rateType, string $taxaSelicAtual, callable $money, int $businessDaysPerMonth): void`
- **Descrição:** Exibe detalhes: tipo, taxa, capital, rendimento, taxa Selic, datas, prazo, dias úteis.

#### `renderGrossSection(Investment $result, callable $money): void`
- **Descrição:** Exibe seção de valores brutos: lucro bruto, lucro bruto diário, montante bruto.

#### `renderTaxSection(Investment $result, callable $money): void`
- **Descrição:** Exibe seção de impostos: IOF, IR, com notas de isenção.

#### `renderNetSection(Investment $result, callable $money): void`
- **Descrição:** Exibe seção de valores líquidos: lucro líquido, mensal, diário, montante líquido final.

#### `renderFooter(): void`
- **Descrição:** Linha de fechamento da tabela.

#### `formatMoney(mixed $value): string`
- **Descrição:** Formata valor monetário no padrão brasileiro: `R$ 1.234,56`.
- **Parâmetros:**
  - `$value` (`mixed`) — Valor a formatar.
- **Retorno:** `string`

#### `resolveRateType(InvestmentInput $input): string`
- **Descrição:** Retorna `"pre-fixado"` ou `"pos-fixado"` conforme o tipo de taxa.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `string`

#### `resolveTaxaSelicAtual(InvestmentInput $input): string`
- **Descrição:** Retorna a taxa CDI Over ou `SelicMeta - 0.10` como fallback.
- **Relação BCB:** Taxa obtida da API do BCB ou calculada a partir da Selic Meta.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `string`

#### `resolveBusinessDaysPerMonth(InvestmentInput $input, Investment $result): int`
- **Descrição:** Calcula dias úteis por mês (arredondado: businessDays / months).
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$result` (`Investment`) — Resultado.
- **Retorno:** `int`

#### `formatPrazo(int $months): string`
- **Descrição:** Formata prazo como "N Meses (X anos Y meses)".
- **Parâmetros:**
  - `$months` (`int`) — Meses.
- **Retorno:** `string`

---

### App\Presenters\InvestmentPresenter

#### `__construct(int $width = 60)`

#### `display(InvestmentInput $input, Investment $result): void`
- **Descrição:** Monta a apresentação completa: cabeçalho → detalhes → bruto → impostos → líquido → rodapé.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$result` (`Investment`) — Resultado do investimento.

---

### App\Controllers\CalculateInvestmentEstimateController

Controller da API REST que calcula estimativa sem persistência (usado por `GET /api/calculate?params...`).

#### `execute(array $params): mixed`
- **Descrição:** Recebe parâmetros de investimento via query string, cria `InvestmentInput` via `HttpInputFactory`, executa `CalculateInvestmentUseCase::recalculate()` (sem persistir) e retorna JSON com input + resultado.
- **Parâmetros:**
  - `$params` (`array`) — Parâmetros da requisição (`investment_type`, `rate_type`, `capital`, `application_date`, `months`, etc.).
- **Retorno:** `mixed` — `null` (resposta JSON enviada diretamente).
- **Códigos HTTP:** `200` sucesso, `422` dados inválidos, `500` erro interno.

---

### App\Controllers\CalculateController

#### `execute(array $argv): array`
- **Descrição:** Exibe defaults, cria `InvestmentInput`, executa o use case, retorna dados para exibição.
- **Parâmetros:**
  - `$argv` (`array`) — Argumentos CLI.
- **Retorno:** `array` — `['input' => InvestmentInput, 'result' => Investment]`

---

### App\Controllers\InvestmentResultController

#### `execute(array $payload): null`
- **Descrição:** Delega exibição ao `InvestmentPresenter::display()`.
- **Parâmetros:**
  - `$payload` (`array`) — `['input' => InvestmentInput, 'result' => Investment]`
- **Retorno:** `null`

---

### App\Controllers\CliController

#### `execute(array $argv): Investment`
- **Descrição:** Delega ao `CliApplication::execute()`.
- **Parâmetros:**
  - `$argv` (`array`) — Argumentos CLI.
- **Retorno:** `Investment`

---

### App\Application\CliApplication

Orquestrador principal da aplicação CLI.

#### `execute(array $argv): Investment`
- **Descrição:** Orquestra o fluxo completo: (1) calcular via `CalculateController`, (2) exibir resultados via `InvestmentResultController`, (3) gerar relatório diário via `DailyReportService::generate()`.
- **Parâmetros:**
  - `$argv` (`array`) — Argumentos CLI.
- **Retorno:** `Investment`

---

### App\UseCases\CalculateInvestmentUseCase

#### `execute(InvestmentInput $input): Investment`
- **Descrição:** Executa o caso de uso de cálculo de investimento delegando ao `InvestmentService::handle()`. Calcula e persiste no repositório.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

#### `getLastSavedId(): ?int`
- **Descrição:** Retorna o ID gerado pelo MySQL na última execução de `execute()`.
- **Retorno:** `?int`

#### `getLastId(): ?int`
- **Descrição:** Retorna o ID gerado pelo MySQL (mesmo que `getLastSavedId()`).
- **Retorno:** `?int`

#### `recalculate(InvestmentInput $input): Investment`
- **Descrição:** Recalcula o investimento sem persistir (usa `InvestmentService::recalculate()`).
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

#### `recalculateUpdate(int|string $id, InvestmentInput $input): Investment`
- **Descrição:** Recalcula e persiste a atualização no repositório. Usado pelo fluxo de update.
- **Parâmetros:**
  - `$id` (`int|string`) — ID do investimento a atualizar.
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

---

### App\Repositories\InMemoryInvestmentRepository

Repositório em memória (útil para testes). Implementa `InvestmentRepositoryInterface`.

#### `save(InvestmentInput $input, Investment $result, ?int $id = null): int`
- **Descrição:** Armazena o resultado do investimento em memória com ID auto-incremental. Se `$id` for fornecido, usa esse ID em vez do auto-incremento.
- **Parâmetros:**
  - `$id` (`?int`, default `null`) — ID opcional (usado pelo `InvestmentService` para sincronizar com MySQL).
- **Retorno:** `int` — O ID gerado ou utilizado.

#### `all(): array`
- **Descrição:** Retorna todos os resultados armazenados.
- **Retorno:** `array`

#### `getLast(): ?array`
- **Descrição:** Retorna o último investimento armazenado.
- **Retorno:** `?array`

#### `findById(int|string $id): ?array`
- **Descrição:** Busca investimento por ID.
- **Retorno:** `?array`

#### `update(int|string $id, InvestmentInput $input, Investment $result): int`
- **Descrição:** Atualiza investimento existente por ID.
- **Retorno:** `int` — O ID do investimento atualizado.

#### `delete(int|string $id): bool`
- **Descrição:** Remove investimento por ID.
- **Retorno:** `bool` — `true` se removido.

---

### App\Repositories\JsonFileInvestmentRepository

Repositório persistente em arquivo JSON (`data/investments.json`). É o repositório padrão da aplicação. Implementa `InvestmentRepositoryInterface`.

#### `__construct(?string $filePath = null)`
- **Descrição:** Carrega dados do arquivo JSON. Se não existir, cria estrutura vazia.
- **Parâmetros:**
  - `$filePath` (`?string`) — Caminho customizado (padrão: `data/investments.json`).

#### `save(InvestmentInput $input, Investment $result, ?int $id = null): int`
- **Descrição:** Salva novo investimento e persiste no arquivo. Se `$id` for fornecido (pelo `InvestmentService`, vindo do MySQL AUTO_INCREMENT), usa esse ID; caso contrário, gera ID auto-incremental interno.
- **Parâmetros:**
  - `$id` (`?int`, default `null`) — ID opcional para sincronizar com MySQL.
- **Retorno:** `int` — O ID utilizado.

#### `all(): array`
- **Descrição:** Retorna todos os investimentos com objetos `InvestmentInput` e `Investment` reconstruídos.
- **Retorno:** `array`

#### `getLast(): ?array`
- **Descrição:** Retorna o último investimento.
- **Retorno:** `?array`

#### `findById(int|string $id): ?array`
- **Descrição:** Busca por ID.
- **Retorno:** `?array`

#### `update(int|string $id, InvestmentInput $input, Investment $result): int`
- **Descrição:** Atualiza e persiste no arquivo.
- **Retorno:** `int` — O ID do investimento atualizado.

#### `delete(int|string $id): bool`
- **Descrição:** Remove e persiste no arquivo.
- **Retorno:** `bool`

#### `paginated(int $page, int $perPage): array`
- **Descrição:** Retorna uma página de investimentos usando `array_slice` no storage. Converte para objetos apenas os registros da página solicitada.
- **Parâmetros:**
  - `$page` (`int`) — Número da página (1-indexed).
  - `$perPage` (`int`) — Quantidade de registros por página.
- **Retorno:** `array` — `['data' => array, 'total' => int]`

---

### App\Factories\HttpInputFactory

Factory que cria `InvestmentInput` a partir de parâmetros de requisição HTTP (JSON ou form).

#### `__construct(CdiRateService $cdiRateService)`

#### `create(array $params): InvestmentInput`
- **Descrição:** Extrai e valida os parâmetros: `investment_type`, `rate_type`, `application_date`, `months`, `capital`, `cdi`, `selic_meta`, `pre_rate`, `cdi_annual`. Para investimentos pós-fixados, obtém a taxa CDI via API do BCB.
- **Parâmetros:**
  - `$params` (`array`) — Parâmetros da requisição.
- **Retorno:** `InvestmentInput`

#### `inputToParams(InvestmentInput $input): array`
- **Descrição:** Converte um `InvestmentInput` de volta para array de parâmetros (útil para mesclar em updates).
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Objeto a converter.
- **Retorno:** `array` — Parâmetros no formato de requisição.

---
