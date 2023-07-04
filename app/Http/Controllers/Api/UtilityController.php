<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UtilityController extends Controller
{
    public function sendMessageTelegram(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
                'message' => 'required',
                'bot_id'  => ['required', Rule::in([2, 3])]
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $bot = DB::table("precise.bot")->where("bot_id", $request->bot_id)->first();
            try {
                $telegram_id = DB::table('users')
                    ->where('user_id', $request->user_id)
                    ->value('telegram_id');

                if ($telegram_id == null) {
                    return response()->json(['status' => 'error', 'message' => 'no telegram account'], 500);
                } else {
                    $bot = DB::table("precise.bot")->where("bot_id", $request->bot_id)->first();
                    $url = "https://api.telegram.org/bot" . $bot->bot_token . "/sendMessage?chat_id=" . $telegram_id . "&parse_mode=HTML&text=" . urlencode($request->message);
                    $ch = curl_init();
                    $optArray = array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true
                    );
                    curl_setopt_array($ch, $optArray);
                    $result = json_decode(curl_exec($ch));
                    curl_close($ch);
                    if (empty($result))
                        return response()->json(['status' => 'error'], 500);
                    return response()->json(['status' => $result->ok], 200);
                }
            } catch (\Exception $e) {
                return response()->json(["status" => "error", "message" => $bot], 500);
            }
        }
    }

    public static function getTransactionNumber($type): JsonResponse
    {
        $data = DB::select("select precise.get_transaction_number(?,now()) as number", [$type]);
        return response()->json(["number" => $data[0]->number]);
    }

    public function createTemporaryProduct(Request $request)
    {
        DB::beginTransaction();
        try {
            $delete = DB::statement("truncate precise.tmp_product_code");

            if (!$delete) {
                DB::rollBack();
                return response()->json(["status" => "error", "message" => "server error"], 500);
            }

            $insert = DB::table("precise.tmp_product_code")
                ->insert([
                    "product_code"  => $request->product_code
                ]);
            DB::commit();
        } catch (\Exception $e) {
        }
    }
}
