<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BarcodeController extends Controller
{
    private $barcode;
    public function index(): JsonResponse
    {
        $this->barcode = DB::table('precise.tmp_barcode as a')
            ->select(
                "a.barcode_id",
                "a.item_code",
                "a.design_code",
                "a.ean13",
                "a.brand_id",
                "b.product_brand_name",
                "a.item_description_id",
                "a.item_description_en",
                "a.is_active",
                "a.created_on",
                "a.created_by",
                "a.updated_on",
                "a.updated_by"
            )
            ->leftJoin("precise.product_brand as b", "a.brand_id", "=", "b.product_brand_id")
            ->get();

        return response()->json(["status" => "ok", "data" => $this->barcode], 200);
    }

    public function show($id): JsonResponse
    {
        $this->barcode = DB::table("precise.tmp_barcode as a")
            ->where("a.barcode_id", $id)
            ->select(
                "a.barcode_id",
                "b.item_id",
                "a.item_code",
                "a.ean13",
                "c.design_id",
                "a.design_code",
                "a.brand_id",
                "d.product_brand_name",
                "a.item_description_id",
                "a.item_description_en",
                "a.is_active",
                "a.created_on",
                "a.created_by",
                "a.updated_on",
                "a.updated_by"
            )
            ->leftJoin("precise.product_item as b", "a.item_code", "=", "b.item_code")
            ->leftJoin("precise.product_design as c", "a.design_code", "=", "c.design_code")
            ->leftJoin("precise.product_brand as d", "a.brand_id", "=", "d.product_brand_id")
            ->first();

        if (empty($this->barcode)) {
            return response()->json("not found", 404);
        }

        return response()->json($this->barcode, 200);
    }


    public function showByEan13($ean13): JsonResponse
    {
        $this->barcode = DB::table("precise.tmp_barcode as a")
            ->where("a.ean13", $ean13)
            ->select(
                "a.barcode_id",
                "b.item_id",
                "a.item_code",
                "a.ean13",
                "c.design_id",
                "a.design_code",
                "a.brand_id",
                "d.product_brand_name",
                "a.item_description_id",
                "a.item_description_en",
                "a.is_active",
                "a.created_on",
                "a.created_by",
                "a.updated_on",
                "a.updated_by"
            )
            ->leftJoin("precise.product_item as b", "a.item_code", "=", "b.item_code")
            ->leftJoin("precise.product_design as c", "a.design_code", "=", "c.design_code")
            ->leftJoin("precise.product_brand as d", "a.brand_id", "=", "d.product_brand_id")
            ->first();
        if (empty($this->barcode)) {
            return response()->json("error", 404);
        }

        return response()->json($this->barcode, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'item_code'     => 'required',
                'design_code'   => 'required',
                'brand_id'      => 'required|exists:product_brand,product_brand_id',
                'item_desc_id'  => 'required',
                'item_desc_en'  => 'required',
                'created_by'    => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $generateBarcode = DB::select("select precise.get_new_ean13() as barcode;");
            $barcodeNumber = $generateBarcode[0]->barcode;

            $this->barcode = DB::table("precise.tmp_barcode")
                ->insert([
                    "item_code"             => $request->item_code,
                    "design_code"           => $request->design_code,
                    "ean13"                 => $barcodeNumber,
                    "brand_id"              => $request->brand_id,
                    "item_description_id"   => $request->item_desc_id,
                    "item_description_en"   => $request->item_desc_en,
                    "created_by"            => $request->created_by
                ]);

            if ($this->barcode == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            }
            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'barcode_id'    => 'required|exists:tmp_barcode,barcode_id',
                'item_code'     => 'required',
                'design_code'   => 'required',
                'brand_id'      => 'required|exists:product_brand,product_brand_id',
                'item_desc_id'  => 'required',
                'item_desc_en'  => 'required',
                'reason'        => 'required',
                'is_active'     => 'required',
                'updated_by'    => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->barcode = DB::table("precise.tmp_barcode")
                ->where("barcode_id", $request->barcode_id)
                ->update([
                    "item_code"             => $request->item_code,
                    "design_code"           => $request->design_code,
                    "brand_id"              => $request->brand_id,
                    "item_description_id"   => $request->item_desc_id,
                    "item_description_en"   => $request->item_desc_en,
                    "is_active"             => $request->is_active,
                    "updated_by"            => $request->updated_by
                ]);

            if ($this->barcode == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode_id'        => 'required|exists:tmp_barcode,barcode_id',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->barcode = DB::table('precise.tmp_barcode')
                ->where('barcode_id', $request->barcode_id)
                ->delete();

            if ($this->barcode == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'failed delete data'], 500);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'item'  => 'required',
            'design' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->barcode = DB::table('precise.tmp_barcode')
                ->where('item_code', $request->get('item'))
                ->where('design_code', $request->get('design'))
                ->count();

            if ($this->barcode == 0)
                return response()->json(['status' => 'ok', 'message' => $this->barcode], 404);

            return response()->json(['status' => 'ok', 'message' => $this->barcode], 200);
        }
    }

    public function convert(Request $request): JsonResponse
    {
        $url = "https://api.labelary.com/v1/printers/8dpmm/labels/" . $request->size . "/0/";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->zpl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
            return response()->json(["status" => "ok", "data" => base64_encode($result)]);
        } else {
            return response()->json(["status" => "error", "data" => $result]);
        }
        curl_close($curl);
    }
}
