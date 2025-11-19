<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Dish;
use App\Models\RecipeItem;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/sales",
     *   tags={"Sales"},
     *   summary="Listar vendas",
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de vendas",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Sale"))
     *         )
     *       }
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Sale::with('items.dish')->orderByDesc('sold_at');
        return $query->paginate(10);
    }

    /**
     * @OA\Get(
     *   path="/api/sales/{id}",
     *   tags={"Sales"},
     *   summary="Obter detalhes da venda",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da venda",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes da venda",
     *     @OA\JsonContent(ref="#/components/schemas/Sale")
     *   ),
     *   @OA\Response(response=404, description="Venda não encontrada")
     * )
     */
    public function show(Sale $sale)
    {
        return $sale->load('items.dish');
    }

    /**
     * @OA\Post(
     *   path="/api/sales",
     *   tags={"Sales"},
     *   summary="Criar venda e baixar estoque por FIFO",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/SaleRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Venda criada com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Sale")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação / estoque insuficiente")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sold_at' => 'required|date',
            'channel' => 'nullable|string|max:64',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'sold_at' => $data['sold_at'],
                'channel' => $data['channel'] ?? 'pos',
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $line) {
                $dish = Dish::findOrFail($line['dish_id']);
                $quantity = (int) $line['quantity'];
                $unitPrice = $line['unit_price'] ?? (float) $dish->price;
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'dish_id' => $dish->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);

                // Consume stock by recipe (FIFO on batches by earliest expiry/received)
                $recipe = $dish->recipe()->with('items')->first();
                if (!$recipe) {
                    throw ValidationException::withMessages(['items' => "Dish {$dish->name} does not have an active recipe."]);
                }
                foreach ($recipe->items as $recipeItem) {
                    $consumeQty = (float) $recipeItem->quantity * $quantity; // total needed in base unit

                    $batches = Batch::where('ingredient_id', $recipeItem->ingredient_id)
                        ->where('quantity', '>', 0)
                        ->orderByRaw('COALESCE(expires_at, received_at) asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($consumeQty <= 0) break;
                        $available = (float) $batch->quantity;
                        if ($available <= 0) continue;
                        $used = min($available, $consumeQty);
                        $batch->quantity = $available - $used;
                        $batch->save();
                        $consumeQty -= $used;
                    }

                    if ($consumeQty > 1e-6) {
                        throw ValidationException::withMessages(['stock' => 'Insufficient stock for ingredient ID ' . $recipeItem->ingredient_id]);
                    }
                }
            }

            $sale->subtotal = $subtotal;
            $sale->total = $subtotal; // simplistic; taxes/discounts can be added later
            $sale->save();

            return $sale->load('items.dish');
        });
    }
}


