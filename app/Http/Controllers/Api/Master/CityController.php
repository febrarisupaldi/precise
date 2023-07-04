<?php

//fixed
namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Controllers\Api\Helpers\ResponseController;

class CityController extends Controller
{
    //OK
    private $city;
    public function index(): JsonResponse
    {
        $this->city = DB::table('precise.city as c')
            ->join('state as s', 'c.state_id', '=', 's.state_id')
            ->join('country as co', 's.country_id', '=', 'co.country_id')
            ->select(
                'city_id',
                'city_code',
                'city_name',
                'state_name',
                'co.country_name',
                'c.created_on',
                'c.created_by',
                'c.updated_on',
                'c.updated_by'
            )
            ->get();

        return ResponseController::json(status: "ok", data: $this->city, code: 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $this->city = DB::table('precise.city')
                ->select(
                    'city_code',
                    'city_name',
                    'state_id'
                )
                ->where('city_id', $id)
                ->first();

            if (empty($this->city)) {
                return response()->json("error", 404);
            }
            return response()->json($this->city, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'city_code' => 'required|unique:city,city_code',
                'city_name' => 'required',
                'state_id' => 'required|exists:state,state_id',
                'created_by' => 'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            $this->city = DB::table('precise.city')
                ->insert([
                    'city_code'     => $request->city_code,
                    'city_name'     => $request->city_name,
                    'state_id'      => $request->state_id,
                    'created_by'    => $request->created_by
                ]);

            if ($this->city == 0)
                return ResponseController::json(status: "error", message: "failed insert data", code: 500);


            return ResponseController::json(status: "ok", message: "success insert data", code: 200);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'city_id' => 'required|exists:city,city_id',
                'city_code' => 'required',
                'city_name' => 'required',
                'state_id' => 'required|exists:state,state_id',
                'updated_by' => 'required',
                'reason' => 'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            DB::beginTransaction();
            try {
                DBController::reason($request, "update");
                $this->city = DB::table('precise.city')
                    ->where('city_id', $request->city_id)
                    ->update([
                        'city_code'     => $request->city_code,
                        'city_name'     => $request->city_name,
                        'state_id'      => $request->state_id,
                        'updated_by'    => $request->updated_by
                    ]);

                if ($this->city == 0) {
                    DB::rollback();
                    return ResponseController::json(status: "error", message: "failed update data", code: 500);
                } else {
                    DB::commit();
                    return ResponseController::json(status: "ok", message: "success update data", code: 200);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
            }
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
                $this->city = DB::table('precise.city')->where('city_code', $value)->count();
            } elseif ($type == "name") {
                $this->city = DB::table('precise.city')->where('city_name', $value)->count();
            }

            if ($this->city == 0)
                return ResponseController::json(status: "not found", message: $this->city, code: 404);

            return ResponseController::json(status: "ok", message: $this->city, code: 200);
        }
    }
}
