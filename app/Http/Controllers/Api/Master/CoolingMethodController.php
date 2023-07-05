<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CoolingMethodController extends Controller
{
    private $coolingMethod;
    public function index(): JsonResponse
    {
        $this->coolingMethod = DB::table('precise.cooling_method')
            ->select(
                'cooling_method_id',
                'cooling_method_name',
                'cooling_method_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        if (count($this->coolingMethod) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->coolingMethod, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->coolingMethod = DB::table('precise.cooling_method')
            ->where('cooling_method_id', $id)
            ->select(
                'cooling_method_id',
                'cooling_method_name',
                'cooling_method_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();
        if (empty($this->coolingMethod))
            return response()->json("not found", 404);

        return response()->json($this->coolingMethod, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cooling_method_name'   => 'required|unique:cooling_method,cooling_method_name',
            'desc'                  => 'nullable',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->coolingMethod = DB::table('precise.cooling_method')
            ->insert([
                'cooling_method_name'           => $request->cooling_method_name,
                'cooling_method_description'    => $request->desc,
                'created_by'                    => $request->created_by
            ]);

        if ($this->coolingMethod == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cooling_method_id'     => 'required|exists:cooling_method,cooling_method_id',
            'cooling_method_name'   => 'required',
            'desc'                  => 'nullable',
            'is_active'             => 'required|boolean',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->coolingMethod = DB::table('precise.cooling_method')
                ->where('cooling_method_id', $request->cooling_method_id)
                ->update([
                    'cooling_method_name'       => $request->cooling_method_name,
                    'cooling_method_description' => $request->desc,
                    'is_active'                 => $request->is_active,
                    'updated_by'                => $request->updated_by
                ]);

            if ($this->coolingMethod == 0) {
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
        $type   = $request->get('type');
        $value  = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "name")
                $this->coolingMethod = DB::table('precise.cooling_method')
                    ->where('cooling_method_name', $value)
                    ->count();

            if ($this->coolingMethod == 0)
                return ResponseController::json(status: "error", message: $this->coolingMethod, code: 404);

            return ResponseController::json(status: "ok", message: $this->coolingMethod, code: 200);
        }
    }
}
