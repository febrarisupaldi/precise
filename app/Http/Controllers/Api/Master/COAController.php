<?php

namespace App\Http\Controllers\Api\Master;

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
            return response()->json(["status" => "ok", "data" => $this->coa], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }
}
