<?php

namespace App\Http\Controllers\Api\Engineering;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MachinePressingActivityController extends Controller
{
    private $activity;
    public function index()
    {
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_pressing_id'   => 'required|exists:machine_pressing,machine_pressing_id',
                'mold_pressing_hd_id'   => 'nullable|exists:mold_pressing_hd,mold_pressing_hd_id',
                'machine_status_code'   => 'nullable|exists:machine_status,status_code',
                'mold_status_code'      => 'nullable|exists:mold_status,status_code',
                'setter_mold_nik'       => 'required|exists:users,user_id',
                'desc'                  => 'nullable'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->activity = DB::table('precise.machine_pressing_activity')
                ->insert([
                    'machine_pressing_id'   => $request->machine_pressing_id,
                    'mold_pressing_hd_id'   => $request->mold_pressing_hd_id,
                    'machine_status_code'   => $request->machine_status_code,
                    'mold_status_code'      => $request->mold_status_code,
                    'setter_mold_nik'       => $request->setter_mold_nik,
                    'description'           => $request->desc
                ]);

            if ($this->activity == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
            }
        }
    }
}
