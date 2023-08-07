<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class SalesmanController extends Controller
{
    private $salesman;
    public function index(): JsonResponse
    {
        $this->salesman = DB::table("precise.salesman as s")
            ->select(
                's.salesman_id',
                'e.employee_name',
                's.created_on',
                's.created_by',
                's.updated_on',
                's.updated_by'
            )
            ->leftJoin("precise.employee as e", "s.salesman_id", "=", "e.employee_nik")
            ->get();

        if ($this->salesman)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->salesman, code: 200);
    }
}
