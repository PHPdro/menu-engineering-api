## Menu Engineering API

API para Engenharia de Cardápio (restaurantes/cafés). Cadastros, estoque por lotes, vendas com baixa FIFO e analytics (popularidade x rentabilidade, perecíveis, tendências de preço e fluxo).

### Stack
- Laravel 12 (PHP ^8.2)
- SQLite (padrão) | Swagger via l5-swagger

### Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
type NUL > database/database.sqlite
php artisan migrate
php artisan l5-swagger:generate
php artisan serve
```
Swagger UI: http://127.0.0.1:8000/api/documentation

### Endpoints principais
- Ingredients: CRUD `/api/ingredients`
- Suppliers: CRUD `/api/suppliers`
- Ingredient Prices: CRUD `/api/ingredient-prices`
- Batches (estoque): CRUD `/api/batches`
- Dishes: CRUD `/api/dishes`
- Recipes: CRUD `/api/recipes`
- Recipe Items: CRUD `/api/recipe-items`
- Sales: `GET/POST /api/sales` (baixa FIFO por receita)
- Analytics:
  - `/api/analytics/menu-matrix`
  - `/api/analytics/perishables-alerts`
  - `/api/analytics/price-trends`
  - `/api/analytics/traffic-flow`
  - `/api/analytics/breakeven`

### Notas
- Baixa de estoque FIFO por validade (fallback recebimento).
- Matriz 2x2 usa medianas como limiares (popularidade e lucro por prato).

### Próximos passos sugeridos
- Auth (Sanctum/Passport), RBAC, CORS e rate limiting
- Seeds + testes (FIFO, matriz 2x2, alertas, tendências)
- Índices/caching e jobs agendados