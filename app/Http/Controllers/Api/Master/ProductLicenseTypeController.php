<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductLicenseTypeController extends Controller
{
    private $productLicenseType;
    public function index(): JsonResponse
    {
        $this->productLicenseType = DB::table('precise.product_license_type')
            ->select(
                'license_type_id',
                'license_type_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        return response()->json(["status" => "ok", "data" => $this->productLicenseType], 200);
    }
}
