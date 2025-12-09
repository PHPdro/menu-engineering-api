# Guia Completo: Fluxo de Tráfego (Traffic Flow)

Este guia mostra como implementar o gráfico de fluxo de tráfego (heatmap) mostrando vendas por hora e dia da semana usando React + Recharts.

## 📋 Visão Geral

O endpoint `/api/analytics/traffic-flow` retorna dados agregados de vendas agrupados por dia da semana e hora do dia, permitindo visualizar padrões de movimento ao longo da semana.

### Endpoint
```
GET /api/analytics/traffic-flow?start=YYYY-MM-DD&end=YYYY-MM-DD
```

### Parâmetros
- `start` (opcional): Data inicial (padrão: 30 dias atrás)
- `end` (opcional): Data final (padrão: hoje)

### Resposta
Array de objetos `TrafficFlowData`:
```typescript
interface TrafficFlowData {
  weekday: string;  // 0-6 (0 = domingo, 6 = sábado)
  hour: string;     // 00-23 (formato 24h)
  revenue: number;  // Receita total no período
  sales: number;    // Quantidade de vendas
}
```

**Exemplo de resposta:**
```json
[
  { "weekday": "0", "hour": "12", "revenue": 1500.50, "sales": 25 },
  { "weekday": "0", "hour": "13", "revenue": 1800.75, "sales": 30 },
  { "weekday": "1", "hour": "12", "revenue": 1200.00, "sales": 20 }
]
```

---

## 🚀 Passo a Passo Completo

### Passo 1: Configuração Inicial

Certifique-se de ter as dependências instaladas:

```bash
npm install recharts @tanstack/react-query axios dayjs
```

### Passo 2: Configurar API Client

Crie o arquivo `src/lib/api.ts` (se ainda não existir):

```typescript
import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
  },
});
```

### Passo 3: Criar Hook Genérico

Crie o arquivo `src/hooks/useApi.ts` (se ainda não existir):

```typescript
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export function useApi<T>(
  key: unknown[],
  url: string,
  params?: Record<string, unknown>,
  options?: { enabled?: boolean }
) {
  return useQuery<T>({
    queryKey: [...key, params],
    queryFn: async () => {
      const response = await api.get<T>(url, { params });
      return response.data;
    },
    enabled: options?.enabled !== false,
  });
}
```

### Passo 4: Definir Tipos TypeScript

Adicione ao arquivo `src/types/analytics.ts`:

```typescript
export interface TrafficFlowData {
  weekday: string;  // 0-6
  hour: string;     // 00-23
  revenue: number;
  sales: number;
}
```

### Passo 5: Componente Completo do Gráfico (Heatmap)

Crie o arquivo `src/components/TrafficFlowHeatmap.tsx`:

