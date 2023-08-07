<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class UOMController extends Controller
{
    private $uom;
    public function index(): JsonResponse
    {
        $this->uom = DB::table('precise.uom')
            ->select(
                'uom_code',
                'uom_name',
                DB::raw("if(is_active = 1, 'Aktif', 'Tidak aktif') as 'Status aktif'"),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if (count($this->uom) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->uom, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->uom = DB::table('precise.uom')
            ->where('uom_code', $id)
            ->select(
                'uom_code',
                'uom_name',
                'is_active'
            )->first();
        if (empty($this->uom))
            return response()->json("not found", 404);
        return response()->json($this->uom, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uom_code'      => 'required|unique:uom,uom_code',
            'uom_name'      => 'required',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->uom = DB::table('precise.uom')
            ->insert([
                'uom_code'      => $request->uom_code,
                'uom_name'      => $request->uom_name,
                'created_by'    => $request->created_by
            ]);

        if ($this->uom == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uom_code'      => 'required',
            'uom_name'      => 'required',
            'is_active'     => 'required|boolean',
            'updated_by'    => 'required',
            'reason'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->uom = DB::table('precise.uom')
                ->where('uom_code', $request->uom_code)
                ->update([
                    'uom_name'      => $request->uom_name,
                    'is_active'     => $request->is_active,
                    'updated_by'    => $request->updated_by
                ]);

            if ($this->uom == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code")
                $this->uom = DB::table('precise.uom')
                    ->where('uom_code', $value)
                    ->count();
            elseif ($type == "name")
                $this->uom = DB::table('precise.uom')
                    ->where('uom_name', $value)
                    ->count();
            if ($this->uom == 0)
                return ResponseController::json(status: "error", message: $this->uom, code: 404);

            return ResponseController::json(status: "ok", message: $this->uom, code: 200);
        }
    }
}
