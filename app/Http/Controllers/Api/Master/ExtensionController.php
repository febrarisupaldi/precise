<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExtensionController extends Controller
{
    private $extension;
    public function index(): JsonResponse
    {
        $this->extension = DB::table('precise.extension')
            ->select(
                'extension_name',
                'extension_description',
                DB::raw(
                    "
                    if(is_active = 1, 'Aktif', 'Tidak aktif') 'Status aktif'"
                ),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        return response()->json(['status' => 'ok', 'data' => $this->extension], 200);
    }

    public function show($id): JsonResponse
    {
        $this->extension = DB::table('precise.extension')
            ->where('extension_name', $id)
            ->select(
                'extension_name',
                'extension_description',
                'is_active'
            )
            ->first();
        if (empty($this->extension)) {
            return response()->json($this->extension, 404);
        }
        return response()->json($this->extension, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'extension_name'    => 'required|unique:extension',
            'desc'              => 'required',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->extension = DB::table('precise.extension')
            ->insert([
                'extension_name'        => $request->extension_name,
                'extension_description' => $request->desc,
                'created_by'            => $request->created_by
            ]);

        if ($this->extension == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
        }

        return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'extension_name'    => 'required',
            'desc'              => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->extension = DB::table('extension')
                ->where('extension_name', $request->extension_id)
                ->update([
                    'extension_name'        => $request->extension_name,
                    'extension_description' => $request->desc,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->extension == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        }
    }

    public function destroy($id)
    {
        $checkExtension = DB::table('extension')->where('extension_name', $id)->delete();
        if ($checkExtension == 1) {
            return response()->json(['status' => 'ok', 'message' => 'Extension has been deleted']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete extension']);
        }
    }

    public function check($type, $val)
    {
        if ($type == 'name') {
            $checkExtension = DB::table('color_type')->where('extension_name', $val)->count();
        }
        return response()->json(['status' => 'ok', 'message' => $checkExtension]);
    }
}
