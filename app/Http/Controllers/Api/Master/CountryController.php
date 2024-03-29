<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CountryController extends Controller
{
    private $country;
    public function index(): JsonResponse
    {
        $this->country = DB::table('precise.country')
            ->select(
                'country_id',
                'country_code',
                'country_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if (count($this->country) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->country, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->country = DB::table('precise.country')
            ->where('country_id', $id)
            ->select(
                'country_code',
                'country_name'
            )
            ->get();

        if (empty($this->country))
            return response()->json("not found", 404);

        return response()->json($this->country, 200);
    }
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_code'  => 'required|unique:country,country_code',
            'country_name'  => 'required',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->country = DB::table('precise.country')
            ->insert([
                'country_code' => $request->country_code,
                'country_name' => $request->country_name,
                'created_by' => $request->created_by
            ]);

        if ($this->country == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id'    => 'required|exists:country,country_id',
            'country_code'  => 'required',
            'country_name'  => 'required',
            'updated_by'    => 'required',
            'reason'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update");
            $this->country = DB::table('precise.country')
                ->where('country_id', $request->country_id)
                ->update([
                    'country_code' => $request->country_code,
                    'country_name' => $request->country_name,
                    'updated_by' => $request->updated_by
                ]);

            if ($this->country == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code") {
                $this->country = DB::table('precise.country')
                    ->where('country_code', $value)
                    ->count();
            } else if ($type == "name") {
                $this->country = DB::table('precise.country')
                    ->where('country_name', $value)
                    ->count();
            }
            if ($this->country == 0)
                return ResponseController::json(status: "error", message: $this->country, code: 404);

            return ResponseController::json(status: "ok", message: $this->country, code: 200);
        }
    }
}
