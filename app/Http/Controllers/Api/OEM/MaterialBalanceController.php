<?php

namespace App\Http\Controllers\Api\OEM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Illuminate\Http\JsonResponse;

class MaterialBalanceController extends Controller
{
    private $materialBalance;
    public function stockCard(Request $request): JsonResponse
    {
        $start    = $request->get('start');
        $end      = $request->get('end');
        $material = $request->get('material_id');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'material_id'   => 'required|exists:product,product_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->materialBalance], 200);
    }

    public function stockMutation(Request $request): JsonResponse
    {
        $start    = $request->get('start');
        $end      = $request->get('end');
        $customer = $request->get('customer_id');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'customer_id'   => 'required|exists:customer,customer_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->materialBalance], 200);
    }
}
