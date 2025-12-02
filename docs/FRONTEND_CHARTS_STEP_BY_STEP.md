## Integração Frontend + Gráficos (Passo a Passo)

Este guia mostra como consumir os endpoints de analytics do backend (`/api/analytics/*`) e transformar as respostas em gráficos usando React + **Recharts**. Adapte conforme seu stack.

### 1. Preparação do Projeto
- Adicione dependências de gráficos:
  ```bash
  npm install recharts @tanstack/react-query axios dayjs
  ```
- Configure um cliente HTTP (`api.ts`) com `axios` definindo `baseURL` apontando para o backend.
- Configure o React Query Provider (ou use SWR/fetch manualmente) para cache/estado de requisições.

**Nota sobre Recharts**: O Recharts não requer um Provider global, você pode usar os componentes diretamente. Apenas certifique-se de importar os componentes necessários de `'recharts'`.

```ts
// src/lib/api.ts
import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  timeout: 15000,
});
```

### 2. Criar um Hook Genérico de Fetch
```ts
// src/hooks/useApi.ts
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export function useApi<T>(key: unknown[], url: string, params?: Record<string, unknown>) {
  return useQuery<T>({
    queryKey: [...key, params],
    queryFn: async () => (await api.get<T>(url, { params })).data,
  });
}
```

### 3. Carregar Ingredientes para Filtros
- Endpoint: `GET /api/ingredients`
- Use para popular selects/autocomplete antes de renderizar gráficos dependentes de ingrediente (ex: price trends).

### 4. Gráfico 1 – Matriz Popularidade x Rentabilidade
- Endpoint: `GET /api/analytics/menu-matrix?start=YYYY-MM-DD&end=YYYY-MM-DD`
- Retorno: `thresholds` + `items[] { name, qty, profit_per_dish, category }`
- Passos:
  1. Criar filtros de período (date pickers).
  2. Chamar hook `useApi<MenuMatrixResponse>(['menu-matrix'], '/analytics/menu-matrix', filters)`
  3. Renderizar Scatter Chart (x=qty, y=profit_per_dish, cor por `category`).

```tsx
import { ScatterChart, Scatter, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell } from 'recharts';

// Função auxiliar para cores por categoria
const getCategoryColor = (category: number) => {
  const colors = {
    1: '#22c55e', // Popular + Rentável (verde)
    2: '#f59e0b', // Popular mas não rentável (laranja)
    3: '#3b82f6', // Não popular mas rentável (azul)
    4: '#ef4444', // Nem popular nem rentável (vermelho)
  };
  return colors[category] || '#94a3b8';
};

// Componente do gráfico
function MenuMatrixChart() {
  const [start, setStart] = useState(dayjs().subtract(30, 'days').format('YYYY-MM-DD'));
  const [end, setEnd] = useState(dayjs().format('YYYY-MM-DD'));
  
  const { data, isLoading } = useApi<MenuMatrixResponse>(
    ['menu-matrix', start, end],
    '/analytics/menu-matrix',
    { start, end }
  );

  if (isLoading) return <div>Carregando...</div>;
  if (!data) return null;

  // Preparar dados para o Recharts
  const scatterData = data.items.map(item => ({
    name: item.name,
    qty: item.qty,
    profit: item.profit_per_dish,
    revenue: item.revenue,
    category: item.category,
  }));

  return (
    <ResponsiveContainer width="100%" height={500}>
      <ScatterChart
        margin={{ top: 20, right: 20, bottom: 20, left: 20 }}
      >
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis 
          type="number" 
          dataKey="qty" 
          name="Quantidade Vendida"
          label={{ value: 'Quantidade Vendida', position: 'insideBottom', offset: -5 }}
        />
        <YAxis 
          type="number" 
          dataKey="profit" 
          name="Lucro por Prato"
          label={{ value: 'Lucro por Prato (R$)', angle: -90, position: 'insideLeft' }}
        />
        <Tooltip 
          cursor={{ strokeDasharray: '3 3' }}
          content={({ active, payload }) => {
            if (active && payload && payload[0]) {
              const data = payload[0].payload;
              return (
                <div style={{ 
                  backgroundColor: 'white', 
                  padding: '10px', 
                  border: '1px solid #ccc',
                  borderRadius: '4px'
                }}>
                  <p><strong>{data.name}</strong></p>
                  <p>Quantidade: {data.qty}</p>
                  <p>Lucro/Prato: R$ {data.profit.toFixed(2)}</p>
                  <p>Receita Total: R$ {data.revenue.toFixed(2)}</p>
                </div>
              );
            }
            return null;
          }}
        />
        <Legend />
        <Scatter 
          name="Pratos" 
          data={scatterData} 
          fill="#8884d8"
        >
          {scatterData.map((entry, index) => (
            <Cell key={`cell-${index}`} fill={getCategoryColor(entry.category)} />
          ))}
        </Scatter>
      </ScatterChart>
    </ResponsiveContainer>
  );
}
```

