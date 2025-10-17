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
 *   url="/",
 *   description="Servidor local"
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
 *   )
 * )
 */
class Annotations
{
    // intentionally empty; used only for OpenAPI annotations
}


