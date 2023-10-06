<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class MachineInjectionActivityController extends Controller
{
    private $activity;
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_injection_id'  => 'required|exists:machine_injection,machine_injection_id',
                'mold_injection_hd_id'  => 'nullable|exists:mold_injection_hd,mold_injection_hd_id',
                'machine_status_code'   => 'nullable|exists:machine_status,status_code',
                'mold_status_code'      => 'nullable|exists:mold_status,status_code',
                'setter_mold_nik'       => 'required|exists:users,user_id',
                'desc'                  => 'nullable'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->activity = DB::table('precise.machine_injection_activity')
            ->insert([
                'machine_injection_id'  => $request->machine_injection_id,
                'mold_injection_hd_id'  => $request->mold_injection_hd_id,
                'machine_status_code'   => $request->machine_status_code,
                'mold_status_code'      => $request->mold_status_code,
                'setter_mold_nik'       => $request->setter_mold_nik,
                'description'           => $request->desc
            ]);

        if ($this->activity == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }
}
