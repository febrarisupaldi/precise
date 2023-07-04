<?php

namespace App\Http\Controllers\Api\Production\Plastic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class PackagingReturnController extends Controller
{
    private $return;
    public function index(): JsonResponse
    {

        $this->return = DB::table('precise.packaging_return_hd')
            ->select(
                'packaging_return_hd_id',
                'return_date',
                'return_number',
                'inspected_by',
                'inspected_on',
                'return_report_note',
                'status_code'
            )
            ->get();

        return response()->json(["status" => "ok", "data" => $this->return], 200);
    }

    public function showDetail($id, $inspected_date): JsonResponse
    {
        $query1 = DB::table("precise.packaging_return_dt as prdt")
            ->where("prdt.packaging_return_hd_id", $id)
            ->select(
                "prdt.packaging_numbering_id"
            )
            ->join("precise.packaging_return_hd as prhd", "prhd.packaging_return_hd_id", "=", "prdt.packaging_return_hd_id");


        $query2 = DB::table(DB::raw("({$query1->toSql()}) as base"))
            ->mergeBindings($query1)
            ->select(
                "base.packaging_numbering_id",
                DB::raw("count(*) as return_count")
            )
            ->leftJoin("precise.packaging_return_dt as prdt", "base.packaging_numbering_id", "=", "prdt.packaging_numbering_id")
            ->groupBy("base.packaging_numbering_id");

        $this->return = DB::table("precise.packaging_return_dt", "prdt")
            ->mergeBindings($query2)
            ->where("prdt.packaging_return_hd_id", $id)
            ->whereRaw("date(prdt.inspected_on) = ?", [$inspected_date])
            ->select(
                "prdt.packaging_return_dt_id",
                "prdt.packaging_return_hd_id",
                "prhd.return_date",
                "prhd.return_number",
                "prdt.packaging_numbering_id",
                "pn.packaging_number",
                "pn.status_code",
                "base.return_count",
                "prdt.inspected_by",
                "prdt.inspected_on",
                "prdt.is_usable",
                "prdt.description"
            )
            ->join("precise.packaging_return_hd as prhd", "prdt.packaging_return_hd_id", "=", "prhd.packaging_return_hd_id")
            ->leftJoin("precise.packaging_numbering as pn", "prdt.packaging_numbering_id", "=", "pn.packaging_numbering_id")
            ->leftJoin(DB::raw("({$query2->toSql()})as base"), "prdt.packaging_numbering_id", "=", "base.packaging_numbering_id")
            ->get();

        if (count($this->return) == 0)
            return response()->json(["status" => "error", "data" => $this->return, "message" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->return], 200);
    }

    function getCummulativeByID($headerID)
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $this->return = DB::table("precise.packaging_return_hd", "hd")
            ->where("hd.packaging_return_hd_id", $headerID)
            ->select(
                "hd.packaging_return_hd_id",
                DB::raw("
                    DATE_FORMAT(dt.inspected_on, '%Y-%m-%d') as inspected_date,
                    SUM(IF(dt.is_usable = 1, 0, 1)) AS count_good,
                    SUM(IF(dt.is_usable = 0, 0, 1)) AS count_ng,
                    COUNT(dt.is_usable) AS total
                ")
            )
            ->leftJoin("precise.packaging_return_dt as dt", "hd.packaging_return_hd_id", "=", "dt.packaging_return_hd_id")
            ->groupBy("inspected_date")
            ->get();
        DB::statement("SET sql_mode=(SELECT CONCAT(@@sql_mode, ',ONLY_FULL_GROUP_BY'));");

        if (count($this->return) == 0)
            return response()->json(["status" => "error", "message" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->return], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'return_date'           => 'required|date',
            'return_number'         => 'required',
            'inspected_by'          => 'required',
            'return_report_note'    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $id = DB::table("precise.packaging_return_hd")
                ->insertGetId([
                    "return_date"           => $data['return_date'],
                    "return_number"         => $data['return_number'],
                    "inspected_by"          => $data['inspected_by'],
                    "return_report_note"    => $data['return_report_note']
                ]);

            if (empty($id)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            }

            foreach ($data["detail"] as $detail) {
                $validator = Validator::make($detail, [
                    "packaging_numbering_id"    => "required",
                    "is_usable"                 => "required",
                    "desc"                      => "nullable"
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                }

                $value = [
                    "packaging_return_hd_id"    => $id,
                    "packaging_numbering_id"    => $detail["packaging_numbering_id"],
                    "is_usable"                 => $detail["is_usable"],
                    "description"               => $detail["desc"]
                ];

                $this->return = DB::table("precise.packaging_return_dt")
                    ->insert($value);

                if ($this->return == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                }

                $this->return = DB::table("precise.packaging_numbering")
                    ->where("packaging_numbering_id", $detail["packaging_numbering_id"])
                    ->update([
                        "status_code"   => $detail["status_code"]
                    ]);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function createHeader(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_date'           => 'required|date',
            'return_number'         => 'required',
            'inspected_by'          => 'required',
            'return_report_note'    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->return = DB::table("precise.packaging_return_hd")
            ->insert([
                "return_date"           => $request->return_date,
                "return_number"         => $request->return_number,
                "inspected_by"          => $request->inspected_by,
                "return_report_note"    => $request->return_report_note
            ]);

        if ($this->return == 0)
            return response()->json(['status' => 'ok', 'message' => 'failed input data'], 500);
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function createDetail(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "packaging_return_hd_id"    => "required|exists:packaging_return_hd,packaging_return_hd_id",
            "packaging_numbering_id"    => "required",
            "is_usable"                 => "required",
            "status_code"               => "required",
            "desc"                      => "nullable"
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();

        $this->return = DB::table("precise.packaging_return_dt")
            ->insert([
                "packaging_return_hd_id"    => $request->packaging_return_hd_id,
                "packaging_numbering_id"    => $request->packaging_numbering_id,
                "is_usable"                 => $request->is_usable,
                "description"               => $request->desc
            ]);

        if ($this->return == 0) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => "server error"], 500);
        }

        $this->return = DB::table("precise.packaging_numbering")
            ->where("packaging_numbering_id", $request->packaging_numbering_id)
            ->update([
                "status_code"   => $request->status_code
            ]);

        if ($this->return == 0) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => "server error"], 500);
        }

        DB::commit();
        return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
    }

    public function createReturnClosed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "packaging_return_hd_id"     => "required|exists:packaging_return_hd,packaging_return_hd_id",
            "status_code"               => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->return = DB::table("precise.packaging_return_hd")
            ->where("packaging_return_hd_id", $request->packaging_return_hd_id)
            ->update([
                "status_code"   => $request->status_code
            ]);

        if ($this->return == 0) {
            return response()->json(['status' => 'error', 'message' => "error update data"], 500);
        }

        return response()->json(['status' => 'ok', 'message' => "success update data"], 200);
    }
}
