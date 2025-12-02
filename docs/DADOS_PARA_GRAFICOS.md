# Dados Necessários para Popular os Gráficos

Este documento explica quais dados são necessários para cada gráfico de analytics e como os seeders os geram.

## 📊 Gráfico 1: Matriz Popularidade x Rentabilidade

**Endpoint**: `GET /api/analytics/menu-matrix`

### Dados Necessários:
- ✅ **Dishes** (pratos) - já existe
- ✅ **Recipes** (receitas) para cada prato - já existe
- ✅ **RecipeItems** (ingredientes das receitas) - já existe
- ✅ **Sales** (vendas) com diferentes quantidades por prato - **melhorado**
- ✅ **Batches** (lotes) com `unit_cost` para calcular custos - já existe

### O que o seeder faz:
- Cria vendas variadas para diferentes pratos (alguns mais vendidos, outros menos)
- Garante que alguns pratos tenham alta popularidade mas baixa rentabilidade
- Garante que alguns pratos tenham baixa popularidade mas alta rentabilidade

---

## 📊 Gráfico 2: Alertas de Perecíveis

**Endpoint**: `GET /api/analytics/perishables-alerts?hours=48`

### Dados Necessários:
- ✅ **Ingredients** com `is_perishable = true` - já existe
- ✅ **Batches** com `expires_at` nas próximas 48 horas - **melhorado**
- ✅ **Sales** para calcular uso médio diário - já existe

### O que o seeder faz:
- Cria batches que expiram em diferentes horários (12-48h)
- Garante que haja batches suficientes para gerar alertas
- Cria vendas históricas para calcular previsão de uso

---

## 📊 Gráfico 3: Tendência de Preço por Fornecedor

**Endpoint**: `GET /api/analytics/price-trends?ingredient_id=X`

### Dados Necessários:
- ✅ **Ingredients** - já existe
- ✅ **Suppliers** (fornecedores) - já existe
- ⚠️ **IngredientPrices** com histórico ao longo do tempo - **melhorado**

### O que o seeder faz:
- Cria múltiplos preços históricos por fornecedor (últimos 2-3 meses)
- Varia os preços ao longo do tempo para mostrar tendências
- Garante que diferentes fornecedores tenham preços diferentes

---

## 📊 Gráfico 4: Fluxo de Tráfego (Heatmap)

**Endpoint**: `GET /api/analytics/traffic-flow?start&end`

### Dados Necessários:
- ⚠️ **Sales** distribuídas em diferentes horários e dias da semana - **melhorado**

### O que o seeder faz:
- Cria vendas com padrões realistas:
  - Mais vendas no almoço (11h-14h)
  - Mais vendas no jantar (18h-21h)
  - Menos vendas de manhã (8h-10h)
  - Menos vendas à noite (22h-23h)
  - Mais vendas nos finais de semana
- Distribui vendas ao longo de 60 dias para ter dados suficientes

---

## 📊 Gráfico 5: Ponto de Equilíbrio Diário

**Endpoint**: `GET /api/analytics/breakeven?date=YYYY-MM-DD&fixed_cost=2000`

### Dados Necessários:
- ✅ **Sales** com `sold_at` na data específica - já existe

### O que o seeder faz:
- Cria vendas para cada dia (incluindo hoje)
- Varia o total de vendas por dia para mostrar diferentes cenários

---

## 🚀 Como Executar os Seeders

```bash
# Limpar banco e recriar
php artisan migrate:fresh

# Executar todos os seeders
php artisan db:seed

# Ou executar seeders específicos
php artisan db:seed --class=SaleSeeder
php artisan db:seed --class=IngredientPriceSeeder
php artisan db:seed --class=BatchSeeder
```

## ✨ Melhorias Implementadas

### SaleSeeder (Vendas)
- ✅ **Padrões de tráfego realistas**: Mais vendas no almoço (11h-14h) e jantar (18h-21h)
- ✅ **Variação por dia da semana**: Mais vendas nos finais de semana, menos na segunda
- ✅ **Pratos populares**: Primeiros 3 pratos têm 60% mais chance de serem vendidos
- ✅ **Distribuição temporal**: Vendas distribuídas ao longo de 60 dias

### IngredientPriceSeeder (Preços)
- ✅ **Histórico de preços**: 6-8 preços históricos por fornecedor/ingrediente
- ✅ **Variação temporal**: Preços variam ao longo de 3 meses
- ✅ **Múltiplos fornecedores**: Cada fornecedor tem preços diferentes
- ✅ **Tendências**: Preços sobem e descem ao longo do tempo

### BatchSeeder (Lotes)
- ✅ **Batches expirando**: Garante 1-2 lotes por ingrediente perecível expirando nas próximas 48h
- ✅ **Distribuição realista**: Alguns lotes já expirados, outros futuros
- ✅ **Ingredientes não perecíveis**: Cria lotes sem data de expiração

## 📝 Resumo dos Seeders

| Seeder | Status | O que faz |
|--------|--------|-----------|
| `IngredientSeeder` | ✅ OK | Cria ingredientes (perecíveis e não perecíveis) |
| `SupplierSeeder` | ✅ OK | Cria fornecedores |
| `IngredientPriceSeeder` | ⚠️ Melhorado | Cria histórico de preços variados |
| `BatchSeeder` | ⚠️ Melhorado | Cria batches com expiração próxima |
| `DishSeeder` | ✅ OK | Cria pratos |
| `RecipeSeeder` | ✅ OK | Cria receitas para pratos |
| `RecipeItemSeeder` | ✅ OK | Cria ingredientes das receitas |
| `SaleSeeder` | ⚠️ Melhorado | Cria vendas com padrões de tráfego realistas |

## 🎯 Dados de Teste Esperados

Após executar os seeders, você deve ter:

- **~8-9 pratos** ativos
- **~20 ingredientes** (alguns perecíveis, outros não)
- **4 fornecedores**
- **~60-80 preços históricos** (múltiplos por ingrediente/fornecedor)
- **~30-50 batches** (alguns expirando nas próximas 48h)
- **~300-900 vendas** distribuídas nos últimos 60 dias
- **~600-2000 itens de venda**

Isso deve ser suficiente para popular todos os gráficos com dados realistas!