```tsx
import { useState } from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  Cell,
} from 'recharts';
import dayjs from 'dayjs';
import { useApi } from '@/hooks/useApi';
import { TrafficFlowData } from '@/types/analytics';

// Nomes dos dias da semana
const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const WEEKDAYS_FULL = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

// Horas do dia (00-23)
const HOURS = Array.from({ length: 24 }, (_, i) => 
  i.toString().padStart(2, '0')
);

export function TrafficFlowHeatmap() {
  const [start, setStart] = useState(
    dayjs().subtract(30, 'days').format('YYYY-MM-DD')
  );
  const [end, setEnd] = useState(dayjs().format('YYYY-MM-DD'));

  const {
    data: trafficData,
    isLoading,
    error,
  } = useApi<TrafficFlowData[]>(
    ['traffic-flow', start, end],
    '/analytics/traffic-flow',
    { start, end }
  );

  // Processar dados para o heatmap
  const heatmapData = (() => {
    if (!trafficData || trafficData.length === 0) return [];

    // Criar matriz de dados: cada linha é um dia da semana
    const matrix = WEEKDAYS.map((day, dayIdx) => {
      const dayData: Record<string, number | string> = { day };
      
      // Para cada hora, buscar o valor correspondente
      HOURS.forEach((hour) => {
        const entry = trafficData.find(
          (d) => d.weekday === dayIdx.toString() && d.hour === hour
        );
        dayData[hour] = entry ? parseFloat(entry.revenue.toString()) : 0;
      });
      
      return dayData;
    });

    return matrix;
  })();

  // Calcular valor máximo para normalizar cores
  const maxRevenue = trafficData
    ? Math.max(...trafficData.map((d) => parseFloat(d.revenue.toString())))
    : 0;

  // Função para obter cor baseada na intensidade
  const getColor = (value: number): string => {
    if (value === 0) return '#f3f4f6'; // Cinza claro (sem dados)
    
    const intensity = value / maxRevenue;
    
    if (intensity < 0.1) return '#dbeafe';  // Azul muito claro
    if (intensity < 0.25) return '#93c5fd';  // Azul claro
    if (intensity < 0.5) return '#60a5fa';  // Azul médio
    if (intensity < 0.75) return '#3b82f6'; // Azul
    return '#1e40af'; // Azul escuro (maior intensidade)
  };

  // Estados de loading e erro
  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-gray-500">Carregando dados de tráfego...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
        <p className="text-red-800">
          Erro ao carregar dados: {error instanceof Error ? error.message : 'Erro desconhecido'}
        </p>
      </div>
    );
  }

  if (!trafficData || trafficData.length === 0) {
    return (
      <div className="p-8 bg-yellow-50 rounded-lg border border-yellow-200">
        <p className="text-yellow-800">
          Nenhum dado de tráfego encontrado para o período selecionado.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Filtros de data */}
      <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-2">
              Data Inicial:
            </span>
            <input
              type="date"
              value={start}
              onChange={(e) => setStart(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </label>
          <label className="block">
            <span className="block text-sm font-medium text-gray-700 mb-2">
              Data Final:
            </span>
            <input
              type="date"
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </label>
        </div>
      </div>

      {/* Estatísticas resumidas */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <p className="text-sm text-blue-600 font-medium">Total de Vendas</p>
          <p className="text-2xl font-bold text-blue-900">
            {trafficData.reduce((sum, d) => sum + d.sales, 0).toLocaleString()}
          </p>
        </div>
        <div className="bg-green-50 p-4 rounded-lg border border-green-200">
          <p className="text-sm text-green-600 font-medium">Receita Total</p>
          <p className="text-2xl font-bold text-green-900">
            R$ {trafficData.reduce((sum, d) => sum + parseFloat(d.revenue.toString()), 0).toFixed(2)}
          </p>
        </div>
        <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
          <p className="text-sm text-purple-600 font-medium">Ticket Médio</p>
          <p className="text-2xl font-bold text-purple-900">
            R$ {(
              trafficData.reduce((sum, d) => sum + parseFloat(d.revenue.toString()), 0) /
              trafficData.reduce((sum, d) => sum + d.sales, 0)
            ).toFixed(2)}
          </p>
        </div>
      </div>

      {/* Heatmap */}
      <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">
          Fluxo de Tráfego por Hora e Dia da Semana
        </h3>
        <p className="text-sm text-gray-600 mb-4">
          Cores mais escuras indicam maior receita no período
        </p>

        <ResponsiveContainer width="100%" height={600}>
          <BarChart
            data={heatmapData}
            layout="vertical"
            margin={{ top: 20, right: 30, left: 80, bottom: 20 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            
            <XAxis 
              type="number" 
              domain={[0, 24]}
              label={{ value: 'Horas do Dia', position: 'insideBottom', offset: -5 }}
              tick={{ fontSize: 10 }}
            />
            
            <YAxis 
              dataKey="day" 
              type="category"
              tick={{ fontSize: 12 }}
              width={60}
            />
            
            <Tooltip
              content={({ active, payload, label }) => {
                if (active && payload && payload.length) {
                  const hour = payload[0].dataKey as string;
                  const revenue = payload[0].value as number;
                  
                  // Encontrar dados completos para esta célula
                  const fullData = trafficData.find(
                    (d) => d.weekday === WEEKDAYS.indexOf(label).toString() && 
                           d.hour === hour
                  );
                  
                  return (
                    <div className="bg-white p-4 border border-gray-300 rounded-lg shadow-lg">
                      <p className="font-semibold text-gray-800 mb-2">
                        {WEEKDAYS_FULL[WEEKDAYS.indexOf(label)]} - {hour}:00
                      </p>
                      <p className="text-sm text-gray-700">
                        <strong>Receita:</strong> R$ {revenue.toFixed(2)}
                      </p>
                      {fullData && (
                        <p className="text-sm text-gray-700">
                          <strong>Vendas:</strong> {fullData.sales} unidade(s)
                        </p>
                      )}
                    </div>
                  );
                }
                return null;
              }}
            />
            
            <Legend 
              content={() => (
                <div className="flex items-center justify-center gap-4 mt-4">
                  <span className="text-sm text-gray-600">Menor</span>
                  <div className="flex gap-1">
                    {[0, 0.1, 0.25, 0.5, 0.75, 1].map((intensity) => (
                      <div
                        key={intensity}
                        style={{
                          width: '30px',
                          height: '20px',
                          backgroundColor: getColor(intensity * maxRevenue),
                          border: '1px solid #ccc',
                        }}
                      />
                    ))}
                  </div>
                  <span className="text-sm text-gray-600">Maior</span>
                </div>
              )}
            />
            
            {/* Criar uma barra para cada hora */}
            {HOURS.map((hour) => (
              <Bar key={hour} dataKey={hour} stackId="a" fill={getColor(0)}>
                {heatmapData.map((entry, entryIdx) => {
                  const value = entry[hour] as number;
                  return (
                    <Cell
                      key={`cell-${entryIdx}-${hour}`}
                      fill={getColor(value)}
                    />
                  );
                })}
              </Bar>
            ))}
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Tabela de dados (opcional) */}
      <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">
          Dados Detalhados
        </h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Dia
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Hora
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Receita
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Vendas
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ticket Médio
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {trafficData
                .sort((a, b) => {
                  if (a.weekday !== b.weekday) {
                    return parseInt(a.weekday) - parseInt(b.weekday);
                  }
                  return parseInt(a.hour) - parseInt(b.hour);
                })
                .map((data, index) => (
                  <tr key={index}>
                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {WEEKDAYS_FULL[parseInt(data.weekday)]}
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                      {data.hour}:00
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                      R$ {parseFloat(data.revenue.toString()).toFixed(2)}
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                      {data.sales}
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                      R$ {(parseFloat(data.revenue.toString()) / data.sales).toFixed(2)}
                    </td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
```

