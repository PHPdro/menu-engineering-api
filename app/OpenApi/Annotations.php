<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="Menu Engineering API",
 *   description="API para gestão de engenharia de cardápio"
 * )
 *
 * @OA\Server(
 *   url=L5_SWAGGER_CONST_HOST,
 *   description="Servidor da API"
 * )
 * @OA\Server(
 *   url="/",
 *   description="Servidor relativo (para Laravel Cloud)"
 * )
 *
 * @OA\Tag(name="Ingredients", description="Gestão de insumos")
 * @OA\Tag(name="Suppliers", description="Fornecedores e preços")
 * @OA\Tag(name="Batches", description="Lotes e estoque")
 * @OA\Tag(name="Dishes", description="Pratos do cardápio")
 * @OA\Tag(name="Recipes", description="Fichas técnicas e itens")
 * @OA\Tag(name="Sales", description="Vendas e baixa de estoque")
 * @OA\Tag(name="Analytics", description="Relatórios e métricas")
 *
 * @OA\Components(
 *   @OA\Schema(
 *     schema="Ingredient",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="unit", type="string"),
 *     @OA\Property(property="is_perishable", type="boolean"),
 *     @OA\Property(property="shelf_life_days", type="integer", nullable=true),
 *     @OA\Property(property="min_stock", type="number", format="float"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 *   ),
 *   @OA\Schema(
 *     schema="Supplier",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="contact_name", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true)
 *   ),
 *   @OA\Schema(
 *     schema="IngredientPrice",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="ingredient_id", type="integer"),
 *     @OA\Property(property="supplier_id", type="integer"),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="purchase_unit_quantity", type="number", format="float"),
 *     @OA\Property(property="purchase_unit", type="string", nullable=true),
 *     @OA\Property(property="valid_from", type="string", format="date"),
 *     @OA\Property(property="valid_to", type="string", format="date", nullable=true)
 *   ),
 *   @OA\Schema(
 *     schema="Batch",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="ingredient_id", type="integer"),
 *     @OA\Property(property="quantity", type="number", format="float"),
 *     @OA\Property(property="received_at", type="string", format="date"),
 *     @OA\Property(property="expires_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="unit_cost", type="number", format="float")
 *   ),
 *   @OA\Schema(
 *     schema="RecipeItem",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="recipe_id", type="integer"),
 *     @OA\Property(property="ingredient_id", type="integer"),
 *     @OA\Property(property="quantity", type="number", format="float"),
 *     @OA\Property(property="notes", type="string", nullable=true)
 *   ),
 *   @OA\Schema(
 *     schema="Recipe",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="dish_id", type="integer"),
 *     @OA\Property(property="version", type="string"),
 *     @OA\Property(property="is_active", type="boolean")
 *   ),
 *   @OA\Schema(
 *     schema="Dish",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="sku", type="string", nullable=true),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="is_active", type="boolean")
 *   ),
 *   @OA\Schema(
 *     schema="SaleItem",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="sale_id", type="integer"),
 *     @OA\Property(property="dish_id", type="integer"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="unit_price", type="number", format="float"),
 *     @OA\Property(property="total_price", type="number", format="float")
 *   ),
 *   @OA\Schema(
 *     schema="Sale",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="sold_at", type="string", format="date-time"),
 *     @OA\Property(property="channel", type="string"),
 *     @OA\Property(property="subtotal", type="number", format="float"),
 *     @OA\Property(property="discount", type="number", format="float"),
 *     @OA\Property(property="tax", type="number", format="float"),
 *     @OA\Property(property="total", type="number", format="float")
 *   ),
 *   @OA\Schema(
 *     schema="IngredientRequest",
 *     required={"name", "unit"},
 *     @OA\Property(property="name", type="string", example="Farinha de trigo"),
 *     @OA\Property(property="unit", type="string", example="kg"),
 *     @OA\Property(property="is_perishable", type="boolean", example=false),
 *     @OA\Property(property="shelf_life_days", type="integer", nullable=true, example=30),
 *     @OA\Property(property="min_stock", type="number", format="float", nullable=true, example=10.5)
 *   ),
 *   @OA\Schema(
 *     schema="SupplierRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", example="Fornecedor ABC"),
 *     @OA\Property(property="contact_name", type="string", nullable=true, example="João Silva"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="contato@fornecedor.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="(11) 99999-9999"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Entrega apenas às terças")
 *   ),
 *   @OA\Schema(
 *     schema="IngredientPriceRequest",
 *     required={"ingredient_id", "supplier_id", "price", "purchase_unit_quantity", "valid_from"},
 *     @OA\Property(property="ingredient_id", type="integer", example=1),
 *     @OA\Property(property="supplier_id", type="integer", example=1),
 *     @OA\Property(property="price", type="number", format="float", example=25.50),
 *     @OA\Property(property="purchase_unit_quantity", type="number", format="float", example=1.0),
 *     @OA\Property(property="purchase_unit", type="string", nullable=true, example="saco"),
 *     @OA\Property(property="valid_from", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="valid_to", type="string", format="date", nullable=true, example="2025-12-31")
 *   ),
 *   @OA\Schema(
 *     schema="BatchRequest",
 *     required={"ingredient_id", "quantity", "received_at", "unit_cost"},
 *     @OA\Property(property="ingredient_id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="number", format="float", example=50.0),
 *     @OA\Property(property="received_at", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="expires_at", type="string", format="date", nullable=true, example="2025-02-15"),
 *     @OA\Property(property="unit_cost", type="number", format="float", example=2.50)
 *   ),
 *   @OA\Schema(
 *     schema="DishRequest",
 *     required={"name", "price"},
 *     @OA\Property(property="name", type="string", example="Hambúrguer Artesanal"),
 *     @OA\Property(property="sku", type="string", nullable=true, example="HAMB-001"),
 *     @OA\Property(property="price", type="number", format="float", example=29.90),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 *   ),
 *   @OA\Schema(
 *     schema="RecipeRequest",
 *     required={"dish_id"},
 *     @OA\Property(property="dish_id", type="integer", example=1),
 *     @OA\Property(property="version", type="string", nullable=true, example="v1.0"),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 *   ),
 *   @OA\Schema(
 *     schema="RecipeItemRequest",
 *     required={"recipe_id", "ingredient_id", "quantity"},
 *     @OA\Property(property="recipe_id", type="integer", example=1),
 *     @OA\Property(property="ingredient_id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="number", format="float", example=0.2),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Peneirar antes de usar")
 *   ),
 *   @OA\Schema(
 *     schema="SaleRequest",
 *     required={"sold_at", "items"},
 *     @OA\Property(property="sold_at", type="string", format="date-time", example="2025-01-15T12:30:00"),
 *     @OA\Property(property="channel", type="string", nullable=true, example="pos"),
 *     @OA\Property(property="items", type="array",
 *       @OA\Items(
 *         required={"dish_id", "quantity"},
 *         @OA\Property(property="dish_id", type="integer", example=1),
 *         @OA\Property(property="quantity", type="integer", example=2),
 *         @OA\Property(property="unit_price", type="number", format="float", nullable=true, example=29.90)
 *       )
 *     )
 *   ),
 *   @OA\Schema(
 *     schema="PaginatedResponse",
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="current_page", type="integer"),
 *     @OA\Property(property="last_page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer")
 *   )
 * )
 */
class Annotations
{
    // intentionally empty; used only for OpenAPI annotations
}


