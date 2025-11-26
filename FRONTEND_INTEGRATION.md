# Guia de Integração Simplificada - Frontend

## 🎯 Problema Resolvido

Antes, para criar um prato completo no frontend, você precisava fazer **3 requisições separadas**:
1. `POST /api/dishes` - Criar o prato
2. `POST /api/recipes` - Criar a receita
3. `POST /api/recipe-items` (múltiplas vezes) - Criar cada ingrediente

Agora você pode fazer **tudo em 1 única requisição**! 🚀

## ✅ Solução Implementada

Os endpoints `POST /api/dishes` e `PUT /api/dishes/{id}` agora aceitam um campo opcional `recipe` que permite criar/atualizar tudo de uma vez.

## 📝 Exemplos de Uso

### 1. Criar Prato Simples (sem receita)

```json
POST /api/dishes
{
  "name": "Hambúrguer Artesanal",
  "sku": "HAMB-001",
  "price": 25.90,
  "is_active": true
}
```

### 2. Criar Prato Completo (com receita e ingredientes) - ⭐ RECOMENDADO

```json
POST /api/dishes
{
  "name": "Hambúrguer Artesanal",
  "sku": "HAMB-001",
  "price": 25.90,
  "is_active": true,
  "recipe": {
    "version": "v1",
    "items": [
      {
        "ingredient_id": 1,
        "quantity": 200,
        "notes": "Carne moída premium"
      },
      {
        "ingredient_id": 5,
        "quantity": 1,
        "notes": "Pão brioche"
      },
      {
        "ingredient_id": 12,
        "quantity": 50,
        "notes": "Queijo cheddar"
      }
    ]
  }
}
```

### 3. Atualizar Prato e Receita

```json
PUT /api/dishes/1
{
  "price": 27.90,
  "recipe": {
    "version": "v2",
    "items": [
      {
        "ingredient_id": 1,
        "quantity": 220,
        "notes": "Mais carne"
      },
      {
        "ingredient_id": 5,
        "quantity": 1,
        "notes": null
      }
    ]
  }
}
```

## 🎨 Fluxo Recomendado no Frontend

### Tela de Cadastro de Prato

1. **Formulário do Prato**
   - Nome
   - SKU (opcional)
   - Preço
   - Status (ativo/inativo)

2. **Seção de Receita (Opcional)**
   - Lista de ingredientes disponíveis (buscar via `GET /api/ingredients`)
   - Adicionar ingredientes com quantidade
   - Campo de observações (opcional)

3. **Enviar tudo de uma vez**
   ```javascript
   const response = await fetch('/api/dishes', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({
       name: formData.name,
       sku: formData.sku,
       price: formData.price,
       is_active: formData.is_active,
       recipe: {
         version: 'v1',
         items: ingredients.map(ing => ({
           ingredient_id: ing.id,
           quantity: ing.quantity,
           notes: ing.notes || null
         }))
       }
     })
   });
   ```

## 📋 Estrutura de Dados

### Request Body (POST/PUT /api/dishes)

```typescript
interface DishRequest {
  name: string;              // Obrigatório
  sku?: string;              // Opcional
  price: number;             // Obrigatório
  is_active?: boolean;       // Opcional (default: true)
  recipe?: {                 // Opcional - cria receita junto
    version?: string;        // Opcional (default: 'v1')
    items?: Array<{          // Opcional - lista de ingredientes
      ingredient_id: number; // Obrigatório se items presente
      quantity: number;      // Obrigatório se items presente (min: 0.001)
      notes?: string;        // Opcional
    }>;
  };
}
```

### Response (com relacionamentos carregados)

```typescript
interface DishResponse {
  id: number;
  name: string;
  sku: string | null;
  price: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  recipe: {
    id: number;
    dish_id: number;
    version: string;
    is_active: boolean;
    items: Array<{
      id: number;
      recipe_id: number;
      ingredient_id: number;
      quantity: string;
      notes: string | null;
      ingredient: {
        id: number;
        name: string;
        unit: string;
        // ... outros campos
      };
    }>;
  } | null;
}
```

## 🔄 Quando Usar Cada Abordagem

### ✅ Use a Abordagem Simplificada (recomendado) quando:
- Criar um novo prato pela primeira vez
- O usuário está preenchendo tudo em um único formulário
- Você quer garantir consistência (tudo ou nada)

### ⚠️ Use os Endpoints Separados quando:
- Você precisa criar o prato primeiro e adicionar receita depois
- Você quer permitir edição incremental (adicionar ingredientes um por um)
- Você precisa de mais controle sobre o processo

## 🎯 Benefícios da Abordagem Simplificada

1. **Menos Requisições**: 1 requisição em vez de 3+
2. **Transação Atômica**: Se algo falhar, nada é salvo (rollback automático)
3. **Melhor UX**: Usuário preenche tudo de uma vez
4. **Menos Código no Frontend**: Lógica mais simples
5. **Menos Erros**: Menos pontos de falha

## 📚 Endpoints Disponíveis

### Endpoints Simplificados (Recomendados)
- `POST /api/dishes` - Criar prato (com receita opcional)
- `PUT /api/dishes/{id}` - Atualizar prato (com receita opcional)
- `GET /api/dishes` - Listar pratos (já vem com receita carregada)
- `GET /api/dishes/{id}` - Detalhes do prato (com receita carregada)

### Endpoints Separados (Para casos específicos)
- `POST /api/recipes` - Criar receita separadamente
- `POST /api/recipe-items` - Adicionar ingrediente à receita
- `PUT /api/recipe-items/{id}` - Atualizar ingrediente
- `DELETE /api/recipe-items/{id}` - Remover ingrediente

### Endpoints Auxiliares
- `GET /api/ingredients` - Listar ingredientes disponíveis (para o select)

## 💡 Dica Pro

No frontend, você pode criar um componente reutilizável:

```javascript
// useDishForm.js
export const useDishForm = () => {
  const createDish = async (dishData) => {
    const response = await fetch('/api/dishes', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dishData)
    });
    return response.json();
  };

  const updateDish = async (dishId, dishData) => {
    const response = await fetch(`/api/dishes/${dishId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dishData)
    });
    return response.json();
  };

  return { createDish, updateDish };
};
```

## ⚠️ Observações Importantes

1. **Transações**: Tudo é feito em uma transação de banco de dados. Se algo falhar, nada é salvo.

2. **Atualização de Receita**: Quando você atualiza a receita via `PUT /api/dishes/{id}`, os itens antigos são **substituídos** pelos novos. Se você quiser adicionar/remover individualmente, use os endpoints separados.

3. **Versões**: O sistema permite múltiplas versões de receita, mas apenas uma pode estar ativa por vez. A receita criada via endpoint simplificado sempre será marcada como ativa.

4. **Validação**: Todos os campos são validados. Se `recipe.items` for fornecido, `ingredient_id` e `quantity` são obrigatórios para cada item.

