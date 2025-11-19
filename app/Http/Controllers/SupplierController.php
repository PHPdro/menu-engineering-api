<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/suppliers",
     *   tags={"Suppliers"},
     *   summary="Listar fornecedores",
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de fornecedores",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Supplier"))
     *         )
     *       }
     *     )
     *   )
     * )
     */
    public function index()
    {
        return Supplier::query()->orderBy('name')->paginate(10);
    }

    /**
     * @OA\Post(
     *   path="/api/suppliers",
     *   tags={"Suppliers"},
     *   summary="Criar fornecedor",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/SupplierRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Fornecedor criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Supplier")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
        ]);
        $supplier = Supplier::create($data);
        return response()->json($supplier, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/suppliers/{id}",
     *   tags={"Suppliers"},
     *   summary="Obter detalhes do fornecedor",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do fornecedor",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do fornecedor",
     *     @OA\JsonContent(ref="#/components/schemas/Supplier")
     *   ),
     *   @OA\Response(response=404, description="Fornecedor não encontrado")
     * )
     */
    public function show(Supplier $supplier)
    {
        return $supplier->load('ingredientPrices.ingredient');
    }

    /**
     * @OA\Put(
     *   path="/api/suppliers/{id}",
     *   tags={"Suppliers"},
     *   summary="Atualizar fornecedor",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do fornecedor",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/SupplierRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Fornecedor atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Supplier")
     *   ),
     *   @OA\Response(response=404, description="Fornecedor não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
        ]);
        $supplier->update($data);
        return $supplier;
    }

    /**
     * @OA\Delete(
     *   path="/api/suppliers/{id}",
     *   tags={"Suppliers"},
     *   summary="Excluir fornecedor",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do fornecedor",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Fornecedor excluído com sucesso"),
     *   @OA\Response(response=404, description="Fornecedor não encontrado")
     * )
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->noContent();
    }
}


