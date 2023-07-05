<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerController extends Controller
{
    private $customer;
    public function index(): JsonResponse
    {
        $this->customer = DB::table('precise.customer as c')
            ->select(
                'c.customer_id',
                'c.customer_code',
                'c.customer_name',
                'c.customer_alias_name',
                'ca.customer_address_id',
                'ca.address',
                'ca.address_type_id',
                'ca.is_default',
                'c.company_type_id',
                'c.retail_type_id',
                'rt.retail_type_description AS retail_type_name',
                'rt.GroupCodeOnProint',
                'rc.cost_center_id',
                'cc.cost_center_code',
                'cc.cost_center_name',
                'c.npwp',
                'c.pkp_name',
                'c.ppn_type',
                'c.city_id',
                'ci.city_code',
                'ci.city_name',
                'c.ar_coa_id',
                'c.is_active',
                'c.approval_status',
                'c.approved_by',
                'c.created_on',
                'c.created_by',
                'c.updated_on',
                'c.updated_by'
            )
            ->leftJoin('precise.retail_type as rt', 'c.retail_type_id', '=', 'rt.retail_type_id')
            ->leftJoin('precise.revenue_center as rc', 'rt.retail_type_id', '=', 'rc.retail_type_id')
            ->leftJoin('precise.cost_center as cc', 'rc.cost_center_id', '=', 'cc.cost_center_id')
            ->leftJoin('precise.city as ci', 'c.city_id', '=', 'ci.city_id')
            ->leftJoin('precise.customer_address as ca', function ($join) {
                $join->on("c.customer_id", "=", "ca.customer_id");
                $join->on("ca.address_type_id", DB::raw(1));
                $join->on("ca.is_default", DB::raw(1));
            })
            ->get();
        if (count($this->customer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->customer, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->customer = DB::table('precise.customer')
            ->where('customer_id', $id)
            ->select(
                'customer_code',
                'customer_name',
                'customer_alias_name',
                'company_type_id',
                'retail_type_id',
                'npwp',
                'pkp_name',
                'ppn_type',
                'city_id',
                'ar_coa_id',
                'is_active'
            )->first();

        if (empty($this->customer))
            return response()->json("not found", 404);

        return response()->json($this->customer, 200);
    }

    public function showByRetail($id): JsonResponse
    {
        try {
            $value = explode("-", $id);
            $this->customer = DB::table('precise.customer as a')
                ->whereIn('b.retail_type_id', $value)
                ->select(
                    'customer_id',
                    'customer_code',
                    'customer_name',
                    'a.customer_alias_name',
                    'c.city_name as Kota',
                    'b.retail_type_code'
                )
                ->leftJoin('retail_type as b', 'a.retail_type_id', '=', 'b.retail_type_id')
                ->leftJoin('city as c', 'a.city_id', '=', 'c.city_id')
                ->orderBy('customer_id')
                ->get();

            return response()->json(["status" => "ok", "data" => $this->customer], 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", data: $e->getMessage(), code: 500);
        }
    }

    public function getCustomerInStock($nik, $name = null): JsonResponse
    {
        $this->customer = DB::table('dbstok.customer as sc')
            ->select(
                'pc.customer_id',
                'pc.customer_code',
                'pc.customer_name',
                'sc.wilayah',
                'sc.provinsi',
                'sc.group',
                'sc.nm_gudang',
                'a.nik',
                'sc.nama_lengkap'
            )
            ->leftJoin('precise.customer as pc', 'sc.id_customer', '=', 'pc.customer_code')
            ->leftJoin('dbstok.admins as a', 'a.id_user', '=', 'sc.id_user')
            ->where('a.nik', $nik);

        if ($name != null)
            $this->customer = $this->customer
                ->where('pc.customer_name', 'like', '%' . $name . '%');

        $customer = $this->customer->get();
        if (count($customer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->customer, code: 200);
    }
}