### 5. Gráfico 2 – Alertas de Perecíveis
- Endpoint: `GET /api/analytics/perishables-alerts?hours=48`
- Ideal para cards/listas, mas também dá para um **bar chart** (quantidade vs expiração).
  1. Crie filtro `hours`.
  2. Use `useApi<PerishableAlert[]>(['perishables', hours], '/analytics/perishables-alerts', { hours })`.
  3. Renderize tabela e barra empilhada (quantidade disponível x uso previsto).

```tsx
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

function PerishablesAlertsChart() {
  const [hours, setHours] = useState(48);
  
  const { data, isLoading } = useApi<PerishableAlert[]>(
    ['perishables', hours],
    '/analytics/perishables-alerts',
    { hours }
  );

  if (isLoading) return <div>Carregando...</div>;
  if (!data || data.length === 0) return <div>Nenhum alerta no período</div>;

  // Preparar dados para o gráfico
  const chartData = data.map(alert => ({
    name: alert.ingredient,
    quantidade: alert.quantity,
    usoPrevisto: alert.forecast_use_until_expiry,
    expiraEm: dayjs(alert.expires_at).format('DD/MM/YYYY'),
  }));

  return (
    <ResponsiveContainer width="100%" height={400}>
      <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 60 }}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis 
          dataKey="name" 
          angle={-45} 
          textAnchor="end" 
          height={100}
        />
        <YAxis />
        <Tooltip />
        <Legend />
        <Bar dataKey="quantidade" fill="#3b82f6" name="Quantidade Disponível" />
        <Bar dataKey="usoPrevisto" fill="#ef4444" name="Uso Previsto até Expiração" />
      </BarChart>
    </ResponsiveContainer>
  );
}
```

### 6. Gráfico 3 – Tendência de Preço por Ingrediente/Fornecedor
- Endpoint: `GET /api/analytics/price-trends?ingredient_id=ID`
- Retorna lista ordenada por `supplier` + `valid_from`.
  1. Dropdown para escolher ingrediente.
  2. Agrupe dados por fornecedor → datasets múltiplos.
  3. Renderize `LineChart` com eixo X = `valid_from`, Y = `price`.

```tsx
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface PriceTrend {
  id: number;
  ingredient_id: number;
  supplier_id: number;
  price: string;
  valid_from: string;
  supplier: {
    id: number;
    name: string;
  };
}

function PriceTrendsChart() {
  const [ingredientId, setIngredientId] = useState<number | null>(null);
  const { data: ingredients } = useApi<Ingredient[]>(['ingredients'], '/ingredients');
  
  const { data: prices, isLoading } = useApi<PriceTrend[]>(
    ['price-trends', ingredientId],
    '/analytics/price-trends',
    ingredientId ? { ingredient_id: ingredientId } : undefined,
    { enabled: !!ingredientId }
  );

  if (isLoading) return <div>Carregando...</div>;
  if (!prices || prices.length === 0) return <div>Selecione um ingrediente</div>;

  // Agrupar por fornecedor
  const groupedBySupplier = prices.reduce((acc, price) => {
    const supplierName = price.supplier.name;
    if (!acc[supplierName]) {
      acc[supplierName] = [];
    }
    acc[supplierName].push({
      date: dayjs(price.valid_from).format('DD/MM/YYYY'),
      price: parseFloat(price.price),
      valid_from: price.valid_from,
    });
    return acc;
  }, {} as Record<string, Array<{ date: string; price: number; valid_from: string }>>);

  // Obter todas as datas únicas e ordenadas
  const allDates = Array.from(
    new Set(prices.map(p => dayjs(p.valid_from).format('DD/MM/YYYY')))
  ).sort((a, b) => dayjs(a, 'DD/MM/YYYY').diff(dayjs(b, 'DD/MM/YYYY')));

  // Preparar dados para o Recharts (formato de array de objetos)
  const chartData = allDates.map(date => {
    const dataPoint: Record<string, string | number> = { date };
    Object.keys(groupedBySupplier).forEach(supplier => {
      const point = groupedBySupplier[supplier].find(p => p.date === date);
      dataPoint[supplier] = point ? point.price : null;
    });
    return dataPoint;
  });

  const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

  return (
    <div>
      <select 
        value={ingredientId || ''} 
        onChange={(e) => setIngredientId(Number(e.target.value) || null)}
        style={{ marginBottom: '20px', padding: '8px' }}
      >
        <option value="">Selecione um ingrediente</option>
        {ingredients?.data.map(ing => (
          <option key={ing.id} value={ing.id}>{ing.name}</option>
        ))}
      </select>

      <ResponsiveContainer width="100%" height={400}>
        <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="date" 
            angle={-45} 
            textAnchor="end" 
            height={100}
          />
          <YAxis 
            label={{ value: 'Preço (R$)', angle: -90, position: 'insideLeft' }}
          />
          <Tooltip />
          <Legend />
          {Object.keys(groupedBySupplier).map((supplier, idx) => (
            <Line
              key={supplier}
              type="monotone"
              dataKey={supplier}
              stroke={colors[idx % colors.length]}
              strokeWidth={2}
              dot={{ r: 4 }}
              connectNulls
            />
          ))}
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
```

