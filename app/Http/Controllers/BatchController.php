<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    /**
     * @OA\Get(path="/api/batches", tags={"Batches"}, @OA\Response(response=200, description="Lista"))
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
     * @OA\Post(path="/api/batches", tags={"Batches"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
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
     * @OA\Get(path="/api/batches/{id}", tags={"Batches"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(Batch $batch)
    {
        return $batch->load('ingredient');
    }

    /**
     * @OA\Put(path="/api/batches/{id}", tags={"Batches"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
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
     * @OA\Delete(path="/api/batches/{id}", tags={"Batches"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(Batch $batch)
    {
        $batch->delete();
        return response()->noContent();
    }
}


