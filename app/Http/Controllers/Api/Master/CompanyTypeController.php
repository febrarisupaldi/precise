<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CompanyTypeController extends Controller
{
    private $companyType;
    public function index(): JsonResponse
    {
        $this->companyType = DB::table('precise.company_type')
            ->select(
                'company_type_id',
                'company_type_code',
                'company_type_description',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        return response()->json(['status' => 'ok', 'data' => $this->companyType], 200);
    }

    public function show($id): JsonResponse
    {
        $this->companyType = DB::table('precise.company_type')
            ->where('company_type_id', $id)
            ->select('company_type_code', 'company_type_description')
            ->first();

        if (empty($this->companyType)) {
            return response()->json("not found", 404);
        }
        return response()->json($this->companyType, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_type_code' => 'required|unique:company_type,company_type_code',
            'desc'              => 'required',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->companyType = DB::table('precise.company_type')
                ->insert([
                    'company_type_code'         => $request->company_type_code,
                    'company_type_description'  => $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->companyType == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_type_id'   => 'required|exists:company_type,company_type_id',
            'company_type_code' => 'required',
            'desc'              => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            DB::beginTransaction();
            try {
                DBController::reason($request, "update");

                $this->companyType = DB::table('precise.company_type')
                    ->where('company_type_id', $request->company_type_id)
                    ->update([
                        'company_type_code'         => $request->company_type_code,
                        'company_type_description'  => $request->desc,
                        'updated_by'                => $request->updated_by
                    ]);

                if ($this->companyType == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
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
            if ($type == 'code') {
                $this->companyType = DB::table('precise.company_type')
                    ->where('company_type_code', $value)
                    ->count();
            }

            if ($this->companyType == 0)
                return response()->json(['status' => 'error', 'message' => $this->companyType], 404);

            return response()->json(['status' => 'ok', 'message' => $this->companyType], 200);
        }
    }
}