### 7. Gráfico 4 – Fluxo de Tráfego (Heatmap)
- Endpoint: `GET /api/analytics/traffic-flow?start&end`
- Campos: `weekday (0-6)`, `hour (00-23)`, `revenue`, `sales`.
  1. Normalize `weekday` para nomes (`domingo...sábado`).
  2. Renderize heatmap usando células customizadas com Recharts.
  3. Cada célula representa receita/sales naquele horário.

```tsx
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell } from 'recharts';

interface TrafficFlowData {
  weekday: string;
  hour: string;
  revenue: number;
  sales: number;
}

function TrafficFlowHeatmap() {
  const [start, setStart] = useState(dayjs().subtract(30, 'days').format('YYYY-MM-DD'));
  const [end, setEnd] = useState(dayjs().format('YYYY-MM-DD'));
  
  const { data, isLoading } = useApi<TrafficFlowData[]>(
    ['traffic-flow', start, end],
    '/analytics/traffic-flow',
    { start, end }
  );

  if (isLoading) return <div>Carregando...</div>;
  if (!data) return null;

  const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));

  // Criar matriz de dados
  const heatmapData = weekdays.map((day, dayIdx) => {
    const dayData: Record<string, number | string> = { day };
    hours.forEach(hour => {
      const entry = data.find(d => d.weekday === dayIdx.toString() && d.hour === hour);
      dayData[hour] = entry ? parseFloat(entry.revenue.toString()) : 0;
    });
    return dayData;
  });

  // Calcular valores máximos para normalizar cores
  const maxRevenue = Math.max(...data.map(d => parseFloat(d.revenue.toString())));

  // Função para obter cor baseada no valor
  const getColor = (value: number) => {
    const intensity = value / maxRevenue;
    if (intensity === 0) return '#f3f4f6';
    if (intensity < 0.25) return '#dbeafe';
    if (intensity < 0.5) return '#93c5fd';
    if (intensity < 0.75) return '#3b82f6';
    return '#1e40af';
  };

  return (
    <div>
      <ResponsiveContainer width="100%" height={500}>
        <BarChart
          data={heatmapData}
          layout="vertical"
          margin={{ top: 20, right: 30, left: 80, bottom: 20 }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis type="number" domain={[0, 24]} />
          <YAxis dataKey="day" type="category" />
          <Tooltip 
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                const hour = payload[0].dataKey;
                const revenue = payload[0].value;
                return (
                  <div style={{ 
                    backgroundColor: 'white', 
                    padding: '10px', 
                    border: '1px solid #ccc',
                    borderRadius: '4px'
                  }}>
                    <p><strong>{hour}:00</strong></p>
                    <p>Receita: R$ {Number(revenue).toFixed(2)}</p>
                  </div>
                );
              }
              return null;
            }}
          />
          {hours.map((hour, idx) => (
            <Bar key={hour} dataKey={hour} stackId="a" fill={getColor(0)}>
              {heatmapData.map((entry, entryIdx) => {
                const value = entry[hour] as number;
                return <Cell key={`cell-${entryIdx}`} fill={getColor(value)} />;
              })}
            </Bar>
          ))}
        </BarChart>
      </ResponsiveContainer>
      
      {/* Legenda de cores */}
      <div style={{ marginTop: '20px', display: 'flex', alignItems: 'center', gap: '10px' }}>
        <span>Menor</span>
        <div style={{ display: 'flex', gap: '2px' }}>
          {[0, 0.25, 0.5, 0.75, 1].map(intensity => (
            <div
              key={intensity}
              style={{
                width: '30px',
                height: '20px',
                backgroundColor: getColor(intensity * maxRevenue),
                border: '1px solid #ccc'
              }}
            />
          ))}
        </div>
        <span>Maior</span>
      </div>
    </div>
  );
}
```

