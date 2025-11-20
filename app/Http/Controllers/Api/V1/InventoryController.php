<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,variant',
            'id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reason' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventoriable = $this->getInventoriable($request->type, $request->id);

        if (!$inventoriable) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        try {
            $this->inventoryService->addStock($inventoriable, $request->quantity, $request->reason);

            return response()->json([
                'message' => 'Stock added successfully',
                'data' => $inventoriable->fresh(['inventory']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deductStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,variant',
            'id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reason' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventoriable = $this->getInventoriable($request->type, $request->id);

        if (!$inventoriable) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        try {
            $this->inventoryService->deductStock($inventoriable, $request->quantity, $request->reason);

            return response()->json([
                'message' => 'Stock deducted successfully',
                'data' => $inventoriable->fresh(['inventory']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private function getInventoriable(string $type, int $id)
    {
        return $type === 'product' 
            ? Product::find($id) 
            : ProductVariant::find($id);
    }
}
