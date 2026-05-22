# Calculator Investment

**Calculadora de investimentos em renda fixa brasileira (CDB, LCI, LCA)**

Aplicação em PHP (CLI + API REST) que calcula o retorno de investimentos em CDB, LCI e LCA, utilizando taxas oficiais do **Banco Central do Brasil** (CDI/Selic) via API SGS (Sistema Gerenciador de Séries Temporais). Realiza cálculos de juros compostos diários em dias úteis, aplica tributação regressiva de IR e IOF conforme a legislação brasileira, e gera relatórios detalhados de simulação.

---

## Índice

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
- [Catálogo de Métodos](#catálogo-de-métodos)

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
php index.php --tipo=1 --taxa=2 --aplicacao=2026-01-01 --meses=6 --capital=10000 --percentual=110 --selic=14.25
```

### API REST (modo HTTP)

Execute com o servidor embutido do PHP:

```bash
php -S localhost:8000
```

#### Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/calculate?investment_type=cdb&rate_type=pos&capital=10000&cdi=110&application_date=2026-01-01&months=6` | Calcula investimento via query string |
| `POST` | `/api/calculate` | Calcula investimento via body JSON |
| `PUT` | `/api/calculate/{id}` | Recalcula substituindo registro |
| `DELETE` | `/api/calculate/{id}` | Remove registro |

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

### Opções CLI / Parâmetros da API

| Parâmetro | Descrição | Valores |
|-----------|-----------|---------|
| `investment_type` | Tipo de investimento | `cdb`, `lci`, `lca` |
| `rate_type` | Tipo de taxa | `pre` (pré-fixado), `pos` (pós-fixado) |
| `application_date` | Data de aplicação | `YYYY-MM-DD` |
| `months` | Prazo em meses | Número inteiro positivo |
| `capital` | Capital inicial | Número decimal positivo |
| `cdi` | Percentual do CDI (pós) | Ex: `110` = 110% do CDI |
| `pre_rate` | Taxa pré-fixada anual (pré) | Ex: `11.50` = 11,50% ao ano |
| `selic_meta` | Taxa Selic Meta | Ex: `14.40` |
| `cdi_annual` | Taxa CDI anual manual (opcional) | Ex: `13.65` |

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
  +--> GET|POST /api/calculate
  |      +--> ApiController::calculate()
  |             +--> HttpInputFactory::create()  (converte JSON em InvestmentInput)
  |             |      +--> CdiRateService::fetchCdiAnnual()
  |             +--> CalculateInvestmentUseCase
  |             +--> InMemoryInvestmentRepository
  |
  +--> PUT /api/calculate/{id}
  |      +--> ApiController::update()
  |             +--> HttpInputFactory::create()
  |             +--> CalculateInvestmentUseCase
  |             +--> InMemoryInvestmentRepository
  |
  +--> DELETE /api/calculate/{id}
         +--> ApiController::destroy()
                +--> InMemoryInvestmentRepository
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
| 3 | 90% | 4 | 87% |
| 5 | 84% | 6 | 81% |
| 7 | 78% | 8 | 75% |
| 9 | 72% | 10 | 69% |
| 11 | 66% | 12 | 63% |
| 13 | 60% | 14 | 57% |
| 15 | 54% | 16 | 51% |
| 17 | 48% | 18 | 45% |
| 19 | 42% | 20 | 39% |
| 21 | 36% | 22 | 33% |
| 23 | 30% | 24 | 27% |
| 25 | 24% | 26 | 21% |
| 27 | 18% | 28 | 15% |
| 29 | 12% | 30 | 9% |

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
- **Arquitetura:** Container DI, camada de serviços, presenters, repositories
- **Banco de dados:** Nenhum (repositório em memória)
- **Modos de execução:** CLI interativo + API REST (auto-detectado via `PHP_SAPI`)

---

## Fontes de Dados (Banco Central do Brasil)

### API SGS (Sistema Gerenciador de Séries Temporais)

| Série | Endpoint | Descrição | Uso |
|-------|----------|-----------|-----|
| **12** | `https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados/ultimos/5?formato=json` | CDI diário (% ao dia) | Fonte primária. Validado como $$0 < valor < 1$$ (percentual diário) e anualizado via $$(1 + taxaDiaria/100)^{252} - 1$$ |
| **4390** | `https://api.bcb.gov.br/dados/serie/bcdata.sgs.4390/dados/ultimos/5?formato=json` | CDI mensal/acumulado | Fallback. Se $$valor > 5$$, tratado como taxa anual; senão, mensal → anualizado via $$(1 + valor/100)^{12} - 1$$ |

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
├── bootstrap.php                          # Autoload, Container, AppServiceProvider
├── index.php                              # Entry point (CLI + HTTP)
├── composer.json
├── htaccess                               # Apache rewrite rules (PHP built-in server)
├── nginx.conf.example                     # Exemplo de configuracao Nginx
│
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
│   │   └── InvestmentRepositoryInterface.php
│   ├── Controllers/
│   │   ├── CliController.php              # Controller CLI
│   │   ├── CalculateController.php        # Logica de calculo (CLI)
│   │   ├── ApiController.php              # Controller da API REST
│   │   └── InvestmentResultController.php # Exibicao de resultado (CLI)
│   ├── Core/
│   │   ├── Container.php                  # Container de injecao de dependencia
│   │   └── AppServiceProvider.php         # Registro de todos os servicos
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
│   │   └── InMemoryInvestmentRepository.php
│   ├── Services/
│   │   ├── ServiceBase.php                # Constantes: escala, precisao, tabela IOF, feriados
│   │   ├── AmountFormatterService.php     # Formatacao de valores monetarios
│   │   ├── BusinessDayService.php         # Contagem de dias uteis (feriados brasileiros)
│   │   ├── CdiApiClient.php              # Cliente HTTP cURL para API do BCB
│   │   ├── CdiRateCalculator.php         # Validacao e anualizacao de taxas CDI
│   │   ├── CdiRateService.php            # Obtencao da taxa CDI do BCB com fallback
│   │   ├── DailyReportService.php        # Relatorio diario de simulacao IOF
│   │   ├── InvestmentService.php         # Servico central de calculo do investimento
│   │   ├── ProfitCalculationService.php  # Calculo de lucros
│   │   ├── RateCalculationService.php    # Operacoes matematicas de taxas
│   │   └── TaxCalculationService.php     # Calculo de IR e IOF
│   ├── UseCases/
│   │   └── CalculateInvestmentUseCase.php
│   └── ValueObjects/
│       ├── InvestmentInput.php           # Dados de entrada do investimento
│       └── Investment.php                # Resultado do investimento
│
└── vendor/                                # Composer dependencies
```

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

Provedor que registra todos os serviços no Container.

#### `register(Container $container): void`
- **Descrição:** Registra todos os serviços do sistema como singletons: `AmountFormatterService`, `BusinessDayService`, `CdiRateService`, `InvestmentInputFactory`, `InvestmentCalculation`, `RateCalculationService`, `TaxCalculationService`, `ProfitCalculationService`, `InvestmentService`, `CalculateInvestmentUseCase`, `DailyReportService`, `CalculateController`, `InvestmentPresenter`, `InvestmentResultController`, `HttpInputFactory`, `ApiController`, `HttpApplication`, `CliApplication`, `CliController`.
- **Parâmetros:**
  - `$container` (`Container`) — Instância do Container de DI.
- **Retorno:** `void`

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
  - `$selicMeta` (`string`) — Taxa Selic Meta em percentual (ex: `"14.40"`).
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
  - `$selicMetaFallback` (`string`) — Taxa Selic Meta para fallback (ex: `"14.40"`).
  - `$spreadFallback` (`string`, default `'-0.10'`) — Spread para fallback.
- **Retorno:** `array` — `['rate' => string, 'source' => string]`, onde `source` é `'bcb_daily'`, `'bcb_monthly'` ou `'fallback'`.

#### `fallback(string $selicMeta, string $spread, string $reason): array`
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

### App\Services\InvestmentService

Serviço central que orquestra todo o cálculo do investimento.

#### `__construct(CalculatesRateInterface, CalculatesTaxInterface, CalculatesProfitInterface, CountsBusinessDaysInterface, FormatsAmountInterface, InvestmentCalculation, InvestmentRepositoryInterface)`

#### `calculate(InvestmentInput $input): Investment`
- **Descrição:** Ponto de entrada principal. Delega para `handle()`.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada do investimento.
- **Retorno:** `Investment`

#### `handle(InvestmentInput $input): Investment`
- **Descrição:** Executa `process()` e salva o resultado no repositório.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
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
  ==========================================
    Simulacao diaria (periodo de cobranca do IOF)
  ==========================================
  Data         Dias DU   %Mes Bruto(R$)  Lucro(R$) IOF(R$) Liquido(R$)
  05-01-2026     0   0   0%  10.000,00      0,00     0,00     0,00
  06-01-2026     1   1   3%  10.005,55      5,55     5,33     5,55
  07-01-2026     2   2   7%  10.011,11     11,11    10,33    11,11
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
- **Descrição:** Executa o caso de uso de cálculo de investimento delegando ao `InvestmentService::handle()`.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
- **Retorno:** `Investment`

---

### App\Repositories\InMemoryInvestmentRepository

#### `save(InvestmentInput $input, Investment $result): Investment`
- **Descrição:** Armazena o resultado do investimento em memória.
- **Parâmetros:**
  - `$input` (`InvestmentInput`) — Dados de entrada.
  - `$result` (`Investment`) — Resultado.
- **Retorno:** `Investment`

#### `all(): array`
- **Descrição:** Retorna todos os resultados armazenados.
- **Parâmetros:** Nenhum.
- **Retorno:** `array`

---

### App\ValueObjects\InvestmentInput

Value Object imutável com dados de entrada do investimento.

**Propriedades (públicas, readonly):**

| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| `$initialCapital` | `string` | Capital inicial investido (ex: `"10000.00"`) |
| `$investmentType` | `string` | Tipo: `"cdb"`, `"lci"` ou `"lca"` |
| `$rateType` | `string` | Tipo de taxa: `"pre"` ou `"pos"` |
| `$cdiPercentage` | `string` | Percentual do CDI contratado (ex: `"110"`) |
| `$selicMeta` | `string` | Taxa Selic Meta anual (ex: `"14.40"`) |
| `$preFixedAnnualRate` | `string` | Taxa anual pré-fixada (ex: `"11.50"`) |
| `$applicationDate` | `string` | Data de aplicação (`Y-m-d`) |
| `$redemptionDate` | `string` | Data de resgate (`Y-m-d`) |
| `$months` | `int` | Prazo em meses |
| `$selicIsOver` | `bool` | Se a Selic fornecida já é "Over" |
| `$cdiOver` | `string` | Taxa CDI Over fornecida manualmente (vazio = buscar da API do BCB) |

**Propriedade computada:**

| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| `$isIsento` | `bool` | `true` se `investmentType` for `lci` ou `lca` (isentos de IR); `false` para `cdb` |

---

### App\ValueObjects\Investment

Value Object imutável com o resultado completo do investimento.

**Propriedades (públicas, readonly):**

| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| `$amountBruto` | `string` | Montante bruto final |
| `$amountLiquid` | `string` | Montante líquido final (após IR/IOF) |
| `$profitBruto` | `string` | Lucro bruto |
| `$profitLiquid` | `string` | Lucro líquido (após IR/IOF) |
| `$iofValue` | `string` | IOF pago |
| `$irTaxAmount` | `string` | IR pago |
| `$monthlyProfitLiquid` | `string` | Lucro líquido mensal |
| `$dailyProfitDisplay` | `string` | Lucro líquido diário (para exibição) |
| `$isIsento` | `bool` | Se o investimento é isento de IR |
| `$days` | `int` | Dias corridos |
| `$businessDays` | `int` | Dias úteis |

---

### App\Application\HttpApplication

Roteador da API REST. Detecta método HTTP e path, direciona para o `ApiController`.

#### `handle(): void`
- **Descrição:** Lê `REQUEST_METHOD` e `REQUEST_URI`, faz o roteamento:
  - `GET|POST /api/calculate` → `ApiController::calculate()`
  - `PUT /api/calculate/{id}` → `ApiController::update()`
  - `DELETE /api/calculate/{id}` → `ApiController::destroy()`
  - Outras rotas → `404` com lista de rotas disponíveis.

#### `resolveParams(string $method): array`
- **Descrição:** Extrai parâmetros da requisição. Para `GET`, lê `$_GET`. Para `POST`/`PUT`, lê `php://input` como JSON (se `Content-Type: application/json`) ou `$_POST` (form-urlencoded).
- **Parâmetros:**
  - `$method` (`string`) — Método HTTP.
- **Retorno:** `array` — Parâmetros mesclados.

---

### App\Controllers\ApiController

Controller da API REST com respostas JSON.

#### `calculate(array $params): void`
- **Descrição:** Cria `InvestmentInput` via `HttpInputFactory`, executa o cálculo, persiste no repositório e retorna `201` com o payload completo.
- **Parâmetros:**
  - `$params` (`array`) — Parâmetros da requisição.

#### `update(string $id, array $params): void`
- **Descrição:** Busca registro por `id`, mescla parâmetros novos, recalcula e substitui o registro. Retorna `200` com `id` e `replaced_id`.
- **Parâmetros:**
  - `$id` (`string`) — ID do registro.
  - `$params` (`array`) — Novos parâmetros.

#### `destroy(string $id): void`
- **Descrição:** Remove registro do repositório. Retorna `200` se removido, `404` se não encontrado.
- **Parâmetros:**
  - `$id` (`string`) — ID do registro.

---

### App\Factories\HttpInputFactory

Factory que cria `InvestmentInput` a partir de parâmetros de requisição HTTP (JSON ou form).

#### `__construct(CdiRateService $cdiRateService)`

#### `create(array $params): InvestmentInput`
- **Descrição:** Extrai e valida os parâmetros: `investment_type`, `rate_type`, `application_date`, `months`, `capital`, `cdi`, `selic_meta`, `pre_rate`, `cdi_annual`. Para investimentos pós-fixados, obtém a taxa CDI via API do BCB.
- **Parâmetros:**
  - `$params` (`array`) — Parâmetros da requisição.
- **Retorno:** `InvestmentInput`

---