---

## 🎨 Versão Simplificada (Sem Tailwind)

Se você não usa Tailwind CSS, aqui está uma versão com estilos inline:

```tsx
import { useState } from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  Cell,
} from 'recharts';
import dayjs from 'dayjs';
import { useApi } from '@/hooks/useApi';
import { TrafficFlowData } from '@/types/analytics';

const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const WEEKDAYS_FULL = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
const HOURS = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));

export function TrafficFlowHeatmap() {
  const [start, setStart] = useState(
    dayjs().subtract(30, 'days').format('YYYY-MM-DD')
  );
  const [end, setEnd] = useState(dayjs().format('YYYY-MM-DD'));

  const { data: trafficData, isLoading, error } = useApi<TrafficFlowData[]>(
    ['traffic-flow', start, end],
    '/analytics/traffic-flow',
    { start, end }
  );

  const heatmapData = (() => {
    if (!trafficData || trafficData.length === 0) return [];

    return WEEKDAYS.map((day, dayIdx) => {
      const dayData: Record<string, number | string> = { day };
      HOURS.forEach((hour) => {
        const entry = trafficData.find(
          (d) => d.weekday === dayIdx.toString() && d.hour === hour
        );
        dayData[hour] = entry ? parseFloat(entry.revenue.toString()) : 0;
      });
      return dayData;
    });
  })();

  const maxRevenue = trafficData
    ? Math.max(...trafficData.map((d) => parseFloat(d.revenue.toString())))
    : 0;

  const getColor = (value: number): string => {
    if (value === 0) return '#f3f4f6';
    const intensity = value / maxRevenue;
    if (intensity < 0.1) return '#dbeafe';
    if (intensity < 0.25) return '#93c5fd';
    if (intensity < 0.5) return '#60a5fa';
    if (intensity < 0.75) return '#3b82f6';
    return '#1e40af';
  };

  if (isLoading) {
    return <div style={{ padding: '20px', textAlign: 'center' }}>Carregando...</div>;
  }

  if (error) {
    return (
      <div style={{ padding: '20px', backgroundColor: '#fee', border: '1px solid #fcc' }}>
        Erro ao carregar dados
      </div>
    );
  }

  if (!trafficData || trafficData.length === 0) {
    return (
      <div style={{ padding: '20px', backgroundColor: '#fffbeb', border: '1px solid #fde047' }}>
        Nenhum dado encontrado
      </div>
    );
  }

  return (
    <div style={{ padding: '20px' }}>
      {/* Filtros */}
      <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#f9fafb', borderRadius: '8px' }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
          <label>
            <div style={{ marginBottom: '5px', fontSize: '14px', fontWeight: '500' }}>Data Inicial:</div>
            <input
              type="date"
              value={start}
              onChange={(e) => setStart(e.target.value)}
              style={{
                width: '100%',
                padding: '8px',
                border: '1px solid #ccc',
                borderRadius: '4px',
              }}
            />
          </label>
          <label>
            <div style={{ marginBottom: '5px', fontSize: '14px', fontWeight: '500' }}>Data Final:</div>
            <input
              type="date"
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              style={{
                width: '100%',
                padding: '8px',
                border: '1px solid #ccc',
                borderRadius: '4px',
              }}
            />
          </label>
        </div>
      </div>

      {/* Estatísticas */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginBottom: '20px' }}>
        <div style={{ padding: '15px', backgroundColor: '#dbeafe', borderRadius: '8px' }}>
          <div style={{ fontSize: '14px', color: '#1e40af', marginBottom: '5px' }}>Total de Vendas</div>
          <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#1e3a8a' }}>
            {trafficData.reduce((sum, d) => sum + d.sales, 0).toLocaleString()}
          </div>
        </div>
        <div style={{ padding: '15px', backgroundColor: '#d1fae5', borderRadius: '8px' }}>
          <div style={{ fontSize: '14px', color: '#065f46', marginBottom: '5px' }}>Receita Total</div>
          <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#064e3b' }}>
            R$ {trafficData.reduce((sum, d) => sum + parseFloat(d.revenue.toString()), 0).toFixed(2)}
          </div>
        </div>
        <div style={{ padding: '15px', backgroundColor: '#f3e8ff', borderRadius: '8px' }}>
          <div style={{ fontSize: '14px', color: '#6b21a8', marginBottom: '5px' }}>Ticket Médio</div>
          <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#581c87' }}>
            R$ {(
              trafficData.reduce((sum, d) => sum + parseFloat(d.revenue.toString()), 0) /
              trafficData.reduce((sum, d) => sum + d.sales, 0)
            ).toFixed(2)}
          </div>
        </div>
      </div>

      {/* Heatmap */}
      <div style={{ backgroundColor: 'white', padding: '20px', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' }}>
        <h3 style={{ marginBottom: '10px' }}>Fluxo de Tráfego por Hora e Dia da Semana</h3>
        <p style={{ fontSize: '14px', color: '#666', marginBottom: '20px' }}>
          Cores mais escuras indicam maior receita
        </p>
        
        <ResponsiveContainer width="100%" height={600}>
          <BarChart
            data={heatmapData}
            layout="vertical"
            margin={{ top: 20, right: 30, left: 80, bottom: 20 }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis type="number" domain={[0, 24]} />
            <YAxis dataKey="day" type="category" />
            <Tooltip
              content={({ active, payload, label }) => {
                if (active && payload && payload.length) {
                  const hour = payload[0].dataKey as string;
                  const revenue = payload[0].value as number;
                  const fullData = trafficData.find(
                    (d) => d.weekday === WEEKDAYS.indexOf(label).toString() && d.hour === hour
                  );
                  return (
                    <div style={{ backgroundColor: 'white', padding: '10px', border: '1px solid #ccc', borderRadius: '4px' }}>
                      <p style={{ fontWeight: 'bold', marginBottom: '5px' }}>
                        {WEEKDAYS_FULL[WEEKDAYS.indexOf(label)]} - {hour}:00
                      </p>
                      <p style={{ fontSize: '14px' }}>Receita: R$ {revenue.toFixed(2)}</p>
                      {fullData && <p style={{ fontSize: '14px' }}>Vendas: {fullData.sales}</p>}
                    </div>
                  );
                }
                return null;
              }}
            />
            <Legend />
            {HOURS.map((hour) => (
              <Bar key={hour} dataKey={hour} stackId="a" fill={getColor(0)}>
                {heatmapData.map((entry, entryIdx) => {
                  const value = entry[hour] as number;
                  return (
                    <Cell
                      key={`cell-${entryIdx}-${hour}`}
                      fill={getColor(value)}
                    />
                  );
                })}
              </Bar>
            ))}
          </BarChart>
        </ResponsiveContainer>

        {/* Legenda de cores */}
        <div style={{ marginTop: '20px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '10px' }}>
          <span>Menor</span>
          <div style={{ display: 'flex', gap: '2px' }}>
            {[0, 0.1, 0.25, 0.5, 0.75, 1].map((intensity) => (
              <div
                key={intensity}
                style={{
                  width: '30px',
                  height: '20px',
                  backgroundColor: getColor(intensity * maxRevenue),
                  border: '1px solid #ccc',
                }}
              />
            ))}
          </div>
          <span>Maior</span>
        </div>
      </div>
    </div>
  );
}
```