### 8. Gráfico 5 – Ponto de Equilíbrio Diário
- Endpoint: `GET /api/analytics/breakeven?date=YYYY-MM-DD&fixed_cost=2000`
- Retorno: `breakeven`, `revenue`, `gap`.
  1. Controle de data + input de custo fixo.
  2. Renderize gauge/donut comparando receita vs meta ou gráfico de barras.
  3. Destaque `gap` positivo/negativo.

```tsx
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

interface BreakevenData {
  date: string;
  breakeven: number;
  revenue: number;
  gap: number;
}

function BreakevenChart() {
  const [date, setDate] = useState(dayjs().format('YYYY-MM-DD'));
  const [fixedCost, setFixedCost] = useState(2000);
  
  const { data, isLoading } = useApi<BreakevenData>(
    ['breakeven', date, fixedCost],
    '/analytics/breakeven',
    { date, fixed_cost: fixedCost }
  );

  if (isLoading) return <div>Carregando...</div>;
  if (!data) return null;

  // Dados para gráfico de barras
  const barData = [
    {
      name: 'Meta (Breakeven)',
      value: data.breakeven,
      fill: '#ef4444',
    },
    {
      name: 'Receita Real',
      value: data.revenue,
      fill: data.gap >= 0 ? '#22c55e' : '#f59e0b',
    },
  ];

  // Dados para gráfico de pizza (percentual)
  const pieData = [
    {
      name: 'Receita',
      value: Math.max(0, data.revenue),
      fill: data.gap >= 0 ? '#22c55e' : '#f59e0b',
    },
    {
      name: 'Faltando',
      value: Math.max(0, -data.gap),
      fill: '#ef4444',
    },
  ].filter(item => item.value > 0);

  const percentage = data.breakeven > 0 
    ? ((data.revenue / data.breakeven) * 100).toFixed(1)
    : 0;

  return (
    <div>
      <div style={{ marginBottom: '20px', display: 'flex', gap: '20px', alignItems: 'center' }}>
        <label>
          Data:
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            style={{ marginLeft: '8px', padding: '4px' }}
          />
        </label>
        <label>
          Custo Fixo (R$):
          <input
            type="number"
            value={fixedCost}
            onChange={(e) => setFixedCost(Number(e.target.value))}
            style={{ marginLeft: '8px', padding: '4px', width: '100px' }}
          />
        </label>
      </div>

      {/* Card de resumo */}
      <div style={{ 
        display: 'flex', 
        gap: '20px', 
        marginBottom: '30px',
        padding: '20px',
        backgroundColor: '#f9fafb',
        borderRadius: '8px'
      }}>
        <div>
          <h3 style={{ margin: 0, fontSize: '14px', color: '#6b7280' }}>Meta (Breakeven)</h3>
          <p style={{ margin: '8px 0 0 0', fontSize: '24px', fontWeight: 'bold' }}>
            R$ {data.breakeven.toFixed(2)}
          </p>
        </div>
        <div>
          <h3 style={{ margin: 0, fontSize: '14px', color: '#6b7280' }}>Receita Real</h3>
          <p style={{ margin: '8px 0 0 0', fontSize: '24px', fontWeight: 'bold', color: data.gap >= 0 ? '#22c55e' : '#ef4444' }}>
            R$ {data.revenue.toFixed(2)}
          </p>
        </div>
        <div>
          <h3 style={{ margin: 0, fontSize: '14px', color: '#6b7280' }}>Gap</h3>
          <p style={{ margin: '8px 0 0 0', fontSize: '24px', fontWeight: 'bold', color: data.gap >= 0 ? '#22c55e' : '#ef4444' }}>
            {data.gap >= 0 ? '+' : ''}R$ {data.gap.toFixed(2)}
          </p>
        </div>
        <div>
          <h3 style={{ margin: 0, fontSize: '14px', color: '#6b7280' }}>Percentual</h3>
          <p style={{ margin: '8px 0 0 0', fontSize: '24px', fontWeight: 'bold' }}>
            {percentage}%
          </p>
        </div>
      </div>

      {/* Gráfico de barras */}
      <div style={{ marginBottom: '30px' }}>
        <h3>Comparação: Meta vs Receita</h3>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={barData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis 
              label={{ value: 'Valor (R$)', angle: -90, position: 'insideLeft' }}
            />
            <Tooltip 
              formatter={(value: number) => `R$ ${value.toFixed(2)}`}
            />
            <Bar dataKey="value" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Gráfico de pizza */}
      {pieData.length > 0 && (
        <div>
          <h3>Distribuição</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={pieData}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                outerRadius={100}
                fill="#8884d8"
                dataKey="value"
              >
                {pieData.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.fill} />
                ))}
              </Pie>
              <Tooltip formatter={(value: number) => `R$ ${value.toFixed(2)}`} />
            </PieChart>
          </ResponsiveContainer>
        </div>
      )}
    </div>
  );
}
```

