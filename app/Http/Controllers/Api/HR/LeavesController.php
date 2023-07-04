<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeavesController extends Controller
{
    private $leaves;
    public function index()
    {
    }

    public function show($id): JsonResponse
    {
        $this->leaves = DB::table("dbhrd.master_cuti as mc")
            ->select(
                'mc.NIP',
                'nd.NAMA',
                'nd.KETSEX',
                'nd.TGLMASUK',
                'nd.BAGIAN',
                'nd.NM_JABATAN',
                'nd.KETSTKERJA',
                'mc.MULAI',
                'mc.SAMPAI',
                'mc.CUTI'
            )
            ->leftJoin("dbhrd.newdatakar as nd", "mc.NIP", "=", "nd.NIP")
            ->where("mc.NIP", $id)
            ->first();

        if (empty($this->leaves))
            return response()->json("not found", 404);
        return response()->json($this->leaves, 200);
    }
}
