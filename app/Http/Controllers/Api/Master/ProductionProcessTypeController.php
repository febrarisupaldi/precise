<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductionProcessTypeController extends Controller
{
    private $production;
    public function index(): JsonResponse
    {
        $this->production = DB::table("precise.production_process_type as pt")
            ->select(
                "pt.process_type_id",
                "pt.workcenter_id",
                "wc.workcenter_code",
                "wc.workcenter_name",
                "pt.process_code",
                "pt.process_description",
                "pt.created_on",
                "pt.created_by",
                "pt.updated_on",
                "pt.updated_by"
            )
            ->join("precise.workcenter as wc", "pt.workcenter_id", "=", "wc.workcenter_id")
            ->get();

        if (count($this->production) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->production, code: 200);
    }

    public function showByProductionType($id): JsonResponse
    {
        $this->production = DB::table("precise.production_process_type as ppt")
            ->where("w.production_type", $id)
            ->select(
                "process_type_id",
                "process_code",
                "process_description",
                "w.workcenter_id",
                "w.workcenter_code",
                "w.workcenter_name",
                "ppt.created_on",
                "ppt.created_by",
                "ppt.updated_on",
                "ppt.updated_by"
            )
            ->leftJoin("precise.workcenter as w", "ppt.workcenter_id", "=", "w.workcenter_id")
            ->get();

        if (count($this->production) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->production, code: 200);
    }
}
