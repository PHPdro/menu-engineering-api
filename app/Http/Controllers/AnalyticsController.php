<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\SaleItem;
use App\Models\Batch;
use App\Models\IngredientPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/analytics/menu-matrix",
     *   tags={"Analytics"},
     *   summary="Matriz de Popularidade x Rentabilidade",
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=false,
     *     description="Data inicial (padrão: 30 dias atrás)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=false,
     *     description="Data final (padrão: hoje)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Matriz de popularidade x rentabilidade",
     *     @OA\JsonContent(
     *       @OA\Property(property="thresholds", type="object"),
     *       @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *     )
     *   )
     * )
     */
    // Popularity vs Profitability matrix
    public function menuMatrix(Request $request)
    {
        // Valida e parseia as datas com tratamento de erro
        try {
            $periodStart = $request->filled('start') 
                ? Carbon::parse($request->input('start'))->startOfDay()
                : now()->subDays(30)->startOfDay();
            
            $periodEnd = $request->filled('end')
                ? Carbon::parse($request->input('end'))->endOfDay()
                : now()->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Formato de data inválido. Use o formato YYYY-MM-DD (ex: 2025-11-01)'
            ], 422);
        }
        
        // Valida que start não seja maior que end
        if ($periodStart->gt($periodEnd)) {
            return response()->json([
                'error' => 'A data inicial não pode ser maior que a data final'
            ], 422);
        }

        $sales = SaleItem::select('dish_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as revenue'))
            ->whereHas('sale', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('sold_at', [
                    $periodStart->toDateTimeString(),
                    $periodEnd->toDateTimeString()
                ]);
            })
            ->groupBy('dish_id')
            ->get()
            ->keyBy('dish_id');

        $dishes = Dish::with(['recipe.items'])->get();

        $rows = [];
        foreach ($dishes as $dish) {
            $qty = (int) ($sales[$dish->id]->total_qty ?? 0);
            $revenue = (float) ($sales[$dish->id]->revenue ?? 0);
            $costPerDish = 0.0;
            if ($dish->recipe) {
                foreach ($dish->recipe->items as $item) {
                    $unitCost = self::currentIngredientUnitCost($item->ingredient_id);
                    $costPerDish += $unitCost * (float)$item->quantity;
                }
            }
            $profitPerDish = (float) $dish->price - $costPerDish;
            $profit = $profitPerDish * $qty;
            $rows[] = [
                'dish_id' => $dish->id,
                'name' => $dish->name,
                'qty' => $qty,
                'revenue' => round($revenue, 2),
                'cost_per_dish' => round($costPerDish, 2),
                'profit_per_dish' => round($profitPerDish, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Determine thresholds (medians) for popularity and profitability
        $qtys = array_column($rows, 'qty');
        $profits = array_column($rows, 'profit_per_dish');
        $popThreshold = self::median($qtys);
        $profitThreshold = self::median($profits);

        foreach ($rows as &$r) {
            $popular = $r['qty'] >= $popThreshold;
            $profitable = $r['profit_per_dish'] >= $profitThreshold;
            if ($popular && $profitable) $r['category'] = 1;
            elseif ($popular && !$profitable) $r['category'] = 2;
            elseif (!$popular && $profitable) $r['category'] = 3;
            else $r['category'] = 4;
        }

        return [
            'thresholds' => [
                'popularity_qty' => $popThreshold,
                'profitability_per_dish' => $profitThreshold,
            ],
            'items' => $rows,
        ];
    }

    /**
     * @OA\Get(
     *   path="/api/analytics/menu-matrix-by-category",
     *   tags={"Analytics"},
     *   summary="Matriz de Popularidade x Rentabilidade agrupada por categoria",
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=false,
     *     description="Data inicial (padrão: 30 dias atrás)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=false,
     *     description="Data final (padrão: hoje)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Matriz agrupada por categoria com totais e percentuais",
     *     @OA\JsonContent(
     *       @OA\Property(property="thresholds", type="object"),
     *       @OA\Property(property="total_sales", type="number"),
     *       @OA\Property(property="categories", type="object")
     *     )
     *   )
     * )
     */
    public function menuMatrixByCategory(Request $request)
    {
        // Valida e parseia as datas (mesma lógica do menuMatrix)
        try {
            $periodStart = $request->filled('start') 
                ? Carbon::parse($request->input('start'))->startOfDay()
                : now()->subDays(30)->startOfDay();
            
            $periodEnd = $request->filled('end')
                ? Carbon::parse($request->input('end'))->endOfDay()
                : now()->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Formato de data inválido. Use o formato YYYY-MM-DD (ex: 2025-11-01)'
            ], 422);
        }
        
        // Valida que start não seja maior que end
        if ($periodStart->gt($periodEnd)) {
            return response()->json([
                'error' => 'A data inicial não pode ser maior que a data final'
            ], 422);
        }

        // Reutiliza a lógica de cálculo do menuMatrix
        $sales = SaleItem::select('dish_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as revenue'))
            ->whereHas('sale', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('sold_at', [
                    $periodStart->toDateTimeString(),
                    $periodEnd->toDateTimeString()
                ]);
            })
            ->groupBy('dish_id')
            ->get()
            ->keyBy('dish_id');

        $dishes = Dish::with(['recipe.items'])->get();

        $rows = [];
        foreach ($dishes as $dish) {
            $qty = (int) ($sales[$dish->id]->total_qty ?? 0);
            $revenue = (float) ($sales[$dish->id]->revenue ?? 0);
            $costPerDish = 0.0;
            if ($dish->recipe) {
                foreach ($dish->recipe->items as $item) {
                    $unitCost = self::currentIngredientUnitCost($item->ingredient_id);
                    $costPerDish += $unitCost * (float)$item->quantity;
                }
            }
            $profitPerDish = (float) $dish->price - $costPerDish;
            $profit = $profitPerDish * $qty;
            $rows[] = [
                'dish_id' => $dish->id,
                'name' => $dish->name,
                'qty' => $qty,
                'revenue' => round($revenue, 2),
                'cost_per_dish' => round($costPerDish, 2),
                'profit_per_dish' => round($profitPerDish, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Determine thresholds (medians) for popularity and profitability
        $qtys = array_column($rows, 'qty');
        $profits = array_column($rows, 'profit_per_dish');
        $popThreshold = self::median($qtys);
        $profitThreshold = self::median($profits);

        foreach ($rows as &$r) {
            $popular = $r['qty'] >= $popThreshold;
            $profitable = $r['profit_per_dish'] >= $profitThreshold;
            if ($popular && $profitable) $r['category'] = 1;
            elseif ($popular && !$profitable) $r['category'] = 2;
            elseif (!$popular && $profitable) $r['category'] = 3;
            else $r['category'] = 4;
        }

        $items = $rows;
        $thresholds = [
            'popularity_qty' => $popThreshold,
            'profitability_per_dish' => $profitThreshold,
        ];
        
        // Calcula total de vendas
        $totalSales = array_sum(array_column($items, 'qty'));
        
        // Agrupa por categoria
        $categories = [
            1 => [
                'name' => 'Estrelas',
                'description' => 'Popular e Rentável',
                'color' => '#22c55e',
                'items' => [],
                'total_qty' => 0,
                'total_revenue' => 0.0,
                'percentage' => 0.0,
            ],
            2 => [
                'name' => 'Vacas Leiteiras',
                'description' => 'Popular mas não Rentável',
                'color' => '#f59e0b',
                'items' => [],
                'total_qty' => 0,
                'total_revenue' => 0.0,
                'percentage' => 0.0,
            ],
            3 => [
                'name' => 'Interrogações',
                'description' => 'Rentável mas não Popular',
                'color' => '#3b82f6',
                'items' => [],
                'total_qty' => 0,
                'total_revenue' => 0.0,
                'percentage' => 0.0,
            ],
            4 => [
                'name' => 'Cachorros',
                'description' => 'Nem Popular nem Rentável',
                'color' => '#ef4444',
                'items' => [],
                'total_qty' => 0,
                'total_revenue' => 0.0,
                'percentage' => 0.0,
            ],
        ];
        
        // Agrupa itens por categoria
        foreach ($items as $item) {
            $category = $item['category'];
            
            $categories[$category]['items'][] = [
                'dish_id' => $item['dish_id'],
                'name' => $item['name'],
                'qty' => $item['qty'],
                'revenue' => $item['revenue'],
                'cost_per_dish' => $item['cost_per_dish'],
                'profit_per_dish' => $item['profit_per_dish'],
                'profit' => $item['profit'],
                'percentage' => $totalSales > 0 ? round(($item['qty'] / $totalSales) * 100, 2) : 0.0,
            ];
            
            $categories[$category]['total_qty'] += $item['qty'];
            $categories[$category]['total_revenue'] += $item['revenue'];
        }
        
        // Calcula percentuais por categoria
        foreach ($categories as $key => &$category) {
            $category['percentage'] = $totalSales > 0 
                ? round(($category['total_qty'] / $totalSales) * 100, 2) 
                : 0.0;
        }
        
        return [
            'thresholds' => $thresholds,
            'total_sales' => $totalSales,
            'categories' => $categories,
        ];
    }

    /**
     * @OA\Get(
     *   path="/api/analytics/perishables-alerts",
     *   tags={"Analytics"},
     *   summary="Alertas de perecíveis",
     *   @OA\Parameter(
     *     name="hours",
     *     in="query",
     *     required=false,
     *     description="Horas à frente para verificar expiração (padrão: 48)",
     *     @OA\Schema(type="integer", default=48)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista de alertas de perecíveis",
     *     @OA\JsonContent(type="array", @OA\Items(type="object"))
     *   )
     * )
     */
    // Perishables alert
    public function perishablesAlerts(Request $request)
    {
        $hours = $request->integer('hours', 48);
        $now = now();
        $limit = $now->copy()->addHours($hours);

        $expiring = Batch::with('ingredient')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now->toDateString(), $limit->toDateString()])
            ->where('quantity', '>', 0)
            ->orderBy('expires_at')
            ->get();

        $alerts = [];
        foreach ($expiring as $batch) {
            $ingredientId = $batch->ingredient_id;
            // simple forecast: avg daily usage of last 14 days
            $dailyUsage = self::avgDailyIngredientUsage($ingredientId, 14);
            $hoursToExpire = max(1, $now->diffInHours(Carbon::parse($batch->expires_at)));
            $expectedUse = $dailyUsage * ($hoursToExpire / 24);
            $alerts[] = [
                'ingredient_id' => $ingredientId,
                'ingredient' => $batch->ingredient->name,
                'unit' => $batch->ingredient->unit,
                'batch_id' => $batch->id,
                'quantity' => (float)$batch->quantity,
                'expires_at' => $batch->expires_at,
                'forecast_use_until_expiry' => round($expectedUse, 3),
            ];
        }
        return $alerts;
    }

    /**
     * @OA\Get(
     *   path="/api/analytics/price-trends",
     *   tags={"Analytics"},
     *   summary="Tendências de preço por fornecedor",
     *   @OA\Parameter(
     *     name="ingredient_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Tendências de preço",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/IngredientPrice"))
     *   )
     * )
     */
    // Supplier price trends
    public function priceTrends(Request $request)
    {
        $ingredientId = $request->integer('ingredient_id');
        $prices = IngredientPrice::with('supplier')
            ->when($ingredientId, fn($q) => $q->where('ingredient_id', $ingredientId))
            ->orderBy('ingredient_id')
            ->orderBy('supplier_id')
            ->orderBy('valid_from')
            ->get();
        return $prices;
    }

    /**
     * @OA\Get(
     *   path="/api/analytics/traffic-flow",
     *   tags={"Analytics"},
     *   summary="Fluxo de tráfego por hora e dia da semana",
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=false,
     *     description="Data inicial (padrão: 30 dias atrás)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=false,
     *     description="Data final (padrão: hoje)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Fluxo de tráfego",
     *     @OA\JsonContent(type="array", @OA\Items(type="object"))
     *   )
     * )
     */
    // Traffic flow by hour and weekday
    public function trafficFlow(Request $request)
    {
        // Valida e parseia as datas com tratamento de erro
        try {
            $start = $request->filled('start')
                ? Carbon::parse($request->input('start'))->startOfDay()
                : now()->subDays(30)->startOfDay();
            
            $end = $request->filled('end')
                ? Carbon::parse($request->input('end'))->endOfDay()
                : now()->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Formato de data inválido. Use o formato YYYY-MM-DD (ex: 2025-11-01)'
            ], 422);
        }
        
        // Valida que start não seja maior que end
        if ($start->gt($end)) {
            return response()->json([
                'error' => 'A data inicial não pode ser maior que a data final'
            ], 422);
        }
        try {
            // Usar where com >= e <= em vez de whereBetween para melhor compatibilidade com SQLite
            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            
            // Verificar se a tabela sales existe e tem dados
            $hasSales = DB::table('sales')
                ->where('sold_at', '>=', $startStr)
                ->where('sold_at', '<=', $endStr)
                ->exists();
            
            if (!$hasSales) {
                return [];
            }
            
            // Detectar o driver do banco de dados e usar a sintaxe apropriada
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'pgsql') {
                // PostgreSQL: EXTRACT retorna DOW (0=domingo, 6=sábado) e HOUR (0-23)
                $rows = DB::table('sales')
                    ->selectRaw('EXTRACT(DOW FROM sold_at)::integer as weekday, EXTRACT(HOUR FROM sold_at)::integer as hour, SUM(total) as revenue, COUNT(*) as sales')
                    ->where('sold_at', '>=', $startStr)
                    ->where('sold_at', '<=', $endStr)
                    ->groupByRaw('EXTRACT(DOW FROM sold_at), EXTRACT(HOUR FROM sold_at)')
                    ->orderByRaw('EXTRACT(DOW FROM sold_at), EXTRACT(HOUR FROM sold_at)')
                    ->get();
            } else {
                // SQLite: strftime
                $rows = DB::table('sales')
                    ->selectRaw('strftime("%w", sold_at) as weekday, strftime("%H", sold_at) as hour, SUM(total) as revenue, COUNT(*) as sales')
                    ->where('sold_at', '>=', $startStr)
                    ->where('sold_at', '<=', $endStr)
                    ->groupByRaw('strftime("%w", sold_at), strftime("%H", sold_at)')
                    ->orderByRaw('strftime("%w", sold_at), strftime("%H", sold_at)')
                    ->get();
            }
            
            // Garantir que os valores numéricos sejam retornados como números
            // Se não houver dados, retorna array vazio
            if ($rows->isEmpty()) {
                return [];
            }
            
            return $rows->map(function ($row) {
                return [
                    'weekday' => (string) ($row->weekday ?? '0'),
                    'hour' => str_pad((string) ($row->hour ?? 0), 2, '0', STR_PAD_LEFT),
                    'revenue' => (float) ($row->revenue ?? 0),
                    'sales' => (int) ($row->sales ?? 0),
                ];
            })->values()->toArray();
        } catch (\Illuminate\Database\QueryException $e) {
            // Log completo do erro SQL
            \Log::error('Erro SQL em traffic-flow', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'driver' => DB::connection()->getDriverName(),
                'start' => $startStr,
                'end' => $endStr,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Sempre retornar o erro real para facilitar debug
            return response()->json([
                'error' => 'Erro ao processar dados de tráfego',
                'message' => $e->getMessage(),
                'sql' => config('app.debug') ? $e->getSql() : null,
                'bindings' => config('app.debug') ? $e->getBindings() : null,
                'driver' => DB::connection()->getDriverName(),
            ], 500);
        } catch (\Exception $e) {
            // Log completo do erro geral
            \Log::error('Erro em traffic-flow', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'start' => $startStr,
                'end' => $endStr,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Sempre retornar o erro real para facilitar debug
            return response()->json([
                'error' => 'Erro ao processar dados de tráfego',
                'message' => $e->getMessage(),
                'class' => config('app.debug') ? get_class($e) : null,
                'file' => config('app.debug') ? $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/analytics/breakeven",
     *   tags={"Analytics"},
     *   summary="Ponto de equilíbrio diário",
     *   @OA\Parameter(
     *     name="date",
     *     in="query",
     *     required=false,
     *     description="Data para análise (padrão: hoje)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="fixed_cost",
     *     in="query",
     *     required=false,
     *     description="Custo fixo diário (padrão: 2000.0)",
     *     @OA\Schema(type="number", format="float", default=2000.0)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Análise de ponto de equilíbrio",
     *     @OA\JsonContent(
     *       @OA\Property(property="date", type="string", format="date"),
     *       @OA\Property(property="breakeven", type="number", format="float"),
     *       @OA\Property(property="revenue", type="number", format="float"),
     *       @OA\Property(property="gap", type="number", format="float")
     *     )
     *   )
     * )
     */
    // Daily breakeven (simple input for fixed cost and COGS ratio)
    public function breakeven(Request $request)
    {
        // Valida e parseia a data com tratamento de erro
        try {
            $date = $request->filled('date')
                ? Carbon::parse($request->input('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Formato de data inválido. Use o formato YYYY-MM-DD (ex: 2025-11-01)'
            ], 422);
        }
        $fixedCost = (float) $request->input('fixed_cost', 2000.0);
        $revenue = (float) DB::table('sales')
            ->whereDate('sold_at', $date->toDateString())
            ->sum('total');
        return [
            'date' => $date->toDateString(),
            'breakeven' => round($fixedCost, 2),
            'revenue' => round($revenue, 2),
            'gap' => round($fixedCost - $revenue, 2),
        ];
    }

    private static function currentIngredientUnitCost(int $ingredientId): float
    {
        // derive from most recent batch weighted average as fallback
        $batch = Batch::where('ingredient_id', $ingredientId)
            ->orderByDesc('received_at')
            ->first();
        return $batch?->unit_cost ? (float)$batch->unit_cost : 0.0;
    }

    private static function median(array $values): float
    {
        if (empty($values)) return 0.0;
        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);
        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2;
        }
        return (float) $values[$mid];
    }

    private static function avgDailyIngredientUsage(int $ingredientId, int $days): float
    {
        // approximate via recipe->sale mapping
        $since = now()->subDays($days);
        $usage = DB::table('sale_items as si')
            ->join('recipes as r', 'r.dish_id', '=', 'si.dish_id')
            ->join('recipe_items as ri', 'ri.recipe_id', '=', 'r.id')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('r.is_active', true)
            ->where('ri.ingredient_id', $ingredientId)
            ->where('s.sold_at', '>=', $since)
            ->selectRaw('SUM(si.quantity * ri.quantity) as qty')
            ->value('qty');
        return max(0.0, (float)$usage / max(1, $days));
    }
}


