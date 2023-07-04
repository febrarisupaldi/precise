<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BOMController extends Controller
{
    //OK
    private $bom;

    public function index(): JsonResponse
    {
        $this->bom = DB::table('precise.bom_hd as a')
            ->select(
                'a.bom_hd_id',
                'a.bom_code',
                'a.bom_name',
                'a.product_id',
                'b.product_code',
                'b.product_name',
            )
            ->distinct('a.bom_hd_id')
            ->leftJoin('precise.product AS b', 'a.product_id', '=', 'b.product_id')
            ->leftJoin('precise.product_workcenter AS pw', 'a.product_id', '=', 'pw.product_id')
            ->leftJoin('precise.workcenter AS w', 'pw.workcenter_id', '=', 'w.workcenter_id')
            ->get();

        if (count($this->bom) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->bom, code: 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $master = DB::table('precise.bom_hd as a')
                ->where('bom_hd_id', $id)
                ->select(
                    'a.bom_hd_id',
                    'a.bom_code',
                    'a.bom_name',
                    'a.bom_description',
                    'a.product_id',
                    'b.product_code',
                    'b.product_name',
                    'a.product_qty',
                    'a.product_uom',
                    'w.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    'a.is_active',
                    'a.start_date',
                    'a.expired_date',
                    'a.usage_priority',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin('precise.product AS b', 'a.product_id', '=', 'b.product_id')
                ->leftJoin('precise.product_workcenter AS pw', 'a.product_id', '=', 'pw.product_id')
                ->leftJoin('precise.workcenter AS w', 'pw.workcenter_id', '=', 'w.workcenter_id')
                ->first();

            if (empty($master)) {
                return response()->json("not found", 404);
            }

            $detail = DB::table('precise.bom_dt as dt')
                ->where('bom_hd_id', $master->bom_hd_id)
                ->select(
                    'dt.bom_dt_id',
                    'dt.bom_hd_id',
                    'dt.material_id',
                    'p.product_code AS material_code',
                    'p.product_name',
                    'dt.material_qty',
                    'dt.material_uom',
                    'dt.created_on',
                    'dt.created_by',
                    'dt.updated_on',
                    'dt.updated_by'
                )
                ->leftJoin('precise.product as p', 'dt.material_id', '=', 'p.product_id')
                ->get();

            $this->bom =
                array(
                    "bom_hd_id"           => $master->bom_hd_id,
                    "bom_code"            => $master->bom_code,
                    "bom_name"            => $master->bom_name,
                    "bom_description"     => $master->bom_description,
                    "product_id"          => $master->product_id,
                    "product_code"        => $master->product_code,
                    "product_name"        => $master->product_name,
                    "product_qty"         => $master->product_qty,
                    "product_uom"         => $master->product_uom,
                    "workcenter_id"       => $master->workcenter_id,
                    "workcenter_code"     => $master->workcenter_code,
                    "workcenter_name"     => $master->workcenter_name,
                    "is_active"           => $master->is_active,
                    "start_date"          => $master->start_date,
                    "expired_date"        => $master->expired_date,
                    "usage_priority"      => $master->usage_priority,
                    "created_on"          => $master->created_on,
                    "created_by"          => $master->created_by,
                    "updated_on"          => $master->updated_on,
                    "updated_by"          => $master->updated_by,
                    "detail"              => $detail
                );
            return response()->json($this->bom, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function showByWorkcenter($id): JsonResponse
    {
        try {
            $value = explode("-", $id);
            $this->bom = DB::table('precise.bom_hd as a')
                ->select(
                    'a.bom_hd_id',
                    'a.bom_code',
                    'a.bom_name',
                    'a.bom_description',
                    DB::raw("
                        concat(d.workcenter_code,'-',d.workcenter_name) as 'Workcenter'
                    "),
                    'c.product_code',
                    'c.product_name',
                    'a.product_qty',
                    'a.product_uom',
                    DB::raw("
                        case a.is_active 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif'
                        end as 'is_active'
                    "),
                    'a.start_date',
                    'a.expired_date',
                    'a.usage_priority',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin('product_workcenter as b', 'a.product_id', '=', 'b.product_id')
                ->leftJoin('product as c', 'a.product_id', '=', 'c.product_id')
                ->leftJoin('workcenter as d', 'b.workcenter_id', '=', 'd.workcenter_id')
                ->whereIn('b.workcenter_id', $value)
                ->get();

            if (count($this->bom) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->bom, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function showByProduct($id): JsonResponse
    {
        $usage = request('usage-priority');
        $search = request('search');
        if ($usage == true) {
            $this->bom = DB::table('precise.bom_hd')
                ->where('product_id', $id)
                ->select(
                    'bom_hd_id',
                    'bom_code',
                    'bom_name',
                    'usage_priority'
                )
                ->orderBy('usage_priority', 'DESC')
                ->get();
        } else if ($search == true) {
            $sub = DB::table('precise.bom_dt as dt')
                ->where('dt.material_id', $id)
                ->select('dt.bom_hd_id')
                ->groupBy('dt.bom_hd_id');

            $this->bom = DB::table(DB::raw('(' . $sub->toSql() . ') as c'))
                ->select(
                    'a.bom_hd_id',
                    'a.bom_code',
                    'a.bom_name',
                    'a.bom_description',
                    'a.product_id',
                    'b.product_code',
                    'b.product_name',
                    'a.product_qty',
                    'a.product_uom',
                    'w.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    DB::raw("
                case a.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'is_active'
                "),
                    'a.start_date',
                    'a.expired_date',
                    'a.usage_priority',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin('precise.bom_hd AS a', 'c.bom_hd_id', '=', 'a.bom_hd_id')
                ->leftJoin('precise.product AS b', 'a.product_id', '=', 'b.product_id')
                ->leftJoin('precise.product_workcenter AS pw', 'a.product_id', '=', 'pw.product_id')
                ->leftJoin('precise.workcenter AS w', 'pw.workcenter_id', '=', 'w.workcenter_id')
                ->mergeBindings($sub)
                ->get();
        } else {
            $this->bom = DB::table('precise.bom_hd')
                ->where('product_id', $id)
                ->select(
                    'bom_hd_id',
                    'bom_code',
                    'bom_name'
                )
                ->get();
        }
        //get using count
        if (count($this->bom) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bom, code: 200);
    }

    public function showDetailByWorkcenter($id): JsonResponse
    {
        try {
            $workcenter_ids = explode('-', $id);
            $this->bom = DB::table('precise.bom_hd as hd')
                ->whereIn('pw.workcenter_id', $workcenter_ids)
                ->select(
                    'hd.bom_hd_id',
                    'hd.bom_code',
                    'hd.bom_name',
                    'hd.bom_description',
                    'prod1.product_code',
                    'prod1.product_name',
                    'hd.product_qty',
                    'hd.product_uom',
                    'dt.material_id',
                    'prod2.product_code AS material_code',
                    'prod2.product_name AS material_name',
                    'dt.material_qty',
                    'dt.material_uom',
                    DB::raw("
                case hd.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'is_active'
            "),
                    'hd.start_date',
                    'hd.expired_date',
                    'hd.usage_priority',
                    'dt.created_on',
                    'dt.created_by',
                    'dt.updated_on',
                    'dt.updated_by'
                )
                ->leftJoin('precise.bom_dt as dt', 'hd.bom_hd_id', '=', 'dt.bom_hd_id')
                ->leftJoin('precise.product as prod1', 'hd.product_id', '=', 'prod1.product_id')
                ->leftJoin('precise.product as prod2', 'dt.material_id', '=', 'prod2.product_id')
                ->leftJoin('precise.product_workcenter as pw', 'hd.product_id', '=', 'pw.product_id')
                ->get();

            if (count($this->bom) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->bom, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    function showDetailByBOM($id): JsonResponse
    {
        try {
            $this->bom = DB::table("precise.bom_dt as a")
                ->where('bom_hd_id', $id)
                ->select(
                    'a.bom_dt_id',
                    'a.material_id',
                    'b.product_code',
                    'b.product_name',
                    'a.material_qty',
                    'a.material_uom',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.product as b", "a.material_id", "=", "b.product_id")
                ->get();
            return ResponseController::json(status: "ok", data: $this->bom, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'bom_code'           => 'required|unique:bom_hd,bom_code',
            'bom_name'           => 'required',
            'product_id'         => 'required|exists:product,product_id',
            'product_qty'        => 'required|numeric',
            'start_date'         => 'required|date_format:Y-m-d',
            'usage_priority'     => 'required',
            'created_by'         => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {

            $id = DB::table('precise.bom_hd')
                ->insertGetId([
                    'bom_code'          => $data['bom_code'],
                    'bom_name'          => $data['bom_name'],
                    'bom_description'   => $data['bom_description'],
                    'product_id'        => $data['product_id'],
                    'product_qty'       => $data['product_qty'],
                    'product_uom'       => $data['product_uom'],
                    'start_date'        => $data['start_date'],
                    'expired_date'      => $data['expired_date'],
                    'usage_priority'    => $data['usage_priority'],
                    'is_active'         => $data['is_active'],
                    'created_by'        => $data['created_by']
                ]);

            foreach ($data['detail'] as $d) {
                $dt[] = [
                    'bom_hd_id'             => $id,
                    'material_id'           => $d['material_id'],
                    'material_qty'          => $d['material_qty'],
                    'material_uom'          => $d['material_uom'],
                    'created_by'            => $d['created_by']
                ];
            }

            DB::table('precise.bom_dt')
                ->insert($dt);

            $trans = DB::table('precise.bom_hd')
                ->where('bom_hd_id', $id)
                ->select('bom_code')
                ->first();

            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", id: $trans->bom_code, code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'bom_hd_id'          => 'required|exists:bom_hd,bom_hd_id',
            'bom_code'           => 'required',
            'bom_name'           => 'required',
            'product_id'         => 'required|exists:product,product_id',
            'product_qty'        => 'required|numeric',
            'start_date'         => 'required|date_format:Y-m-d',
            'usage_priority'     => 'required',
            'updated_by'         => 'required',
            'reason'             => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            DB::table('precise.bom_hd')
                ->where('bom_hd_id', $data['bom_hd_id'])
                ->update([
                    'bom_code'              => $data['bom_code'],
                    'bom_name'              => $data['bom_name'],
                    'bom_description'       => $data['bom_description'],
                    'product_id'            => $data['product_id'],
                    'product_qty'           => $data['product_qty'],
                    'product_uom'           => $data['product_uom'],
                    'is_active'             => $data['is_active'],
                    'start_date'            => $data['start_date'],
                    'expired_date'          => $data['expired_date'],
                    'usage_priority'        => $data['usage_priority'],
                    'updated_by'            => $data['updated_by']
                ]);

            if ($data['inserted'] != null) {
                foreach ($data['inserted'] as $d) {
                    $dt[] = [
                        'bom_hd_id'             => $d['bom_hd_id'],
                        'material_id'           => $d['material_id'],
                        'material_qty'          => $d['material_qty'],
                        'material_uom'          => $d['material_uom'],
                        'created_by'            => $d['created_by']
                    ];
                }
                DB::table('precise.bom_dt')
                    ->insert($dt);
            }

            if ($data['updated'] != null) {
                foreach ($data['updated'] as $d) {
                    DB::table('precise.bom_dt')
                        ->where('bom_dt_id', $d['bom_dt_id'])
                        ->update([
                            'bom_hd_id'         => $d['bom_hd_id'],
                            'material_id'       => $d['material_id'],
                            'material_qty'      => $d['material_qty'],
                            'material_uom'      => $d['material_uom'],
                            'updated_by'        => $d['updated_by']
                        ]);
                }
            }

            if ($data['deleted'] != null) {
                $delete = array();
                foreach ($data['deleted'] as $del) {
                    $delete[] = $del['bom_dt_id'];
                }

                DB::table('precise.bom_dt')
                    ->whereIn('bom_dt_id', $delete)
                    ->delete();
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bom_hd_id' => 'required|exists:bom_hd,bom_hd_id',
            'reason'    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $check = DB::table('precise.bom_dt')
                ->where('bom_hd_id', $request->bom_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error delete data", code: 500);
            }

            $check = DB::table('precise.bom_hd')
                ->where('bom_hd_id', $request->bom_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error delete data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $usage_priority = $request->get('usage_priority');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "bom_code") {
                $this->bom = DB::table('precise.bom_hd')
                    ->where('bom_code', $value)
                    ->count();
            } elseif ($type == "usage_priority") {
                $this->bom = DB::table('precise.bom_hd')
                    ->where('product_id', $value)
                    ->where('usage_priority', $usage_priority)
                    ->count();
            } elseif ($type == "deleted") {
                $this->bom = DB::table('precise.work_order')
                    ->where('bom_default', $value)
                    ->count();
            }
            if ($this->bom == 0)
                return ResponseController::json(status: "error", message: $this->bom, code: 404);

            return ResponseController::json(status: "ok", message: $this->bom, code: 200);
        }
    }
}
