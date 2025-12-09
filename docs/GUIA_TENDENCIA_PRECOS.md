# Guia Completo: Tendência de Preços por Fornecedor

Este guia mostra como implementar o gráfico de tendência de preços por fornecedor usando React + Recharts.

## 📋 Visão Geral

O endpoint `/api/analytics/price-trends` retorna o histórico de preços de um ingrediente específico, agrupado por fornecedor, permitindo comparar a evolução dos preços ao longo do tempo.

### Endpoint
```
GET /api/analytics/price-trends?ingredient_id={ID}
```

### Parâmetros
- `ingredient_id` (opcional): ID do ingrediente. Se não fornecido, retorna todos os ingredientes.

### Resposta
Array de objetos `PriceTrend`:
```typescript
interface PriceTrend {
  id: number;
  ingredient_id: number;
  supplier_id: number;
  price: string;           // Preço em formato decimal
  valid_from: string;     // Data de início da validade (YYYY-MM-DD)
  valid_to: string | null; // Data de fim da validade (pode ser null)
  supplier: {
    id: number;
    name: string;
    contact_name: string;
    email: string;
    phone: string;
  };
}
```

---

## 🚀 Passo a Passo Completo

### Passo 1: Configuração Inicial

Certifique-se de ter as dependências instaladas:

```bash
npm install recharts @tanstack/react-query axios dayjs
```

### Passo 2: Configurar API Client

Crie o arquivo `src/lib/api.ts`:

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

Crie o arquivo `src/hooks/useApi.ts`:

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

Crie o arquivo `src/types/analytics.ts`:

```typescript
export interface PriceTrend {
  id: number;
  ingredient_id: number;
  supplier_id: number;
  price: string;
  valid_from: string;
  valid_to: string | null;
  supplier: {
    id: number;
    name: string;
    contact_name: string;
    email: string;
    phone: string;
  };
}

export interface Ingredient {
  id: number;
  name: string;
  unit: string;
  is_perishable: boolean;
  shelf_life_days: number | null;
  min_stock: number;
}
```

### Passo 5: Componente Completo do Gráfico

Crie o arquivo `src/components/PriceTrendsChart.tsx`:

