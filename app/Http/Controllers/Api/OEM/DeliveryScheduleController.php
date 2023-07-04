<?php

namespace App\Http\Controllers\Api\OEM;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use App\Http\Controllers\Api\Helpers\QueryController;
use Illuminate\Http\JsonResponse;

class DeliveryScheduleController extends Controller
{
    private $deliverySchedule;
    public function index(Request $request): JsonResponse
    {
        $start      = $request->get('start');
        $end        = $request->get('end');
        $warehouse  = $request->get('warehouse_id');
        $customer   = $request->get('customer_id');


        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'warehouse_id'  => 'required|exists:warehouse,warehouse_id',
            'customer_id'   => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->deliverySchedule], 200);
    }

    public function show(Request $request): JsonResponse
    {
        $product_customer   = $request->get('product_customer_id');
        $warehouse          = $request->get('warehouse_id');
        $date               = $request->get('schedule_date');


        $validator = Validator::make($request->all(), [
            'schedule_date'         => 'required|date_format:Y-m-d',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->deliverySchedule = DB::table('precise.oem_delivery_schedule as ods')
            ->where('ods.product_customer_id', $product_customer)
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

    public function check(Request $request): JsonResponse
    {
        $product_customer   = $request->get('product_customer_id');
        $warehouse          = $request->get('warehouse_id');
        $date               = $request->get('schedule_date');


        $validator = Validator::make($request->all(), [
            'schedule_date'         => 'required|date_format:Y-m-d',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->deliverySchedule = DB::table('precise.oem_delivery_schedule')
                ->where('product_customer_id', $product_customer)
                ->where('warehouse_id', $warehouse)
                ->where('schedule_date', $date)
                ->select(
                    "product_customer_id"
                )
                ->count();

            if ($this->deliverySchedule == 0)
                return response()->json(['status' => 'error', 'message' => $this->deliverySchedule], 404);
            return response()->json(['status' => 'ok', 'message' => $this->deliverySchedule], 200);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->json()->all();

            foreach ($data as $d) {
                $validator = Validator::make($request->all(), [
                    'product_customer_id'   => 'required|exists:product_customer,product_customer_id',
                    'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
                    'schedule_date'         => 'required|date_format:Y-m-d',
                    'schedule_qty'          => 'required|numeric',
                    'created_by'            => 'required'
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }
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

            if ($this->deliverySchedule == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {

        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'schedule_date'         => 'required|date_format:Y-m-d',
            'warehouse'             => 'required|exists:warehouse,warehouse_id',
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id'
        ]);
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete", $data);
            $this->deliverySchedule = DB::table("precise.oem_delivery_schedule")
                ->where("warehouse_id", $data["warehouse_id"])
                ->where("schedule_date", $data["schedule_date"])
                ->where("product_customer_id", $data["product_customer_id"])
                ->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
