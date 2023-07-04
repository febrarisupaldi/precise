<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;


//Fixed
class StateController extends Controller
{
    private $state;
    public function index(): JsonResponse
    {
        $this->state = DB::table('precise.state as s')
            ->select(
                'state_id',
                'state_code',
                'state_name',
                'country_name',
                's.created_on',
                's.created_by',
                's.updated_on',
                's.updated_by'
            )
            ->leftJoin('precise.country as c', 's.country_id', '=', 'c.country_id')
            ->get();
        return response()->json(['status' => 'ok', 'data' => $this->state], 200);
    }
    public function show($id): JsonResponse
    {
        $this->state = DB::table('precise.state')
            ->where('state_id', $id)
            ->select(
                'state_code',
                'state_name',
                'country_id'
            )
            ->first();
        if (empty($this->state))
            return response()->json("not found", 404);
        return response()->json($this->state, 200);
    }
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'state_code' => 'required|unique:state,state_code',
                'state_name' => 'required',
                'country_id' => 'required|exists:country,country_id',
                'created_by' => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->state = DB::table('precise.state')
            ->insert([
                'state_code' => $request->state_code,
                'state_name' => $request->state_name,
                'country_id' => $request->country_id,
                'created_by' => $request->created_by
            ]);

        if ($this->state == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'state_id'      => 'required|exists:state,state_id',
                'state_code'    => 'required',
                'state_name'    => 'required',
                'country_id'    => 'required|exists:country,country_id',
                'updated_by'    => 'required',
                'reason'        => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update");

            $this->state = DB::table('precise.state')
                ->where('state_id', $request->state_id)
                ->update([
                    'state_code' => $request->state_code,
                    'state_name' => $request->state_name,
                    'country_id' => $request->country_id,
                    'updated_by' => $request->updated_by
                ]);

            if ($this->state == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
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
        }
        if ($type == "code") {
            $this->state = DB::table('precise.state')->where('state_code', $value)->count();
        } elseif ($type == "name") {
            $this->state = DB::table('precise.state')->where('state_name', $value)->count();
        }
        if ($this->state == 0)
            return response()->json(['status' => 'error', 'message' => $this->state], 404);
        return response()->json(['status' => 'ok', 'message' => $this->state], 200);
    }
}
