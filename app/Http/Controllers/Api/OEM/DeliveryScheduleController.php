<?php

namespace App\Http\Controllers\Api\OEM;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use App\Http\Controllers\Api\Helpers\QueryController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class DeliveryScheduleController extends Controller
{
    private $deliverySchedule;
    public function index($warehouse, $customer, Request $request): JsonResponse
    {
        $start      = $request->get('start', date("Y-m-d"));
        $end        = $request->get('end', date("Y-m-d"));

        $validator = Validator::make($request->all(), [
            'start'         => 'required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $customer = str_replace('-', ',', $customer);
        $this->deliverySchedule = DB::select(
            'call precise.oem_get_delivery_schedule(:start,:end,:warehouse,:customer)',
            [
                'start'     => $start,
                'end'       => $end,
                'warehouse' => $warehouse,
                'customer'  => $customer
            ]
        );
        if (count($this->deliverySchedule) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliverySchedule, code: 200);
    }

    public function show($productCustomer, $warehouse, Request $request): JsonResponse
    {
        $date               = $request->get('schedule_date');
        $validator = Validator::make($request->all(), [
            'schedule_date'         => 'required|date_format:Y-m-d'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->deliverySchedule = DB::table('precise.oem_delivery_schedule', 'ods')
            ->where('ods.product_customer_id', $productCustomer)
            ->where('ods.warehouse_id', $warehouse)
            ->where('schedule_date', $date)
            ->select(
                'oem_delivery_schedule_id',
                'ods.product_customer_id',
                'pc.product_id',
                'p.product_code',
                'p.product_name',
                'ods.warehouse_id',
                'w.warehouse_code',
                'w.warehouse_name',
                'pc.customer_id',
                'c.customer_code',
                'c.customer_name',
                'schedule_date',
                'schedule_qty'
            )
            ->leftJoin('precise.product_customer as pc', 'ods.product_customer_id', '=', 'pc.product_customer_id')
            ->leftJoin('precise.product as p', 'pc.product_id', '=', 'p.product_id')
            ->leftJoin('precise.warehouse as w', 'ods.warehouse_id', '=', 'w.warehouse_id')
            ->leftJoin('precise.customer as c', 'pc.customer_id', '=', 'c.customer_id')
            ->get();
        if (count($this->deliverySchedule) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->deliverySchedule], 200);
    }

    public function check($productCustomer, $warehouse, Request $request): JsonResponse
    {
        $date   = $request->get('schedule_date');
        $validator = Validator::make($request->all(), [
            'schedule_date'         => 'required|date_format:Y-m-d'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->deliverySchedule = DB::table('precise.oem_delivery_schedule')
            ->where('product_customer_id', $productCustomer)
            ->where('warehouse_id', $warehouse)
            ->where('schedule_date', $date)
            ->select(
                "product_customer_id"
            )
            ->count();

        if ($this->deliverySchedule == 0)
            return ResponseController::json(status: "error", message: $this->deliverySchedule, code: 404);
        return ResponseController::json(status: "ok", message: $this->deliverySchedule, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        //different route api
        try {
            $data = $request->json()->all();
            $validator = Validator::make($data['data'], [
                '*.product_customer_id'   => 'required|exists:product_customer,product_customer_id',
                '*.warehouse_id'          => 'required|exists:warehouse,warehouse_id',
                '*.schedule_date'         => 'required|date_format:Y-m-d',
                '*.schedule_qty'          => 'required|numeric',
                '*.created_by'            => 'required'
            ]);

            if ($validator->fails()) {
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }
            foreach ($data as $d) {

                $dt[] = [
                    'product_customer_id'   => $d['product_customer_id'],
                    'warehouse_id'          => $d['warehouse_id'],
                    'schedule_date'         => $d['schedule_date'],
                    'schedule_qty'          => $d['schedule_qty'],
                    'created_by'            => $d['created_by']
                ];
            }

            $this->deliverySchedule = DB::table('precise.oem_delivery_schedule')
                ->insert($dt);

            if ($this->deliverySchedule == 0)
                return ResponseController::json(status: "error", message: "failed input data", code: 500);

            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schedule_qty'              => 'required',
            'updated_by'                => 'required',
            'oem_delivery_schedule_id'  => 'required|exists:oem_delivery_schedule,oem_delivery_schedule_id'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DBController::reason($request, "update");
        try {
            $this->deliverySchedule = DB::table("precise.oem_delivery_schedule")
                ->where('oem_delivery_schedule_id', $request->oem_delivery_schedule_id)
                ->update([
                    'schedule_qty'  => $request->schedule_qty,
                    'updated_by'    => $request->updated_by
                ]);

            if ($this->deliverySchedule == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "failed update data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    //modified route api
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'schedule_date'         => 'required|date_format:Y-m-d',
            'warehouse'             => 'required|exists:warehouse,warehouse_id',
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id',
            "reason"                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete", $data);
            $this->deliverySchedule = DB::table("precise.oem_delivery_schedule")
                ->where("warehouse_id", $data["warehouse_id"])
                ->where("schedule_date", $data["schedule_date"])
                ->where("product_customer_id", $data["product_customer_id"])
                ->delete();

            if ($this->deliverySchedule == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
