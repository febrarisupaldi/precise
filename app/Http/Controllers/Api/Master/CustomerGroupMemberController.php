<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Master\HelperController;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerGroupMemberController extends Controller
{
    private $customer;
    public function show($id): JsonResponse
    {

        $this->customer = DB::table('customer_group_member as a')
            ->where('a.customer_group_member_id', $id)
            ->select(
                'b.customer_id',
                'b.customer_name',
                'b.customer_alias_name',
                'city_name',
                'company_type_code',
                'retail_type_description',
                'npwp',
                'ppn_type',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->rightJoin('customer as b', 'a.customer_id', '=', 'b.customer_id')
            ->leftJoin('company_type as c', 'b.company_type_id', '=', 'c.company_type_id')
            ->leftJoin('retail_type as d', 'b.retail_type_id', '=', 'd.retail_type_id')
            ->leftJoin('city as e', 'b.city_id', '=', 'e.city_id')
            ->get();
        return response()->json($this->customer, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $helper = new HelperController();
        if ($helper->insertOrUpdate($request->data, 'customer_group_member', '') == false) {
            return response()->json(['status' => 'error', 'message' => 'Failed to add Member customer group']);
        } else {
            return response()->json(['status' => 'ok', 'message' => 'Member customer group have beed added']);
        }
    }

    public function destroy($id): JsonResponse
    {
        $id = explode("-", $id);
        DB::beginTransaction();
        try {
            $helper = new HelperController();
            $helper->reason("delete");

            $this->customer = DB::table('customer_group_member')
                ->whereIn('customer_group_member_id', $id)
                ->delete();
            if ($this->customer == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete Member customer group']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'Member customer group have beed deleted']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $customer_group = $request->get('customer_group_id');
        $customer = $request->get('customer_id');
        $validator = Validator::make($request->all(), [
            'customer_group_id' => 'required',
            'customer_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->customer = DB::table('customer_group_member')
                ->where(
                    [
                        'customer_group_member_id' => $customer_group,
                        'customer_id' => $customer
                    ]
                )->count();
            return response()->json(["status" => "ok", "message" => $this->customer]);
        }
    }
}
