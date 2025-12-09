## Passo a Passo: Gráficos do Frontend (Recharts)

Este guia é um roteiro prático, rota por rota, para consumir os endpoints de analytics e renderizar os gráficos no frontend usando **Recharts + React Query + axios**.

### Pré-requisitos
- Dependências: `npm install recharts @tanstack/react-query axios dayjs`
- Cliente HTTP (`src/lib/api.ts`):
  ```ts
  import axios from 'axios';
  export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
    timeout: 15000,
  });
  ```
- Hook genérico (`src/hooks/useApi.ts`):
  ```ts
  import { useQuery } from '@tanstack/react-query';
  import { api } from '@/lib/api';
  export function useApi<T>(key: unknown[], url: string, params?: Record<string, unknown>) {
    return useQuery<T>({ queryKey: [...key, params], queryFn: async () => (await api.get<T>(url, { params })).data });
  }
  ```

---

## 1) Menu Matrix (popularidade x rentabilidade)
- **Endpoint:** `GET /api/analytics/menu-matrix?start=YYYY-MM-DD&end=YYYY-MM-DD`
- **Uso:** Scatter plot; pontos coloridos por categoria (1..4).  
- **Dados-chave:** `items[].{qty, profit_per_dish, revenue, category}`, `thresholds.{popularity_qty, profitability_per_dish}`
- **Passos:**
  1) Filtrar período (`start`, `end`).
  2) Chamar `useApi<MenuMatrixResponse>(['menu-matrix', start, end], '/analytics/menu-matrix', { start, end })`.
  3) Montar `ScatterChart`:
     - `x = qty` (popularidade), `y = profit_per_dish` (rentabilidade).
     - Cores por `category`.
     - (Opcional) `ReferenceLine` em `thresholds.popularity_qty` e `thresholds.profitability_per_dish`.
- **Tooltip:** Mostrar `qty`, `profit_per_dish`, `revenue` e se está acima/abaixo dos thresholds.

## 2) Menu Matrix by Category (agrupado)
- **Endpoint:** `GET /api/analytics/menu-matrix-by-category?start=YYYY-MM-DD&end=YYYY-MM-DD`
- **Uso:** Cards ou stacked bars por categoria, com lista dos pratos de cada grupo.
- **Dados-chave:** `categories[1..4].{items[], total_qty, total_revenue, percentage, color}` e `total_sales`.
- **Passos:**
  1) Chamar `useApi<MenuMatrixCategoryResponse>(['menu-matrix-by-category', start, end], '/analytics/menu-matrix-by-category', { start, end })`.
  2) Exibir cards por categoria:
     - `total_qty`, `percentage` (% do total), `total_revenue`.
  3) (Opcional) Gráfico de barras empilhadas por categoria mostrando `total_qty`.
  4) Tabela/lista dos itens dentro de cada categoria.

## 3) Alertas de Perecíveis
- **Endpoint:** `GET /api/analytics/perishables-alerts?hours=48`
- **Uso:** Tabela + barra (quantidade vs uso previsto até expirar).
- **Dados-chave:** `ingredient`, `quantity`, `forecast_use_until_expiry`, `expires_at`.
- **Passos:**
  1) Filtro `hours` (ex: 24/48/72).
  2) `useApi<PerishableAlert[]>(['perishables', hours], '/analytics/perishables-alerts', { hours })`.
  3) BarChart com `quantidade` x `usoPrevisto`.
  4) Ordenar por `expires_at` e destacar os que expiram primeiro.

## 4) Tendência de Preço por Fornecedor
- **Endpoint:** `GET /api/analytics/price-trends?ingredient_id=ID`
- **Uso:** Line chart com múltiplas linhas (uma por fornecedor).
- **Dados-chave:** `ingredient_id`, `supplier`, `price`, `valid_from`.
- **Passos:**
  1) Dropdown de ingredientes (`GET /api/ingredients`).
  2) `useApi<PriceTrend[]>(['price-trends', ingredientId], '/analytics/price-trends', { ingredient_id: ingredientId })`.
  3) Agrupar por `supplier.name`, montar `LineChart` com X=`valid_from` (formatar data), Y=`price`.
  4) `connectNulls` para lidar com datas sem ponto de um fornecedor.

## 5) Fluxo de Tráfego (Heatmap simples)
- **Endpoint:** `GET /api/analytics/traffic-flow?start=YYYY-MM-DD&end=YYYY-MM-DD`
- **Uso:** Heatmap (ou barras empilhadas) por dia da semana x hora.
- **Dados-chave:** `weekday (0-6)`, `hour (00-23)`, `revenue`, `sales`.
- **Passos:**
  1) Filtros de período.
  2) `useApi<TrafficFlowData[]>(['traffic-flow', start, end], '/analytics/traffic-flow', { start, end })`.
  3) Montar matriz: linhas = weekday (Dom..Sáb), colunas = hour.
  4) Colorir célula pela intensidade de `revenue` (ou `sales`).

## 6) Breakeven Diário
- **Endpoint:** `GET /api/analytics/breakeven?date=YYYY-MM-DD&fixed_cost=2000`
- **Uso:** Card resumo + BarChart (meta vs receita) + Pie (distribuição).
- **Dados-chave:** `breakeven`, `revenue`, `gap`.
- **Passos:**
  1) Inputs: `date` e `fixed_cost`.
  2) `useApi<BreakevenData>(['breakeven', date, fixedCost], '/analytics/breakeven', { date, fixed_cost: fixedCost })`.
  3) Card com valores; BarChart comparando `breakeven` x `revenue`.
  4) Pie para mostrar percentual atingido (se `revenue` < `breakeven`, mostra “faltando”).

---

## Boas práticas gerais
- Sempre usar `ResponsiveContainer` nos gráficos.
- Exibir estados: loading, empty (“Sem dados no período”) e erro (mostrar mensagem vinda da API).
- Formatar datas no frontend (`dayjs`).
- Formatar valores monetários (`Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`).
- Validar filtros antes de chamar a API (ex.: `start <= end`).

## Estrutura mínima sugerida
- `services/analytics.ts` (wrap das chamadas)
- `hooks/useMenuMatrix`, `useMenuMatrixByCategory`, `usePerishablesAlerts`, `usePriceTrends`, `useTrafficFlow`, `useBreakeven`
- `components/analytics/*` para cada gráfico
- `components/filters/*` para selects e date pickers reutilizáveis

## Referência rápida de rotas
- `GET /api/analytics/menu-matrix`
- `GET /api/analytics/menu-matrix-by-category`
- `GET /api/analytics/perishables-alerts`
- `GET /api/analytics/price-trends`
- `GET /api/analytics/traffic-flow`
- `GET /api/analytics/breakeven`

Com isso você tem o passo a passo completo para consumir cada rota e renderizar os gráficos no frontend com Recharts.