---

## 📝 Explicações Detalhadas

### 1. Estrutura de Dados do Heatmap

O heatmap é criado usando um `BarChart` em layout vertical, onde:
- Cada linha representa um dia da semana
- Cada coluna representa uma hora do dia
- A cor de cada célula indica a intensidade (receita)

```typescript
const heatmapData = [
  { day: 'Dom', '00': 0, '01': 0, '02': 0, ..., '23': 150.50 },
  { day: 'Seg', '00': 0, '01': 0, '02': 0, ..., '23': 200.75 },
  // ...
];
```

### 2. Normalização de Cores

As cores são calculadas baseadas na intensidade relativa:

```typescript
const intensity = value / maxRevenue;
```

Isso garante que a cor seja proporcional ao valor máximo encontrado, facilitando a comparação visual.

### 3. Layout Vertical do BarChart

Usamos `layout="vertical"` para que:
- O eixo Y mostre os dias da semana (horizontalmente)
- O eixo X mostre as horas (verticalmente)
- Cada barra empilhada represente uma hora, com células coloridas por dia

### 4. Múltiplas Barras Empilhadas

Criamos uma barra para cada hora (00-23), e cada célula dentro da barra representa um dia da semana:

```tsx
{HOURS.map((hour) => (
  <Bar key={hour} dataKey={hour} stackId="a">
    {heatmapData.map((entry, entryIdx) => (
      <Cell fill={getColor(entry[hour])} />
    ))}
  </Bar>
))}
```

