<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductWorkcenterController extends Controller
{
    private $productWorkcenter;
    public function index($id): JsonResponse
    {
        $id = explode('-', $id);
        $this->productWorkcenter = DB::table('precise.product_workcenter as pw')
            ->whereIn('w.workcenter_id', $id)
            ->select(
                'pw.product_workcenter_id',
                'p.product_code',
                'p.product_name',
                'w.workcenter_code',
                'w.workcenter_name',
                'bom.bom_code',
                'bom.bom_name',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'pw.output_tolerance',
                'pw.created_on',
                'pw.created_by',
                'pw.updated_on',
                'pw.updated_by'
            )
            ->leftJoin('precise.product as p', 'pw.product_id', '=', 'p.product_id')
            ->leftJoin('precise.workcenter as w', 'pw.workcenter_id', '=', 'w.workcenter_id')
            ->leftJoin('precise.bom_hd as bom', 'pw.bom_default', '=', 'bom.bom_hd_id')
            ->leftJoin('precise.warehouse as wh', 'pw.warehouse_default', '=', 'wh.warehouse_id')
            ->get();
        return response()->json(["status" => "ok", "data" => $this->productWorkcenter], 200);
    }

    public function show($id): JsonResponse
    {
        $id = explode('-', $id);
        $this->productWorkcenter = DB::table('precise.product_workcenter as pw')
            ->whereIn('pw.product_workcenter_id', $id)
            ->select(
                'pw.product_workcenter_id',
                'pw.product_id',
                'pw.workcenter_id',
                'pw.bom_default',
                'pw.warehouse_default',
                'p.product_code',
                'p.product_name',
                'w.workcenter_code',
                'w.workcenter_name',
                'bom.bom_code',
                'bom.bom_name',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'pw.output_tolerance',
                'pw.created_on',
                'pw.created_by',
                'pw.updated_on',
                'pw.updated_by'
            )
            ->leftJoin('precise.product as p', 'pw.product_id', '=', 'p.product_id')
            ->leftJoin('precise.workcenter as w', 'pw.workcenter_id', '=', 'w.workcenter_id')
            ->leftJoin('precise.bom_hd as bom', 'pw.bom_default', '=', 'bom.bom_hd_id')
            ->leftJoin('precise.warehouse as wh', 'pw.warehouse_default', '=', 'wh.warehouse_id')
            ->first();

        if (empty($this->productWorkcenter))
            return response()->json($this->productWorkcenter, 404);
        return response()->json($this->productWorkcenter, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'product_id'        => 'required|exists:product,product_id',
                'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
                'bom_default'       => 'nullable|exists:bom_hd,bom_hd_id',
                'warehouse_default' => 'nullable|exists:warehouse,warehouse_id',
                'created_by'        => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->productWorkcenter = DB::table("precise.product_workcenter")
            ->insert([
                'product_id'            => $request->product_id,
                'workcenter_id'         => $request->workcenter_id,
                'bom_default'           => $request->bom_default,
                'warehouse_default'     => $request->warehouse_default,
                'output_tolerance'      => $request->output_tolerance,
                'created_by'            => $request->created_by
            ]);

        if ($this->productWorkcenter == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function update(Request $request): JsonResponse
    {

        $validator = Validator::make(
            $request->all(),
            [
                'product_workcenter_id' => 'required|exists:product_workcenter,product_workcenter_id',
                'product_id'            => 'required|exists:product,product_id',
                'workcenter_id'         => 'required|exists:workcenter,workcenter_id',
                'bom_default'           => 'nullable|exists:bom_hd,bom_hd_id',
                'warehouse_default'     => 'nullable|exists:warehouse,warehouse_id',
                'updated_by'            => 'required',
                'reason'                => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update");
            $this->productWorkcenter = DB::table("precise.product_workcenter")
                ->where('product_workcenter_id', $request->product_workcenter_id)
                ->update([
                    'product_id'          => $request->product_id,
                    'workcenter_id'       => $request->workcenter_id,
                    'bom_default'         => $request->bom_default,
                    'warehouse_default'   => $request->warehouse_default,
                    'output_tolerance'    => $request->output_tolerance,
                    'updated_by'          => $request->updated_by
                ]);

            if ($this->productWorkcenter == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'product_workcenter_id' => 'required|exists:product_workcenter,product_workcenter_id',
                'deleted_by'            => 'required',
                'reason'                => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->productWorkcenter = DB::table('precise.product_workcenter')
                ->where('product_workcenter_id', $request->product_workcenter_id)
                ->delete();

            if ($this->productWorkcenter == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed delete data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $product = $request->get('product');
        $workcenter = $request->get('workcenter');
        $validator = Validator::make($request->all(), [
            'product'    => 'required',
            'workcenter' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->productWorkcenter = DB::table('precise.product_workcenter')
                ->where('product_id', $product)
                ->where('workcenter_id', $workcenter)
                ->count();
            if ($this->productWorkcenter == 0)
                return response()->json(['status' => 'error', 'message' => $this->productWorkcenter], 404);
            return response()->json(['status' => 'ok', 'message' => $this->productWorkcenter], 200);
        }
    }
}
