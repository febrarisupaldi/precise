<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockMutationController extends Controller
{
    private $stock;
    public function index($warehouse, Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->stock = DB::select('call precise.warehouse_get_stock_mutation(?,?,?)', array($start, $end, $warehouse));
        if (count($this->stock) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->stock, code: 200);
    }

    public function getStockCard($warehouseID, $productID, Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->stock = DB::select('call precise.warehouse_get_stock_card(?,?,?,?)', array($start, $end, $warehouseID, $productID));
        if (count($this->stock) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->stock, code: 200);
    }
}