---

## 🎯 Alternativa: Heatmap com Células Customizadas

Se preferir um heatmap mais tradicional (matriz de células), você pode usar esta abordagem:

```tsx
import { useState } from 'react';
import { useApi } from '@/hooks/useApi';
import { TrafficFlowData } from '@/types/analytics';

export function TrafficFlowHeatmapGrid() {
  const { data: trafficData } = useApi<TrafficFlowData[]>(
    ['traffic-flow'],
    '/analytics/traffic-flow'
  );

  const getCellValue = (weekday: number, hour: string) => {
    const entry = trafficData?.find(
      (d) => d.weekday === weekday.toString() && d.hour === hour
    );
    return entry ? parseFloat(entry.revenue.toString()) : 0;
  };

  const maxRevenue = trafficData
    ? Math.max(...trafficData.map((d) => parseFloat(d.revenue.toString())))
    : 0;

  const getColor = (value: number) => {
    if (value === 0) return '#f3f4f6';
    const intensity = value / maxRevenue;
    if (intensity < 0.25) return '#dbeafe';
    if (intensity < 0.5) return '#93c5fd';
    if (intensity < 0.75) return '#3b82f6';
    return '#1e40af';
  };

  return (
    <div style={{ padding: '20px' }}>
      <table style={{ borderCollapse: 'collapse', width: '100%' }}>
        <thead>
          <tr>
            <th style={{ padding: '10px', border: '1px solid #ccc' }}>Dia</th>
            {HOURS.map((hour) => (
              <th key={hour} style={{ padding: '10px', border: '1px solid #ccc', fontSize: '10px' }}>
                {hour}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {WEEKDAYS.map((day, dayIdx) => (
            <tr key={day}>
              <td style={{ padding: '10px', border: '1px solid #ccc', fontWeight: 'bold' }}>
                {day}
              </td>
              {HOURS.map((hour) => {
                const value = getCellValue(dayIdx, hour);
                return (
                  <td
                    key={hour}
                    style={{
                      padding: '10px',
                      border: '1px solid #ccc',
                      backgroundColor: getColor(value),
                      textAlign: 'center',
                      fontSize: '10px',
                    }}
                    title={`${day} ${hour}:00 - R$ ${value.toFixed(2)}`}
                  >
                    {value > 0 && `R$ ${value.toFixed(0)}`}
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

---

## 🎨 Melhorias Opcionais

### 1. Filtro por Métrica

Permita alternar entre receita e quantidade de vendas:

```tsx
const [metric, setMetric] = useState<'revenue' | 'sales'>('revenue');

