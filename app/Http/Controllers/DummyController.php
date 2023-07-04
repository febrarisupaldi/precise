<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class DummyController extends Controller
{
    private $dummy;
    public function index()
    { 
        $this->dummy = DB::table('precise.')
            ->select()
            ->get();

        return response()->json(["data"=>$this->dummy], 200);
    }

    public function show($id)
    {
        $this->dummy = DB::table('precise.')
            ->where('',$id)
            ->select()
            ->first();

        return response()->json($this->dummy, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->dummy = DB::table('precise.')
                ->insert([
                    'created_by'        => $request->created_by
                ]);
            if ($this->dummy == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create dummy, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'dummy has been created']);
            }
        }
    }

    public function update(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'reason'        => 'required',
            'updated_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->dummy = DB::table('precise.')
                    ->where('', $request)
                    ->update([
                        'updated_by'        => $request->updated_by
                    ]);
                if($this->dummy == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update dummy, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'dummy has been updated']);
                }
            }catch(\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            QueryController::reasonAction("delete");
            $this->dummy = DB::table('precise.')
                ->where('', $id)
                ->delete();

            if ($this->dummy == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete dummy, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'dummy has been deleted']);
            }
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request)
    {
        $type   = $request->get('type');
        $value  = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code") {
                $this->dummy = DB::table('precise.')
                    ->where('', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->dummy]);
        }
    }
}
