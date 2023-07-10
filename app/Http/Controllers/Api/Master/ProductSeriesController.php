<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductSeriesController extends Controller
{
    private $productSeries;
    public function index(): JsonResponse
    {
        $this->productSeries = DB::table('precise.product_series')
            ->select(
                'series_id',
                'series_name',
                'series_description',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )->get();

        if (count($this->productSeries) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productSeries, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productSeries = DB::table('precise.product_series')
            ->where('series_id', $id)
            ->select(
                'series_id',
                'series_name',
                'series_description'
            )
            ->first();

        if (empty($this->productSeries))
            return response()->json($this->productSeries, 404);
        return response()->json($this->productSeries, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'series_name'   => 'required',
            'desc'          => 'nullable',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productSeries = DB::table('precise.product_series')
            ->insert([
                'series_name'           => $request->series_name,
                'series_description'    => $request->desc,
                'created_by'            => $request->created_by
            ]);

        if ($this->productSeries == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'series_id'     => 'required|exists:product_series,series_id',
            'series_name'   => 'required',
            'desc'          => 'nullable',
            'updated_by'    => 'required',
            'reason'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");

            $this->productSeries = DB::table('precise.product_series')
                ->where('series_id', $request->series_id)
                ->update([
                    'series_name'           => $request->series_name,
                    'series_description'    => $request->desc,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->productSeries == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "error update data", code: 500);
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
            'series_id'     => 'required|exists:product_series,series_id',
            'deleted_by'    => 'required',
            'reason'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->productSeries = DB::table('precise.product_series')
                ->where('series_id', $request->series_id)
                ->delete();

            if ($this->productSeries == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed delete data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "name") {
                $this->productSeries = DB::table('precise.product_series')->where([
                    'series_name' => $value
                ])->count();
            }

            if ($this->productSeries == 0)
                return response()->json(['status' => 'error', 'message' => $this->productSeries], 404);
            return response()->json(['status' => 'ok', 'message' => $this->productSeries], 200);
        }
    }
}
