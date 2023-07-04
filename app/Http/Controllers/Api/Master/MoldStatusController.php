<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MoldStatusController extends Controller
{
    private $moldStatus;
    public function index(): JsonResponse
    {
        $this->moldStatus = DB::table('precise.mold_status')
            ->select(
                'status_code',
                'status_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["status" => "ok", "data" => $this->moldStatus], 200);
    }

    public function show($id): JsonResponse
    {
        $this->moldStatus = DB::table('precise.mold_status')
            ->where('status_code', $id)
            ->select(
                'status_code',
                'status_description',
                'is_active'
            )
            ->first();
        if (empty($this->moldStatus))
            return response()->json("not found", 404);

        return response()->json($this->moldStatus, 200);
    }

    public function create(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'status_code'   => 'required|unique:mold_status,status_code',
            'desc'          => 'nullable',
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->moldStatus = DB::table('precise.mold_status')
                ->insert([
                    'status_code'           => $request->status_code,
                    'status_description'    => $request->desc,
                    'created_by'            => $request->created_by
                ]);
            if ($this->moldStatus == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
            }
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_code'   => 'required',
            'desc'          => 'nullable',
            'is_active'     => 'boolean',
            'updated_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            DB::beginTransaction();
            try {
                DBController::reason($request, "update");
                $this->moldStatus = DB::table('precise.mold_status')
                    ->where('status_code', $request->status_code)
                    ->update([
                        'status_code'           => $request->status_code,
                        'status_description'    => $request->desc,
                        'is_active'             => $request->is_active,
                        'updated_by'            => $request->updated_by
                    ]);

                if ($this->moldStatus == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
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
            if ($type == "code") {
                $this->moldStatus = DB::table('precise.mold_status')
                    ->where('status_code', $value)
                    ->count();
            }

            if ($this->moldStatus == 0)
                return response()->json(['status' => 'error', 'message' => $this->moldStatus], 404);

            return response()->json(['status' => 'ok', 'message' => $this->moldStatus], 200);
        }
    }
}