```tsx
import { useState } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import dayjs from 'dayjs';
import { useApi } from '@/hooks/useApi';
import { PriceTrend, Ingredient } from '@/types/analytics';

// Cores para diferentes fornecedores
const SUPPLIER_COLORS = [
  '#3b82f6', // Azul
  '#22c55e', // Verde
  '#f59e0b', // Laranja
  '#ef4444', // Vermelho
  '#8b5cf6', // Roxo
  '#ec4899', // Rosa
  '#06b6d4', // Ciano
  '#84cc16', // Lima
];

export function PriceTrendsChart() {
  const [ingredientId, setIngredientId] = useState<number | null>(null);

  // Buscar lista de ingredientes para o dropdown
  const { data: ingredientsResponse, isLoading: loadingIngredients } = useApi<{
    data: Ingredient[];
  }>(['ingredients'], '/ingredients');

  // Buscar tendências de preço quando um ingrediente for selecionado
  const {
    data: priceTrends,
    isLoading: loadingPrices,
    error,
  } = useApi<PriceTrend[]>(
    ['price-trends', ingredientId],
    '/analytics/price-trends',
    ingredientId ? { ingredient_id: ingredientId } : undefined,
    { enabled: !!ingredientId }
  );

  // Processar dados para o gráfico
  const chartData = (() => {
    if (!priceTrends || priceTrends.length === 0) return [];

    // Agrupar preços por fornecedor
    const groupedBySupplier = priceTrends.reduce(
      (acc, price) => {
        const supplierName = price.supplier.name;
        if (!acc[supplierName]) {
          acc[supplierName] = [];
        }
        acc[supplierName].push({
          date: dayjs(price.valid_from).format('DD/MM/YYYY'),
          price: parseFloat(price.price),
          valid_from: price.valid_from,
          valid_to: price.valid_to,
        });
        return acc;
      },
      {} as Record<
        string,
        Array<{
          date: string;
          price: number;
          valid_from: string;
          valid_to: string | null;
        }>
      >
    );

    // Obter todas as datas únicas e ordenadas
    const allDates = Array.from(
      new Set(priceTrends.map((p) => dayjs(p.valid_from).format('DD/MM/YYYY')))
    ).sort((a, b) =>
      dayjs(a, 'DD/MM/YYYY').diff(dayjs(b, 'DD/MM/YYYY'))
    );

    // Criar estrutura de dados para o Recharts
    // Cada objeto representa uma data, com valores para cada fornecedor
    const chartData = allDates.map((date) => {
      const dataPoint: Record<string, string | number | null> = { date };
      
      // Para cada fornecedor, encontrar o preço válido nessa data
      Object.keys(groupedBySupplier).forEach((supplier) => {
        const prices = groupedBySupplier[supplier];
        
        // Encontrar o preço válido para esta data
        // Um preço é válido se valid_from <= date <= valid_to (ou valid_to é null)
        const validPrice = prices.find((p) => {
          const priceDate = dayjs(p.valid_from, 'DD/MM/YYYY');
          const currentDate = dayjs(date, 'DD/MM/YYYY');
          
          if (p.valid_to) {
            const validToDate = dayjs(p.valid_to);
            return (
              currentDate.isSameOrAfter(priceDate) &&
              currentDate.isBefore(validToDate)
            );
          }
          // Se valid_to é null, o preço é válido até hoje
          return currentDate.isSameOrAfter(priceDate);
        });
        
        dataPoint[supplier] = validPrice ? validPrice.price : null;
      });
      
      return dataPoint;
    });

    return chartData;
  })();

  // Obter lista de fornecedores únicos
  const suppliers = Array.from(
    new Set(priceTrends?.map((p) => p.supplier.name) || [])
  );

  // Estados de loading
  if (loadingIngredients) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-gray-500">Carregando ingredientes...</div>
      </div>
    );
  }

  // Estado de erro
  if (error) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
        <p className="text-red-800">
          Erro ao carregar dados: {error instanceof Error ? error.message : 'Erro desconhecido'}
        </p>
      </div>
    );
  }

  // Estado vazio (sem ingrediente selecionado)
  if (!ingredientId) {
    return (
      <div className="p-8 bg-gray-50 rounded-lg border border-gray-200">
        <label className="block mb-4">
          <span className="block text-sm font-medium text-gray-700 mb-2">
            Selecione um ingrediente para ver a tendência de preços:
          </span>
          <select
            value=""
            onChange={(e) => setIngredientId(Number(e.target.value) || null)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">-- Selecione um ingrediente --</option>
            {ingredientsResponse?.data.map((ing) => (
              <option key={ing.id} value={ing.id}>
                {ing.name} ({ing.unit})
              </option>
            ))}
          </select>
        </label>
      </div>
    );
  }

  // Estado de loading dos preços
  if (loadingPrices) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-gray-500">Carregando tendências de preço...</div>
      </div>
    );
  }

  // Estado vazio (sem dados de preço)
  if (!priceTrends || priceTrends.length === 0) {
    return (
      <div className="p-8 bg-yellow-50 rounded-lg border border-yellow-200">
        <p className="text-yellow-800">
          Nenhum dado de preço encontrado para este ingrediente.
        </p>
        <button
          onClick={() => setIngredientId(null)}
          className="mt-4 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
        >
          Selecionar outro ingrediente
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Seletor de ingrediente */}
      <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <label className="block">
          <span className="block text-sm font-medium text-gray-700 mb-2">
            Ingrediente:
          </span>
          <select
            value={ingredientId}
            onChange={(e) => setIngredientId(Number(e.target.value) || null)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">-- Selecione um ingrediente --</option>
            {ingredientsResponse?.data.map((ing) => (
              <option key={ing.id} value={ing.id}>
                {ing.name} ({ing.unit})
              </option>
            ))}
          </select>
        </label>
      </div>

      {/* Informações do ingrediente selecionado */}
      {ingredientsResponse?.data.find((ing) => ing.id === ingredientId) && (
        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <p className="text-sm text-blue-800">
            <strong>Ingrediente selecionado:</strong>{' '}
            {
              ingredientsResponse.data.find((ing) => ing.id === ingredientId)
                ?.name
            }
          </p>
          <p className="text-sm text-blue-700 mt-1">
            <strong>Fornecedores:</strong> {suppliers.length} fornecedor(es)
            encontrado(s)
          </p>
        </div>
      )}

      {/* Gráfico */}
      <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">
          Evolução de Preços por Fornecedor
        </h3>
        
        <ResponsiveContainer width="100%" height={500}>
          <LineChart
            data={chartData}
            margin={{ top: 5, right: 30, left: 20, bottom: 80 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            
            <XAxis
              dataKey="date"
              angle={-45}
              textAnchor="end"
              height={100}
              tick={{ fontSize: 12 }}
              stroke="#6b7280"
            />
            
            <YAxis
              label={{
                value: 'Preço (R$)',
                angle: -90,
                position: 'insideLeft',
                style: { textAnchor: 'middle' },
              }}
              tick={{ fontSize: 12 }}
              stroke="#6b7280"
              tickFormatter={(value) => `R$ ${value.toFixed(2)}`}
            />
            
            <Tooltip
              content={({ active, payload, label }) => {
                if (active && payload && payload.length) {
                  return (
                    <div className="bg-white p-4 border border-gray-300 rounded-lg shadow-lg">
                      <p className="font-semibold text-gray-800 mb-2">
                        {label}
                      </p>
                      {payload.map((entry, index) => {
                        if (entry.value === null) return null;
                        return (
                          <p
                            key={index}
                            className="text-sm"
                            style={{ color: entry.color }}
                          >
                            <strong>{entry.dataKey}:</strong> R${' '}
                            {Number(entry.value).toFixed(2)}
                          </p>
                        );
                      })}
                    </div>
                  );
                }
                return null;
              }}
            />
            
            <Legend
              wrapperStyle={{ paddingTop: '20px' }}
              iconType="line"
            />
            
            {/* Linhas para cada fornecedor */}
            {suppliers.map((supplier, index) => (
              <Line
                key={supplier}
                type="monotone"
                dataKey={supplier}
                stroke={SUPPLIER_COLORS[index % SUPPLIER_COLORS.length]}
                strokeWidth={2}
                dot={{ r: 4 }}
                connectNulls={true} // Conecta pontos mesmo com valores null
                name={supplier}
              />
            ))}
          </LineChart>
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
                  Fornecedor
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Preço
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Válido de
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Válido até
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {priceTrends.map((trend) => (
                <tr key={trend.id}>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {trend.supplier.name}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                    R$ {parseFloat(trend.price).toFixed(2)}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    {dayjs(trend.valid_from).format('DD/MM/YYYY')}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    {trend.valid_to
                      ? dayjs(trend.valid_to).format('DD/MM/YYYY')
                      : 'Indefinido'}
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
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import dayjs from 'dayjs';
import { useApi } from '@/hooks/useApi';
import { PriceTrend, Ingredient } from '@/types/analytics';

const SUPPLIER_COLORS = [
  '#3b82f6', '#22c55e', '#f59e0b', '#ef4444',
  '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16',
];

export function PriceTrendsChart() {
  const [ingredientId, setIngredientId] = useState<number | null>(null);

  const { data: ingredientsResponse, isLoading: loadingIngredients } = useApi<{
    data: Ingredient[];
  }>(['ingredients'], '/ingredients');

  const {
    data: priceTrends,
    isLoading: loadingPrices,
    error,
  } = useApi<PriceTrend[]>(
    ['price-trends', ingredientId],
    '/analytics/price-trends',
    ingredientId ? { ingredient_id: ingredientId } : undefined,
    { enabled: !!ingredientId }
  );

  // Processar dados
  const chartData = (() => {
    if (!priceTrends || priceTrends.length === 0) return [];

    const groupedBySupplier = priceTrends.reduce(
      (acc, price) => {
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
      },
      {} as Record<string, Array<{ date: string; price: number; valid_from: string }>>
    );

    const allDates = Array.from(
      new Set(priceTrends.map((p) => dayjs(p.valid_from).format('DD/MM/YYYY')))
    ).sort((a, b) => dayjs(a, 'DD/MM/YYYY').diff(dayjs(b, 'DD/MM/YYYY')));

    return allDates.map((date) => {
      const dataPoint: Record<string, string | number | null> = { date };
      Object.keys(groupedBySupplier).forEach((supplier) => {
        const point = groupedBySupplier[supplier].find(
          (p) => dayjs(p.date, 'DD/MM/YYYY').isSame(dayjs(date, 'DD/MM/YYYY'))
        );
        dataPoint[supplier] = point ? point.price : null;
      });
      return dataPoint;
    });
  })();

  const suppliers = Array.from(
    new Set(priceTrends?.map((p) => p.supplier.name) || [])
  );

  if (loadingIngredients) {
    return <div style={{ padding: '20px', textAlign: 'center' }}>Carregando...</div>;
  }

  if (error) {
    return (
      <div style={{ padding: '20px', backgroundColor: '#fee', border: '1px solid #fcc' }}>
        Erro ao carregar dados
      </div>
    );
  }

  if (!ingredientId) {
    return (
      <div style={{ padding: '20px', backgroundColor: '#f9fafb', borderRadius: '8px' }}>
        <label style={{ display: 'block', marginBottom: '10px' }}>
          Selecione um ingrediente:
        </label>
        <select
          value=""
          onChange={(e) => setIngredientId(Number(e.target.value) || null)}
          style={{
            width: '100%',
            padding: '8px',
            border: '1px solid #ccc',
            borderRadius: '4px',
          }}
        >
          <option value="">-- Selecione --</option>
          {ingredientsResponse?.data.map((ing) => (
            <option key={ing.id} value={ing.id}>
              {ing.name} ({ing.unit})
            </option>
          ))}
        </select>
      </div>
    );
  }

  if (loadingPrices) {
    return <div style={{ padding: '20px', textAlign: 'center' }}>Carregando preços...</div>;
  }

  if (!priceTrends || priceTrends.length === 0) {
    return (
      <div style={{ padding: '20px', backgroundColor: '#fffbeb', border: '1px solid #fde047' }}>
        Nenhum dado encontrado
      </div>
    );
  }

  return (
    <div style={{ padding: '20px' }}>
      <div style={{ marginBottom: '20px' }}>
        <label style={{ display: 'block', marginBottom: '8px' }}>Ingrediente:</label>
        <select
          value={ingredientId}
          onChange={(e) => setIngredientId(Number(e.target.value) || null)}
          style={{
            width: '100%',
            padding: '8px',
            border: '1px solid #ccc',
            borderRadius: '4px',
          }}
        >
          <option value="">-- Selecione --</option>
          {ingredientsResponse?.data.map((ing) => (
            <option key={ing.id} value={ing.id}>
              {ing.name} ({ing.unit})
            </option>
          ))}
        </select>
      </div>

      <div style={{ backgroundColor: 'white', padding: '20px', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' }}>
        <h3 style={{ marginBottom: '20px' }}>Evolução de Preços por Fornecedor</h3>
        <ResponsiveContainer width="100%" height={500}>
          <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 80 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis
              dataKey="date"
              angle={-45}
              textAnchor="end"
              height={100}
            />
            <YAxis
              label={{ value: 'Preço (R$)', angle: -90, position: 'insideLeft' }}
              tickFormatter={(value) => `R$ ${value.toFixed(2)}`}
            />
            <Tooltip
              formatter={(value: number) => `R$ ${value.toFixed(2)}`}
            />
            <Legend />
            {suppliers.map((supplier, index) => (
              <Line
                key={supplier}
                type="monotone"
                dataKey={supplier}
                stroke={SUPPLIER_COLORS[index % SUPPLIER_COLORS.length]}
                strokeWidth={2}
                dot={{ r: 4 }}
                connectNulls={true}
              />
            ))}
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}
```

