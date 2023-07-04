<?php

namespace App\Http\Controllers\Api\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class LogScanController extends Controller
{
    private $logScan;
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "user_id"       => 'required|exists:users,user_id',
            "barcode_text"  => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->logScan = DB::table("precise.log_scan")
            ->insert([
                "user_id"       => $request->user_id,
                "barcode_text"  => $request->barcode_text
            ]);

        if ($this->logScan == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
        }

        return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
    }
}
