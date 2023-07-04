<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class COAController extends Controller
{
    private $coa;
    public function index()
    {
        try {
            $this->coa = DB::table("precise.coa")
                ->select(
                    "coa_id",
                    "coa_code",
                    "coa_name"
                )
                ->get();
            if (count($this->coa) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->coa, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
