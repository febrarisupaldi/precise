<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerGroupController extends Controller
{
    private $customerGroup;
    public function index(): JsonResponse
    {
        $sub = DB::table('precise.customer_group_member')
            ->select(
                'customer_group_id',
                DB::raw('
                    count(customer_id) as number_of_store
                ')
            )
            ->groupBy('customer_group_id');

        $this->customerGroup = DB::table('precise.customer_group as a')
            ->select(
                'a.customer_group_id',
                'a.group_code',
                'a.group_name',
                'a.group_description',
                DB::raw("
                        ifnull(number_of_store, 0) as 'Jumlah toko'
                    "),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->leftJoin(DB::raw("({$sub->toSql()}) as b"), 'a.customer_group_id', '=', 'b.customer_group_id')
            ->get();

        if (count($this->customerGroup) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->customerGroup, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->customerGroup = DB::table('precise.customer_group')
            ->where('customer_group_id', $id)
            ->select(
                'customer_group_id',
                'group_code',
                'group_name',
                'group_description'
            )->first();

        if (empty($this->customerGroup)) {
            return response()->json("not found", 404);
        }
        return response()->json($this->customerGroup, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_code'    => 'required|unique:customer_group,group_code',
            'group_name'    => 'required|unique:customer_group,group_name',
            'desc'          => 'nullable',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->customerGroup = DB::table('precise.customer_group')->insert([
            'group_code'        => $request->group_code,
            'group_name'        => $request->group_name,
            'group_description' => $request->desc,
            'created_by'        => $request->created_by
        ]);

        if ($this->customerGroup == 0)
            return ResponseController::json(status: "error", message: 'failed input data', code: 500);
        return ResponseController::json(status: "ok", message: 'success input data', code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_id'      => 'required|exists:customer_group,customer_group_id',
            'group_code'    => 'required',
            'group_name'    => 'required',
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
            $this->customerGroup = DB::table('customer_group')
                ->where('customer_group_id', $request->group_id)
                ->update([
                    'group_code' => $request->group_code,
                    'group_name' => $request->group_name,
                    'group_description' => $request->desc,
                    'updated_by' => $request->updated_by
                ]);

            if ($this->customerGroup == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: 'failed update data', code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: 'success update data', code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "customer_group_id"     =>  "required|exists:customer_group,customer_group_id",
            "reason"                =>  "required",
            "deleted_by"            =>  "required"
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->customerGroup = DB::table('precise.customer_group')
                ->where('customer_group_id', $request->customer_group_id)
                ->delete();

            if ($this->customerGroup == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: 'failed delete data', code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: 'success delete data', code: 204);
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
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $this->customerGroup = DB::table('precise.customer_group')
                    ->where('group_code', $value)
                    ->count();
            } elseif ($type == "name") {
                $this->customerGroup = DB::table('precise.customer_group')
                    ->where('group_name', $value)
                    ->count();
            }

            if ($this->customerGroup == 0)
                return ResponseController::json(status: "error", message: $this->customerGroup, code: 404);

            return ResponseController::json(status: "ok", message: $this->customerGroup, code: 200);
        }
    }
}
