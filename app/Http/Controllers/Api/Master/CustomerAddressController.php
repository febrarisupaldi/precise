<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerAddressController extends Controller
{
    private $customerAddress;
    public function index(): JsonResponse
    {
        $this->customerAddress = DB::table("precise.customer_address as ca")
            ->select(
                'ca.customer_address_id',
                'ca.customer_id',
                'c.customer_code',
                'c.customer_name',
                'ca.address_type_id',
                'at.address_type_name',
                'ca.address',
                'ca.subdistrict',
                'ca.district',
                'ca.city_id',
                'ct.city_name',
                'ca.zipcode',
                'ca.phone_number',
                'ca.fax_number',
                'ca.email',
                'ca.contact_person',
                'ca.created_on',
                'ca.created_by',
                'ca.updated_on',
                'ca.updated_by'
            )
            ->leftJoin("precise.customer as c", "ca.customer_id", "=", "c.customer_id")
            ->leftJoin("precise.address_type as at", "ca.address_type_id", "=", "at.address_type_id")
            ->leftJoin("precise.city as ct", "ca.city_id", "=", "ct.city_id")
            ->get();

        return response()->json(["status" => "ok", "data" => $this->customerAddress], 200);
    }

    public function show($id): JsonResponse
    {
        $this->customerAddress = DB::table("precise.customer_address as ca")
            ->where("ca.customer_address_id", $id)
            ->select(
                'ca.customer_address_id',
                'ca.customer_id',
                'c.customer_code',
                'c.customer_name',
                'ca.address_type_id',
                'at.address_type_name',
                'ca.address',
                'ca.subdistrict',
                'ca.district',
                'ca.city_id',
                'ct.city_name',
                'ca.zipcode',
                'ca.phone_number',
                'ca.fax_number',
                'ca.email',
                'ca.contact_person',
                'ca.created_on',
                'ca.created_by',
                'ca.updated_on',
                'ca.updated_by'
            )
            ->leftJoin("precise.customer as c", "ca.customer_id", "=", "c.customer_id")
            ->leftJoin("precise.address_type as at", "ca.address_type_id", "=", "at.address_type_id")
            ->leftJoin("precise.city as ct", "ca.city_id", "=", "ct.city_id")
            ->first();

        if (empty($this->customerAddress)) {
            return response()->json($this->customerAddress, 404);
        }
        return response()->json($this->customerAddress, 200);
    }

    public function showByCustomerID($id): JsonResponse
    {
        try {
            $customerID = explode("-", $id);

            $this->customerAddress = DB::table("precise.customer_address as ca")
                ->whereIn("ca.customer_id", $customerID)
                ->select(
                    'ca.customer_address_id',
                    'ca.customer_id',
                    'c.customer_code',
                    'c.customer_name',
                    'ca.address_type_id',
                    'at.address_type_name',
                    'ca.address',
                    'ca.subdistrict',
                    'ca.district',
                    'ca.city_id',
                    'ct.city_name',
                    'ca.zipcode',
                    'ca.phone_number',
                    'ca.fax_number',
                    'ca.email',
                    'ca.contact_person',
                    'ca.created_on',
                    'ca.created_by',
                    'ca.updated_on',
                    'ca.updated_by'
                )
                ->leftJoin("precise.customer as c", "ca.customer_id", "=", "c.customer_id")
                ->leftJoin("precise.address_type as at", "ca.address_type_id", "=", "at.address_type_id")
                ->leftJoin("precise.city as ct", "ca.city_id", "=", "ct.city_id")
                ->get();

            if (empty($this->customerAddress)) {
                return response()->json(["status" => "error", "data" => "not found"], 404);
            }
            return response()->json(["status" => "ok", "data" => $this->customerAddress], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 500]);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "customer_id"       =>  "required|exists:customer,customer_id",
            "address_type_id"   =>  "required|exists:address_type,address_type_id",
            "address"           =>  "required",
            "subdistrict"       =>  "required",
            "district"          =>  "required",
            "city_id"           =>  "required|exists:city,city_id",
            "zipcode"           =>  "required",
            "phone_number"      =>  "required",
            "fax_number"        =>  "nullable",
            "email"             =>  "nullable",
            "contact_person"    =>  "required",
            "created_by"        =>  "required"
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->customerAddress = DB::table("precise.customer_address")
            ->insert([
                "customer_id"       => $request->customer_id,
                "address_type_id"   => $request->address_type_id,
                "address"           => $request->address,
                "subdistrict"       => $request->subdistrict,
                "district"          => $request->district,
                "city_id"           => $request->city_id,
                "zipcode"           => $request->zipcode,
                "phone_number"      => $request->phone_number,
                "fax_number"        => $request->fax_number,
                "email"             => $request->email,
                "contact_person"    => $request->contact_person,
                "created_by"        => $request->created_by
            ]);

        if ($this->customerAddress == 0) {
            return response()->json(["status" => "error", "message" => "failed insert data"], 500);
        }

        return response()->json(["status" => "ok", "message" => "success insert data"], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "customer_address_id"   =>  "required|exists:customer_address,customer_address_id",
            "customer_id"           =>  "required|exists:customer,customer_id",
            "address_type_id"       =>  "required|exists:address_type,address_type_id",
            "address"               =>  "required",
            "subdistrict"           =>  "required",
            "district"              =>  "required",
            "city_id"               =>  "required|exists:city,city_id",
            "zipcode"               =>  "nullable",
            "phone_number"          =>  "nullable",
            "fax_number"            =>  "nullable",
            "email"                 =>  "nullable",
            "contact_person"        =>  "nullable",
            "updated_by"            =>  "required"
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");

            $this->customerAddress = DB::table("precise.customer_address")
                ->where("customer_address_id", $request->customer_address_id)
                ->update([
                    "customer_id"       => $request->customer_id,
                    "address_type_id"   => $request->address_type_id,
                    "address"           => $request->address,
                    "subdistrict"       => $request->subdistrict,
                    "district"          => $request->district,
                    "city_id"           => $request->city_id,
                    "zipcode"           => $request->zipcode,
                    "phone_number"      => $request->phone_number,
                    "fax_number"        => $request->fax_number,
                    "email"             => $request->email,
                    "contact_person"    => $request->contact_person,
                    "updated_by"        => $request->updated_by
                ]);

            if ($this->customerAddress == 0) {
                return response()->json(["status" => "error", "message" => "failed update data"], 500);
            }

            DB::commit();
            return response()->json(["status" => "ok", "message" => "success update data"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 500]);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "customer_address_id"   =>  "required|exists:customer_address,customer_address_id",
            "deleted_by"            =>  "required",
            "reason"                =>  "required"
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->customerAddress = DB::table("precise.customer_address")
                ->where("customer_address_id", $request->customer_address_id)
                ->delete();

            if ($this->customerAddress == 0) {
                return response()->json(["status" => "error", "message" => "failed delete data"], 500);
            }

            DB::commit();
            return response()->json(["status" => "ok", "message" => "success delete data"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 500]);
        }
    }
}
