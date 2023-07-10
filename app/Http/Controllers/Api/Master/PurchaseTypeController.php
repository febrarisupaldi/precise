<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PurchaseTypeController extends Controller
{
    private $purchaseType;
    public function index(): JsonResponse
    {
        $this->purchaseType = DB::table("precise.purchase_type")
            ->select(
                'purchase_type_id',
                'purchase_type_code',
                'purchase_type_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        if (count($this->purchaseType) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->purchaseType, code: 200);
    }
}
