<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class WarehouseRackController extends Controller
{
    private $rack;
    public function index(): JsonResponse
    {
        $this->rack = DB::table("precise.warehouse_rack")
            ->select(
                'rack_id',
                'rack_code',
                'rack_zone',
                'rack_lane',
                'rack_number',
                'rack_level',
                DB::raw("
                    CONCAT('LEVEL ', rack_level) AS rack_level_caption
                "),
                'multiplication'
            )
            ->crossJoin(DB::raw('(select 1 as multiplication) as a'))
            ->orderBy('rack_number')
            ->orderBy('rack_level', 'desc')
            ->get();

        if (count($this->rack) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->rack, code: 200);
    }

    public function getByZoneAndLane($zone, $lane): JsonResponse
    {
        $this->rack = DB::table('precise.warehouse_rack')
            ->where('rack_zone', $zone)
            ->where('rack_lane', $lane)
            ->whereBetween('rack_number', [1, 4])
            ->select(
                'rack_id',
                'rack_code',
                'rack_zone',
                'rack_lane',
                'rack_number',
                'rack_level',
                DB::raw("
                    concat('LEVEL ', rack_level) as rack_level_caption
                ")
            )
            ->orderBy('rack_number')
            ->orderBy('rack_level', 'desc')
            ->get();

        if (count($this->rack) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->rack, code: 200);
    }
}
