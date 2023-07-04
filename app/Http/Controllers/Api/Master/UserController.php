<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{
    private $user;
    public function index(): JsonResponse
    {
        $this->user = DB::table('precise.users as u')
            ->select(
                'user_id',
                'employee_name',
                'email_internal',
                'email_external',
                'u.is_active',
                'e.is_active',
                'u.created_on',
                'u.created_by',
                'u.updated_on',
                'u.updated_by'
            )->leftJoin('employee as e', 'u.user_id', '=', 'e.employee_nik')
            ->get();
        return response()->json(['status' => 'ok', 'data' => $this->user], 200);
    }

    public function show($id): JsonResponse
    {
        $this->user = DB::table('precise.users as u')
            ->where('user_id', $id)
            ->select(
                'user_id',
                'employee_name',
                'email_internal',
                'email_external',
                'main_phone_number',
                'telegram_id',
                'u.is_active'
            )->leftJoin('employee as e', 'u.user_id', '=', 'e.employee_nik')
            ->first();
        if (empty($this->user))
            return response()->json("not found", 404);
        return response()->json($this->user, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|unique:users,user_id',
            'password'          => 'nullable',
            'email_internal'    => 'nullable|email',
            'email_external'    => 'nullable|email',
            'main_phone_number' => 'nullable',
            'telegram_id'       => 'nullable',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if (empty($request->password))
                $pass = 123456;
            else
                $pass = $request->password;
            $this->user = DB::table('precise.users')->insert([
                'user_id'           => $request->user_id,
                'password'          => bcrypt($pass),
                'email_internal'    => $request->email_internal,
                'email_external'    => $request->email_external,
                'main_phone_number' => $request->main_phone_number,
                'telegram_id'       => $request->telegram_id,
                'created_by'        => $request->created_by
            ]);

            if ($this->user == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
            }
            return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,user_id',
            'email_internal'    => 'nullable|email',
            'email_external'    => 'nullable|email',
            'main_phone_number' => 'nullable',
            'telegram_id'       => 'nullable',
            'is_active'         => 'nullable',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->user = DB::table('precise.users')
                ->where('user_id', $request->user_id)
                ->update([
                    'email_internal'    => $request->email_internal,
                    'email_external'    => $request->email_external,
                    'main_phone_number' => $request->main_phone_number,
                    'is_active'         => $request->is_active,
                    'telegram_id'       => $request->telegram_id,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->user == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == 'id') {
                $this->user = DB::table('precise.users')
                    ->where('user_id', $value)
                    ->count();
            }
            if ($this->user == 0)
                return response()->json(['status' => 'error', 'message' => $this->user], 404);
            return response()->json(['status' => 'ok', 'message' => $this->user], 200);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,user_id',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->user = DB::table('precise.users')
                ->where('user_id', $request->user_id)
                ->update([
                    'password' => bcrypt(123456)
                ]);

            if ($this->user == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed reset password'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success reset password'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id'       => 'required|exists:users,user_id',
                'old_password'  => 'required',
                'new_password'  => 'required'
            ]

        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->user = DB::table('precise.users')
            ->select('password')
            ->where('user_id', $request->user_id)
            ->first();

        if (empty($this->user))
            return response()->json(['status' => 'error', 'message' => 'user not found'], 500);
        if (Hash::check($request->old_password, $this->user->password)) {
            DB::beginTransaction();
            try {
                DBController::reason($request, "update");
                $this->user = DB::table('precise.users')
                    ->where('user_id', $request->user_id)
                    ->update([
                        'password' => bcrypt($request->new_password)
                    ]);

                if ($this->user == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'failed update password'], 500);
                }
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update password'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'old password not match'], 500);
        }
    }
}
