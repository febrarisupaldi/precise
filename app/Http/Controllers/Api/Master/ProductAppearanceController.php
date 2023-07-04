<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductAppearanceController extends Controller
{
    private $productAppearance;
    public function index(): JsonResponse
    {
        $this->productAppearance = DB::table('precise.product_appearance')
            ->select(
                'appearance_id',
                'appearance_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )->get();
        return response()->json(['status' => 'ok', 'data' => $this->productAppearance], 200);
    }

    public function show($id): JsonResponse
    {
        $this->productAppearance = DB::table('precise.product_appearance')
            ->where('appearance_id', $id)
            ->select(
                'appearance_id',
                'appearance_name'
            )
            ->first();

        if (empty($this->productAppearance))
            return response()->json("not found", 404);

        return response()->json($this->productAppearance, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appearance_name'   => 'required|unique:product_appearance,appearance_name',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->productAppearance = DB::table('precise.product_appearance')
            ->insert([
                'appearance_name'   => $request->appearance_name,
                'created_by'        => $request->created_by
            ]);

        if ($this->productAppearance == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appearance_id'     => 'required|exists:product_appearance,appearance_id',
            'appearance_name'   => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productAppearance = DB::table('precise.product_appearance')
                ->where('appearance_id', $request->appearance_id)
                ->update([
                    'appearance_name'   => $request->appearance_name,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->productAppearance == 0) {
                DB::rollBack();
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
}
