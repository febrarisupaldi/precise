<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class AddressTypeController extends Controller
{
    //OK
    private $address;
    public function index(): JsonResponse
    {
        try {
            $this->address = DB::table('precise.address_type')
                ->select(
                    'address_type_id',
                    'address_type_name',
                    'address_type_description',
                    'created_on',
                    'created_by',
                    'updated_on',
                    'updated_by'
                )
                ->get();

            return ResponseController::json(status: "ok", data: $this->address, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $this->address = DB::table('precise.address_type')
                ->where('address_type_id', $id)
                ->select(
                    'address_type_id',
                    'address_type_name',
                    'address_type_description',
                    'created_on',
                    'created_by',
                    'updated_on',
                    'updated_by'
                )
                ->first();

            if (empty($this->address)) {
                return response()->json("not found", 404);
            }
            return response()->json($this->address, 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_type_name' =>  'required',
            'desc'              =>  'nullable',
            'created_by'        =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            $this->address = DB::table('precise.address_type')
                ->insert([
                    'address_type_name'         => $request->address_type_name,
                    'address_type_description'  => $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->address == 0) {
                return ResponseController::json(status: "error", message: "error insert data", code: 500);
            }
            return ResponseController::json(status: "ok", message: "success insert data", code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_type_id'   =>  'required|exists:address_type,address_type_id',
            'address_type_name' =>  'required',
            'desc'              =>  'nullable',
            'updated_by'        =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->address = DB::table('precise.address_type')
                ->where('address_type_id', $request->address_type_id)
                ->update([
                    'address_type_name'         => $request->address_type_name,
                    'address_type_description'  => $request->desc,
                    'updated_by'                => $request->updated_by
                ]);

            if ($this->address == 0) {
                DB::rollBack();
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
            'address_type_id'   =>  'required',
            'reason'            =>  'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->address = DB::table('precise.address_type')
                ->where('address_type_id', $request->address_type_id)
                ->delete();

            if ($this->address == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
            } else {
                DB::commit();
                return ResponseController::json(status: "ok", message: "success delete data", code: 204);
            }
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
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "name") {
                $this->address = DB::table('precise.address_type')->where('address_type_name', $value)->count();
            }

            if ($this->address == 0)
                return ResponseController::json(status: "not found", message: $this->address, code: 404);

            return ResponseController::json(status: "ok", message: $this->address, code: 200);
        }
    }
}
