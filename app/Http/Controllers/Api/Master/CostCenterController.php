<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class CostCenterController extends Controller
{
    private $costCenter;
    public function showByMultipleID($id): JsonResponse
    {
        try {
            $cost = explode("-", $id);
            $this->costCenter = DB::table("precise.cost_center")
                ->whereIn('cost_center_id', $cost)
                ->select(
                    'cost_center_id',
                    'cost_center_code',
                    'cost_center_name',
                    'created_on',
                    'created_by',
                    'updated_on',
                    'updated_by'
                )
                ->get();

            if (count($this->costCenter) == 0) {
                return response()->json(["status" => "error", "data" => $this->costCenter], 404);
            }
            return response()->json(["status" => "ok", "data" => $this->costCenter], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 500]);
        }
    }
}
