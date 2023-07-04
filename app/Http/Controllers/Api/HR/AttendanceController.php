<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    private $attendance;

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nip'           => 'required',
                'latitude'      => 'required',
                'longtitude'    => 'required',
                'country'       => 'required',
                'locality'      => 'required',
                'address'       => 'required',
                'mocking'       => 'required',
                'ipaddress'     => 'required|ipv4'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->attendance = DB::table('dbhrd.absen')
                ->insert([
                    "TANGGAL"       => DB::raw('now()'),
                    "WAKTU"         => DB::raw('now()'),
                    "NIP"           => $request->nip,
                    "LATITUDE"      => $request->latitude,
                    "LONGTITUDE"    => $request->longtitude,
                    "COUNTRY"       => $request->country,
                    "LOCALITY"      => $request->locality,
                    "ADDRESS"       => $request->address,
                    "MOCKING"       => $request->mocking,
                    "IPADDRESS"     => $request->ipaddress,
                    "TGLCREATE"     => DB::raw('now()')
                ]);

            if ($this->attendance == 0) {
                return response()->json(['status' => 'error', 'message' => 'Server error'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success'], 200);
        }
    }
}
