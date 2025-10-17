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
     * @OA\Get(path="/api/analytics/menu-matrix", tags={"Analytics"}, @OA\Response(response=200, description="Matriz Popularidade x Rentabilidade"))
     */
    // Popularity vs Profitability matrix
    public function menuMatrix(Request $request)
    {
        $periodStart = $request->date('start', now()->subDays(30));
        $periodEnd = $request->date('end', now());

        $sales = SaleItem::select('dish_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as revenue'))
            ->whereHas('sale', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('sold_at', [$periodStart, $periodEnd]);
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
     * @OA\Get(path="/api/analytics/perishables-alerts", tags={"Analytics"}, @OA\Response(response=200, description="Alertas de perecíveis"))
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
                'batch_id' => $batch->id,
                'quantity' => (float)$batch->quantity,
                'expires_at' => $batch->expires_at,
                'forecast_use_until_expiry' => round($expectedUse, 3),
            ];
        }
        return $alerts;
    }

    /**
     * @OA\Get(path="/api/analytics/price-trends", tags={"Analytics"}, @OA\Response(response=200, description="Tendências de preço por fornecedor"))
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
     * @OA\Get(path="/api/analytics/traffic-flow", tags={"Analytics"}, @OA\Response(response=200, description="Fluxo por hora e dia da semana"))
     */
    // Traffic flow by hour and weekday
    public function trafficFlow(Request $request)
    {
        $start = $request->date('start', now()->subDays(30));
        $end = $request->date('end', now());
        $rows = DB::table('sales')
            ->selectRaw('strftime("%w", sold_at) as weekday, strftime("%H", sold_at) as hour, SUM(total) as revenue, COUNT(*) as sales')
            ->whereBetween('sold_at', [$start, $end])
            ->groupByRaw('weekday, hour')
            ->orderByRaw('weekday, hour')
            ->get();
        return $rows;
    }

    /**
     * @OA\Get(path="/api/analytics/breakeven", tags={"Analytics"}, @OA\Response(response=200, description="Ponto de equilíbrio diário"))
     */
    // Daily breakeven (simple input for fixed cost and COGS ratio)
    public function breakeven(Request $request)
    {
        $date = $request->date('date', now());
        $fixedCost = (float) $request->input('fixed_cost', 2000.0);
        $revenue = (float) DB::table('sales')
            ->whereDate('sold_at', $date)
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


