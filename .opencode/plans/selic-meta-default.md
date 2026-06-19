# Plano: Campo `selic_meta_default`

## Objetivo
Adicionar campo `selic_meta_default` na tabela `investments` para registrar o valor padrão da Selic Meta (API BCB) no momento do cadastro, permitindo distinguir do valor customizado pelo usuário.

| Campo | Conteúdo |
|-------|----------|
| `selic_meta` | Valor usado (pode ser customizado: 16.00) |
| `selic_meta_default` | Valor padrão da API no momento (14.25) |

## Arquivos a alterar (10)

### 1. `sql/calculator_investment.sql`
Adicionar coluna após `selic_meta`:
```sql
selic_meta_default DECIMAL(8,2) NOT NULL DEFAULT 0,
```

### 2. `App\ValueObjects\InvestmentInput.php`
Adicionar propriedade:
```php
public readonly string $selicMetaDefault = '',
```
Posicionar entre `$selicMeta` e `$preFixedAnnualRate`.

### 3. `App\Factories\InvestmentInputFactory.php`
Após obter `$defaultSelic`, popular:
```php
$selicMetaDefault = $defaultSelic;
```
No `new InvestmentInput(...)` adicionar:
```php
selicMetaDefault: $selicMetaDefault,
```

### 4. `App\Factories\HttpInputFactory.php`
Após obter `$defaultSelic`, popular:
```php
$selicMetaDefault = $defaultSelic;
```
No `new InvestmentInput(...)` adicionar:
```php
selicMetaDefault: $selicMetaDefault,
```
No `inputToParams()` adicionar:
```php
'selic_meta_default' => $input->selicMetaDefault,
```

### 5. `App\Repositories\CreateInvestmentRepository.php`
**INSERT** - adicionar coluna e parâmetro:
```sql
selic_meta_default
:selic_meta_default
```

**UPDATE** - adicionar:
```sql
selic_meta_default = :selic_meta_default,
```

### 6. `App\Repositories\ListInvestmentRepository.php`
**SELECT** - adicionar:
```sql
i.selic_meta_default,
```

**rowToItem()** - adicionar no InvestmentInput:
```php
selicMetaDefault: (string) $row['selic_meta_default'],
```

### 7. `App\Repositories\ShowInvestmentRepository.php`
**SELECT** - adicionar:
```sql
i.selic_meta_default,
```

**rowToItem()** - adicionar no InvestmentInput:
```php
selicMetaDefault: (string) $row['selic_meta_default'],
```

### 8. `App\Controllers\BaseApiController.php`
No `buildPayload()` adicionar no array `input`:
```php
'selic_meta_default' => $input->selicMetaDefault !== '' ? (float) $input->selicMetaDefault : null,
```

### 9. `App\Services\InvestmentService.php`
No `handle()` (INSERT) e `recalculateUpdate()` (UPDATE) adicionar:
```php
'selic_meta_default' => $input->selicMetaDefault !== '' ? $input->selicMetaDefault : $input->selicMeta,
```

### 10. `App\Factories\HttpInputFactory.php` (inputToParams)
Adicionar:
```php
'selic_meta_default' => $input->selicMetaDefault,
```

## SQL Migration (para banco existente)
```sql
ALTER TABLE investments
ADD COLUMN selic_meta_default DECIMAL(8,2) NOT NULL DEFAULT 0
AFTER selic_meta;

UPDATE investments SET selic_meta_default = selic_meta WHERE selic_meta_default = 0;
```

## Fluxo
```
1. API BCB retorna Selic Meta = 14.25
2. Usuário digita 16.00
3. selic_meta = 16.00 (usado nos cálculos)
4. selic_meta_default = 14.25 (valor da API)
5. Frontend mostra: "Padrão: 14.25 | Informado: 16.00"
```
