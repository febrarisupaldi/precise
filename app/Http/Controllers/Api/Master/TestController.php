<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    private $privilege;
    public function testBuilder(Request $request)
    {
        $transNum = DB::select('SELECT precise.get_transaction_number(6, :rDate) AS transNumber', ['rDate' => $request['production_date']]);
        $check1 = $transNum[0]->transNumber;
        $transNum = array_pop($transNum);
        $check2 = $transNum;

        return response(["data 1" => $check1, "data 2" => $check2], 200); //->json(["data" => $this->privilege], 200);

        // $object = (object)$transNum;
        // foreach ($transNum as $key => $value) {
        //     $object->$key = $value;
        // }

        // $object1 = $object->$key;

        // return response()->json(["data" => $object1], 200);
    }

    public function testValidator(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make($data['data'], [
            "*.city"    => ['required'],
            "*.state" => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        return response()->json(["message" => "Validation passed"]);
    }
}