### 9. Organização das Páginas
Sugestão de páginas/sections:
1. **Dashboard Resumo** – Cards (breakeven, perecíveis, top pratos).
2. **Menu Engineering** – Matriz + tabela detalhada.
3. **Compras & Custos** – Tendência de preços.
4. **Operação** – Heatmap de tráfego.

### 10. Melhorias de UX
- Skeleton loaders para gráficos.
- Empty states (“Sem dados no período”).
- Download CSV (use `data.items` diretamente).
- Compartilhar filtros entre gráficos via Zustand/Redux ou contexto.

### 11. Fluxo Geral de Desenvolvimento
1. Configurar .env do frontend com `VITE_API_URL`.
2. Criar camada de serviços (`services/analytics.ts`) encapsulando cada endpoint.
3. Criar hooks específicos: `useMenuMatrix`, `usePriceTrends`, etc.
4. Criar componentes de filtro reutilizáveis (date range, select ingrediente).
5. Montar gráficos com **Recharts** (componentes declarativos).
6. Adicionar testes (ex: Jest + MSW simulando API).

### 12. Dicas de Uso do Recharts

#### Responsividade
- Sempre use `ResponsiveContainer` para gráficos responsivos
- Defina `width="100%"` e `height` fixo ou calculado

#### Customização de Cores
```tsx
const COLORS = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

// Use Cell para customizar cores individuais
<Bar dataKey="value">
  {data.map((entry, index) => (
    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
  ))}
</Bar>
```

#### Tooltips Customizados
```tsx
<Tooltip 
  content={({ active, payload }) => {
    if (active && payload && payload[0]) {
      return (
        <div style={{ backgroundColor: 'white', padding: '10px', border: '1px solid #ccc' }}>
          <p><strong>{payload[0].name}</strong></p>
          <p>Valor: R$ {payload[0].value?.toFixed(2)}</p>
        </div>
      );
    }
    return null;
  }}
/>
```

#### Formatação de Valores
```tsx
// Formatar eixos
<YAxis 
  tickFormatter={(value) => `R$ ${value.toFixed(2)}`}
/>

// Formatar tooltip
<Tooltip 
  formatter={(value: number) => `R$ ${value.toFixed(2)}`}
/>
```

### 13. Referência Rápida de Endpoints
| Feature                | Método | Endpoint                            | Params            |
|-----------------------|--------|-------------------------------------|-------------------|
| Menu Matrix           | GET    | `/api/analytics/menu-matrix`        | `start`, `end`    |
| Perishables Alerts    | GET    | `/api/analytics/perishables-alerts` | `hours`           |
| Price Trends          | GET    | `/api/analytics/price-trends`       | `ingredient_id`   |
| Traffic Flow Heatmap  | GET    | `/api/analytics/traffic-flow`       | `start`, `end`    |
| Breakeven             | GET    | `/api/analytics/breakeven`          | `date`, `fixed_cost` |

> Dica: os endpoints já retornam os dados normalizados pelo backend, então o frontend só precisa aplicar filtros, mapear para o formato que o Recharts espera (array de objetos) e renderizar os componentes de gráfico.

### 14. Estrutura de Tipos TypeScript (Opcional)

Para melhor tipagem, crie um arquivo `types/analytics.ts`:

```ts
export interface MenuMatrixResponse {
  thresholds: {
    popularity_qty: number;
    profitability_per_dish: number;
  };
  items: Array<{
    dish_id: number;
    name: string;
    qty: number;
    revenue: number;
    cost_per_dish: number;
    profit_per_dish: number;
    profit: number;
    category: 1 | 2 | 3 | 4;
  }>;
}

export interface PerishableAlert {
  ingredient_id: number;
  ingredient: string;
  batch_id: number;
  quantity: number;
  expires_at: string;
  forecast_use_until_expiry: number;
}

export interface PriceTrend {
  id: number;
  ingredient_id: number;
  supplier_id: number;
  price: string;
  valid_from: string;
  supplier: {
    id: number;
    name: string;
  };
}

export interface TrafficFlowData {
  weekday: string;
  hour: string;
  revenue: number;
  sales: number;
}

export interface BreakevenData {
  date: string;
  breakeven: number;
  revenue: number;
  gap: number;
}
```