---

## 📝 Explicações Detalhadas

### 1. Agrupamento por Fornecedor

O código agrupa os preços por fornecedor usando `reduce`:

```typescript
const groupedBySupplier = priceTrends.reduce((acc, price) => {
  const supplierName = price.supplier.name;
  if (!acc[supplierName]) {
    acc[supplierName] = [];
  }
  acc[supplierName].push({
    date: dayjs(price.valid_from).format('DD/MM/YYYY'),
    price: parseFloat(price.price),
  });
  return acc;
}, {});
```

### 2. Estrutura de Dados para o Recharts

O Recharts espera um array de objetos, onde cada objeto representa uma data e contém valores para cada fornecedor:

```typescript
[
  { date: '01/11/2025', 'Fornecedor A': 10.50, 'Fornecedor B': 11.00 },
  { date: '02/11/2025', 'Fornecedor A': 10.75, 'Fornecedor B': null },
  // ...
]
```

### 3. `connectNulls={true}`

Esta propriedade faz com que o Recharts conecte pontos mesmo quando há valores `null` entre eles, criando uma linha contínua.

### 4. Formatação de Datas

Usamos `dayjs` para formatar as datas:
- `dayjs(price.valid_from).format('DD/MM/YYYY')` - Formata para exibição
- `dayjs(a, 'DD/MM/YYYY').diff(dayjs(b, 'DD/MM/YYYY'))` - Compara datas para ordenação

