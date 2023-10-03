<?php

namespace App\Http\Controllers\Api\OEM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class MaterialBalanceController extends Controller
{
    private $materialBalance;

    /**
     * modified route api
     * 
     */
    public function stockCard($material, Request $request): JsonResponse
    {
        $start    = $request->get('start');
        $end      = $request->get('end');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->materialBalance = DB::connection('mysql2')->select(
            'call precise.oem_get_material_stock_card(:start,:end,:material)',
            [
                'start' => $start,
                'end'   => $end,
                'material' => $material
            ]
        );
        if (count($this->materialBalance) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialBalance, code: 200);
    }

    /**
     * modified route api
     * 
     */
    public function stockMutation($customer, Request $request): JsonResponse
    {
        $start    = $request->get('start');
        $end      = $request->get('end');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->materialBalance = DB::select(
            'call precise.oem_get_material_stock_mutation(:start,:end,:customer)',
            [
                'start'     => $start,
                'end'       => $end,
                'customer'  => $customer
            ]
        );
        if (count($this->materialBalance) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialBalance, code: 200);
    }
}
