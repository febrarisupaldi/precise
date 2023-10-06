<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ProductionStorageController extends Controller
{
    private $productionStorage;
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trans_number'  => 'required|exists:warehouse_trans_hd,trans_number',
            'rack_number'   => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productionStorage = DB::table("precise.warehouse_trans_hd")
            ->where('trans_number', $request->inventory_number)
            ->update([
                'trans_description' => DB::raw("concat(ifnull(trans_description,''), ' ', $request->rack_number)")
            ]);

        if ($this->productionStorage == 0)
            return ResponseController::json(status: "error", message: "error update data", code: 500);

        return ResponseController::json(status: "ok", message: "success update data", code: 200);
    }
}
