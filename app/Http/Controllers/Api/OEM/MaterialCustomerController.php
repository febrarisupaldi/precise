<?php

namespace App\Http\Controllers\Api\OEM;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class MaterialCustomerController extends Controller
{
    private $materialCustomer;
    public function index(): JsonResponse
    {
        $this->materialCustomer = DB::table('precise.material_customer_hd as hd')
            ->select(
                'material_customer_hd_id',
                'product_code',
                'product_name',
                'customer_code',
                'customer_name',
                DB::raw("
                    case hd.is_active 
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'Status aktif'
                "),
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )->leftJoin('precise.product as prod', 'hd.material_id', '=', 'prod.product_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->get();
        if (count($this->materialCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialCustomer, code: 200);
    }

    /**
     * modified route api
     * 
     */
    public function showMaterial($material): JsonResponse
    {
        $this->materialCustomer = DB::table('precise.material_customer_hd as hd')
            ->where('material_id', $material)
            ->select(
                'material_id',
                'hd.customer_id',
                'cust.customer_code',
                'cust.customer_name'
            )->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->get();
        if (count($this->materialCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialCustomer, code: 200);
    }

    public function detail(): JsonResponse
    {
        $this->materialCustomer = DB::table('precise.material_customer_hd as hd')
            ->select(
                'hd.material_customer_hd_id',
                'prod1.product_code',
                'prod1.product_name',
                'customer_code',
                'customer_name',
                'prod2.product_code',
                'prod2.product_name',
                DB::raw(
                    "case hd.is_active
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'Status aktif'"
                ),
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.material_customer_dt as dt', 'hd.material_customer_hd_id', '=', 'dt.material_customer_hd_id')
            ->leftJoin('precise.product_customer as pc', 'dt.product_customer_id', '=', 'pc.product_customer_id')
            ->leftJoin('precise.product as prod1', 'hd.material_id', '=', 'prod1.product_id')
            ->leftJoin('precise.product as prod2', 'pc.product_id', '=', 'prod2.product_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->get();

        if (count($this->materialCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialCustomer, code: 200);
    }

    public function showProductCustomer($id): JsonResponse
    {
        $this->materialCustomer = DB::table('precise.material_customer_dt as dt')
            ->where('dt.product_customer_id', $id)
            ->select(
                'dt.product_customer_id',
                'prod.product_id',
                'prod.product_code',
                'prod.product_name'
            )
            ->leftJoin('precise.material_customer_hd as hd', 'hd.material_customer_hd_id', '=', 'dt.material_customer_hd_id')
            ->leftJoin('precise.product as prod', 'hd.material_id', '=', 'prod.product_id')
            ->get();
        if (count($this->materialCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialCustomer, code: 200);
    }

    public function showCustomer($id): JsonResponse
    {
        $this->materialCustomer = DB::table('precise.material_customer_hd as hd')
            ->where('hd.customer_id', $id)
            ->select(
                'material_customer_hd_id',
                'material_id',
                'product_code as Kode material',
                'product_name as Nama material',
                'prod.uom_code as UOM',
                DB::raw("
                case hd.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'Status aktif'
            ")
            )
            ->leftJoin('precise.product as prod', 'hd.material_id', '=', 'prod.product_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->get();

        if (count($this->materialCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->materialCustomer, code: 200);
    }

    public function show($id): JsonResponse
    {
        $master = DB::table('precise.material_customer_hd as hd')
            ->where('material_customer_hd_id', $id)
            ->select(
                'material_customer_hd_id',
                'material_id',
                'product_code',
                'product_name',
                'hd.customer_id',
                'customer_code',
                'customer_name',
                'hd.is_active',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.product as prod', 'hd.material_id', '=', 'prod.product_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->first();
        if (empty($master))
            return response()->json("not found", 404);

        $detail = DB::table('precise.material_customer_dt as dt')
            ->where('material_customer_hd_id', $master->material_customer_hd_id)
            ->select(
                'material_customer_dt_id',
                'material_customer_hd_id',
                'dt.product_customer_id',
                'product_code',
                'product_name',
                'dt.is_active',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.product_customer as pc', 'dt.product_customer_id', '=', 'pc.product_customer_id')
            ->leftJoin('precise.product as prod', 'pc.product_id', '=', 'prod.product_id')
            ->get();

        $this->materialCustomer =
            array_merge_recursive(
                (array)$master,
                array("detail" => $detail)
            );

        return response()->json($this->materialCustomer, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'material_id'                   => 'required|exists:product,product_id',
            'customer_id'                   => 'required|exists:customer,customer_id',
            'created_by'                    => 'required',
            'detail.*.product_customer_id'  => 'required|exists:product_customer,product_customer_id',
            'detail.*.created_by'           => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            $id = DB::table('precise.material_customer_hd')
                ->insertGetId([
                    'material_id' => $data['material_id'],
                    'customer_id' => $data['customer_id'],
                    'created_by'  => $data['created_by']
                ]);

            if ($id == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }
            foreach ($data['detail'] as $d) {
                $dt[] = [
                    'material_customer_hd_id' => $id,
                    'product_customer_id'     => $d['product_customer_id'],
                    'created_by'              => $d['created_by']
                ];
            }
            $check = DB::table('precise.material_customer_dt')
                ->insert($dt);

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'material_customer_hd_id'   => 'required|exists:material_customer_hd,material_customer_hd_id',
            'material_id'               => 'required|exists:product,product_id',
            'customer_id'               => 'required|exists:customer,customer_id',
            'updated_by'                => 'required',
            'reason'                    => 'required',
            'is_active'                 => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            DB::table('precise.material_customer_hd')
                ->where('material_customer_hd_id', $data['material_customer_hd_id'])
                ->update([
                    'material_id' => $data['material_id'],
                    'customer_id' => $data['customer_id'],
                    'is_active'   => $data['is_active'],
                    'updated_by'  => $data['updated_by']
                ]);

            if ($data['inserted'] != null) {
                $validator = Validator::make($data, [
                    'inserted.*.material_customer_hd_id'    => 'required|exists:material_customer_hd,material_customer_hd_id',
                    'inserted.*.product_customer_id'        => 'required|exists:product_customer,product_customer_id',
                    'inserted.*.created_by'                 => 'required'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }
                foreach ($data['inserted'] as $d) {
                    $dt[] = [
                        'material_customer_hd_id' => $d['material_customer_hd_id'],
                        'product_customer_id'     => $d['product_customer_id'],
                        'created_by'              => $d['created_by']
                    ];
                }
                $check = DB::table('precise.material_customer_dt')
                    ->insert($dt);

                if ($check == 0) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }

            if ($data['updated'] != null) {

                $validator = Validator::make($data, [
                    'updated.*.material_customer_dt_id'    => 'required|exists:material_customer_dt,material_customer_dt_id',
                    'updated.*.material_customer_hd_id'    => 'required|exists:material_customer_hd,material_customer_hd_id',
                    'updated.*.product_customer_id'        => 'required|exists:product_customer,product_customer_id',
                    'updated.*.created_by'                 => 'required'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['updated'] as $d) {
                    $check = DB::table('precise.material_customer_dt')
                        ->where('material_customer_dt_id', $d['material_customer_dt_id'])
                        ->update([
                            'material_customer_hd_id' => $d['material_customer_hd_id'],
                            'product_customer_id'     => $d['product_customer_id'],
                            'updated_by'              => $d['updated_by']
                        ]);

                    if ($check == 0) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: "server error", code: 500);
                    }
                }
            }

            if ($data['deleted'] != null) {

                $validator = Validator::make($data, [
                    'deleted.*.material_customer_dt_id'    => 'required|exists:material_customer_dt,material_customer_dt_id',
                ]);
                if ($validator->fails()) {
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['deleted'] as $del) {
                    $delete[] = $del['material_customer_dt_id'];
                }
                $check = DB::table('precise.material_customer_dt')
                    ->whereIn('material_customer_dt_id', $delete)
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