// No processamento dos dados:
dayData[hour] = entry 
  ? (metric === 'revenue' 
      ? parseFloat(entry.revenue.toString()) 
      : entry.sales)
  : 0;
```

### 2. Gráfico de Linha por Dia

Mostre a evolução ao longo do dia para um dia específico:

```tsx
const [selectedDay, setSelectedDay] = useState<number | null>(null);

// Filtrar dados para o dia selecionado
const dayData = trafficData?.filter(
  (d) => d.weekday === selectedDay?.toString()
) || [];
```

### 3. Comparação de Períodos

Compare dois períodos lado a lado:

```tsx
const [period1, setPeriod1] = useState({ start: '', end: '' });
const [period2, setPeriod2] = useState({ start: '', end: '' });
```

### 4. Exportar Dados

Adicione botão para exportar CSV:

```tsx
const exportToCSV = () => {
  const csv = [
    ['Dia', 'Hora', 'Receita', 'Vendas'].join(','),
    ...trafficData.map(d => [
      WEEKDAYS_FULL[parseInt(d.weekday)],
      `${d.hour}:00`,
      d.revenue,
      d.sales
    ].join(','))
  ].join('\n');
  
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'traffic-flow.csv';
  a.click();
};
```

---

## ✅ Checklist de Implementação

- [ ] Instalar dependências (recharts, react-query, axios, dayjs)
- [ ] Configurar API client
- [ ] Criar hook useApi
- [ ] Definir tipos TypeScript
- [ ] Criar componente TrafficFlowHeatmap
- [ ] Implementar processamento de dados
- [ ] Adicionar função de normalização de cores
- [ ] Testar com dados reais
- [ ] Adicionar tratamento de erros
- [ ] Adicionar estados de loading
- [ ] Estilizar conforme design system
- [ ] Adicionar filtros de data
- [ ] Adicionar estatísticas resumidas
- [ ] Adicionar tabela de dados (opcional)
- [ ] Adicionar testes (opcional)

---

## 🐛 Troubleshooting

### Problema: Células não aparecem coloridas
**Solução**: Verifique se os dados estão sendo processados corretamente e se `getColor()` está retornando cores válidas.

### Problema: Eixo X muito longo
**Solução**: Ajuste o `margin` do BarChart ou use `angle` e `textAnchor` no XAxis para rotacionar labels.

### Problema: Cores muito similares
**Solução**: Ajuste os thresholds na função `getColor()` para criar mais contraste.

### Problema: Dados não aparecem
**Solução**: Verifique se o formato de data está correto (YYYY-MM-DD) e se há dados no período selecionado.

---

## 📚 Recursos Adicionais

- [Documentação Recharts - BarChart](https://recharts.org/en-US/api/BarChart)
- [Documentação Recharts - Layout](https://recharts.org/en-US/api/BarChart#layout)
- [Documentação Day.js](https://day.js.org/)

---

Este guia fornece uma implementação completa e pronta para uso do gráfico de fluxo de tráfego (heatmap)!

