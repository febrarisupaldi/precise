<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class MenuController extends Controller
{
    private $menu;
    public function index(): JsonResponse
    {
        $this->menu = DB::table('precise.menu as m')
            ->select(
                'menu_id',
                'menu_name',
                'menu_parent',
                'm.menu_category_id',
                'c.menu_category_name',
                'is_active',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )
            ->leftJoin('precise.menu_category as c', 'm.menu_category_id', '=', 'c.menu_category_id')
            ->get();

        if (count($this->menu) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->menu, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->menu = DB::table('menu')
            ->where('menu_id', $id)
            ->select(
                'menu_name',
                'menu_parent',
                'menu_category_id',
                'is_active'
            )
            ->first();

        if (empty($this->menu)) {
            return response()->json($this->menu, 404);
        }
        return response()->json($this->menu, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'menu_name'         => 'required',
            'menu_parent'       => 'nullable',
            'menu_category_id'  => 'required|exists:menu_category,menu_category_id',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->menu = DB::table('precise.menu')
            ->insert([
                'menu_name'         => $request->menu_name,
                'menu_parent'       => $request->menu_parent,
                'menu_category_id'  => $request->menu_category_id,
                'created_by'        => $request->created_by
            ]);

        if ($this->menu == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'menu_id'           => 'required|exists:menu,menu_id',
            'menu_name'         => 'required',
            'menu_category_id'  => 'required|exists:menu_category,menu_category_id',
            'menu_parent'       => 'nullable',
            'is_active'         => 'boolean',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->menu = DB::table('precise.menu')
                ->where('menu_id', $request->menu_id)
                ->update([
                    'menu_name'         => $request->menu_name,
                    'menu_parent'       => $request->menu_parent,
                    'is_active'         => $request->is_active,
                    'menu_category_id'  => $request->menu_category_id,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->menu == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed update data", code: 500);
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
            'menu_id'   => 'required|exists:menu,menu_id',
            'reason'    => 'required',
            'deleted_by' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->menu = DB::table('precise.menu')
                ->where('menu_id', $request->menu_id)
                ->delete();

            if ($this->menu == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
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
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "name") {
                $this->menu = DB::table('precise.menu')
                    ->where('menu_name', $value)
                    ->count();
            }

            if ($this->menu == 0) return ResponseController::json(status: "error", message: $this->menu, code: 404);

            return ResponseController::json(status: "ok", message: $this->menu, code: 200);
        }
    }
}