---

## 🎯 Melhorias Opcionais

### 1. Filtro por Período

Adicione filtros de data:

```tsx
const [startDate, setStartDate] = useState<string>('');
const [endDate, setEndDate] = useState<string>('');

// Filtrar chartData
const filteredData = chartData.filter(item => {
  const itemDate = dayjs(item.date, 'DD/MM/YYYY');
  if (startDate && itemDate.isBefore(dayjs(startDate))) return false;
  if (endDate && itemDate.isAfter(dayjs(endDate))) return false;
  return true;
});
```

### 2. Comparação de Múltiplos Ingredientes

Permita selecionar múltiplos ingredientes e comparar:

```tsx
const [selectedIngredients, setSelectedIngredients] = useState<number[]>([]);
```

### 3. Exportar Dados

Adicione botão para exportar CSV:

```tsx
const exportToCSV = () => {
  const csv = [
    ['Data', ...suppliers].join(','),
    ...chartData.map(row => [
      row.date,
      ...suppliers.map(s => row[s] || '')
    ].join(','))
  ].join('\n');
  
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'price-trends.csv';
  a.click();
};
```

---

## ✅ Checklist de Implementação

- [ ] Instalar dependências (recharts, react-query, axios, dayjs)
- [ ] Configurar API client
- [ ] Criar hook useApi
- [ ] Definir tipos TypeScript
- [ ] Criar componente PriceTrendsChart
- [ ] Testar com dados reais
- [ ] Adicionar tratamento de erros
- [ ] Adicionar estados de loading
- [ ] Estilizar conforme design system
- [ ] Adicionar testes (opcional)

---

## 🐛 Troubleshooting

### Problema: Linhas não aparecem
**Solução**: Verifique se `connectNulls={true}` está definido e se os dados estão no formato correto.

### Problema: Datas desordenadas
**Solução**: Certifique-se de ordenar as datas antes de criar o chartData:
```typescript
.sort((a, b) => dayjs(a, 'DD/MM/YYYY').diff(dayjs(b, 'DD/MM/YYYY')))
```

### Problema: Valores null não conectam
**Solução**: Use `connectNulls={true}` na propriedade `Line`.

### Problema: Cores repetidas
**Solução**: Use um array de cores maior ou gere cores dinamicamente.

---

## 📚 Recursos Adicionais

- [Documentação Recharts](https://recharts.org/)
- [Documentação React Query](https://tanstack.com/query/latest)
- [Documentação Day.js](https://day.js.org/)

---

Este guia fornece uma implementação completa e pronta para uso do gráfico de tendência de preços por fornecedor!

