<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/batches",
     *   tags={"Batches"},
     *   summary="Listar lotes",
     *   @OA\Parameter(
     *     name="ingredient_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de lotes",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Batch"))
     *         )
     *       }
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Batch::with('ingredient')->orderBy('expires_at');
        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->integer('ingredient_id'));
        }
        return $query->paginate(50);
    }

    /**
     * @OA\Post(
     *   path="/api/batches",
     *   tags={"Batches"},
     *   summary="Criar lote",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/BatchRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Lote criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Batch")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.001',
            'received_at' => 'required|date',
            'expires_at' => 'nullable|date|after_or_equal:received_at',
            'unit_cost' => 'required|numeric|min:0',
        ]);
        $batch = Batch::create($data);
        return response()->json($batch->load('ingredient'), 201);
    }

    /**
     * @OA\Get(
     *   path="/api/batches/{id}",
     *   tags={"Batches"},
     *   summary="Obter detalhes do lote",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do lote",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do lote",
     *     @OA\JsonContent(ref="#/components/schemas/Batch")
     *   ),
     *   @OA\Response(response=404, description="Lote não encontrado")
     * )
     */
    public function show(Batch $batch)
    {
        return $batch->load('ingredient');
    }

    /**
     * @OA\Put(
     *   path="/api/batches/{id}",
     *   tags={"Batches"},
     *   summary="Atualizar lote",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do lote",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/BatchRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lote atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Batch")
     *   ),
     *   @OA\Response(response=404, description="Lote não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'quantity' => 'sometimes|numeric|min:0',
            'received_at' => 'sometimes|date',
            'expires_at' => 'nullable|date',
            'unit_cost' => 'sometimes|numeric|min:0',
        ]);
        $batch->update($data);
        return $batch->load('ingredient');
    }

    /**
     * @OA\Delete(
     *   path="/api/batches/{id}",
     *   tags={"Batches"},
     *   summary="Excluir lote",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do lote",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Lote excluído com sucesso"),
     *   @OA\Response(response=404, description="Lote não encontrado")
     * )
     */
    public function destroy(Batch $batch)
    {
        $batch->delete();
        return response()->noContent();
    }
}


