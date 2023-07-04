<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|string',
            'password'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $credentials = $request->only(['user_id', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user_id = $this->me()->getData()->data->user_id;
        $log = DB::table('log_user_login')->insertGetId([
            'user_id'   => $user_id,
            'login_on'  => DB::raw('sysdate()')
        ]);

        if ($request->is_send_otp == 0 || $request->is_send_otp == null) {
            return response()->json([
                'access_token'  => $token,
                'expires_in'    => Auth::factory()->getTTL() * 60,
                'log_id'        => $log,
            ], 200);
        } else {
            $telegram = $this->sendMessageTelegram();
            if ($telegram->getData()->status); {
                return response()->json([
                    'access_token'  => $token,
                    'expires_in'    => Auth::factory()->getTTL() * 60,
                    'log_id'        => $log,
                    'otp'           => $telegram->getData()->otp
                ], 200);
            }
        }
    }

    public function me(): JsonResponse
    {
        return response()->json(["data" => auth()->user()], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $log = DB::table('log_user_login')
            ->where('log_id', $request->log_id)
            ->update(['logout_on' => DB::raw("sysdate()")]);
        if ($log == 1) {
            auth()->logout();
            return response()->json(['message' => 'Successfully logged out'], 200);
        } else {
            return response()->json(['message' => 'Failed logged out'], 500);
        }
    }

    public function refresh(): JsonResponse
    {
        $token =  Auth::guard()->refresh(Auth::getToken());
        //$user = JWTAuth::setToken($token)->toUser();
        return response()->json([
            'access_token'  => $token
        ], 200);
    }

    private function randomOTP()
    {
        $otp = rand(1000, 9999);
        return $otp;
    }

    public function sendMessageTelegram(): JsonResponse
    {
        $chatID = $this->me()->getData()->data->telegram_id;
        $message = $this->randomOTP();
        $bot = DB::table("precise.bot")->where("bot_id", 1)->first();
        $url = "https://api.telegram.org/bot" . $bot->bot_token . "/sendMessage?chat_id=" . $chatID . "&text=" . urlencode("your otp code for login:") . $message;
        $ch = curl_init();
        $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        );
        curl_setopt_array($ch, $optArray);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);
        return response()->json(['status' => $result->ok, 'otp' => $message]);
    }
}
