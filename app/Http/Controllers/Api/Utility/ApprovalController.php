<?php

namespace App\Http\Controllers\Api\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    private $approval;


    public function show($id)
    {
        $approval = DB::table("precise.approval")
            ->where('approval_id', $id)
            ->first();

        return response()->json($approval, 200);
    }

    public function showByUserID($user)
    {
        if ($user == 2015060)
            $this->approval = DB::table("precise.approval")
                ->get();
        else
            $this->approval = DB::table("precise.approval")
                ->where(function ($query) use ($user) {
                    $query->where('request_to_1', '=', $user)
                        ->orWhere("request_to_2", '=', $user)
                        ->orWhere("request_to_3", '=', $user);
                })->get();

        //if ($this->approval->isEmpty())
        if (count($this->approval) == 0)
            return response()->json(["status" => "error", "message" => "not found", "data" => null], 404);

        return response()->json(["status" => "ok", "message" => "ok", "data" => $this->approval], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'case_id'       =>  'required',
                'request_from'  =>  'required',
                'approval_json' =>  'required|json',
                'request_to_1'  =>  'required',
                'request_to_2'  =>  'nullable',
                'request_to_3'  =>  'nullable'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $id = Str::uuid();
        $this->approval = DB::table("precise.approval")
            ->insert([
                'approval_id'   => $id,
                'case_id'       => $request->case_id,
                'approval_json' => $request->approval_json,
                'request_from'  => $request->request_from,
                'request_to_1'  => $request->request_to_1,
                'request_to_2'  => $request->request_to_2,
                'request_to_3'  => $request->request_to_3,
            ]);

        if ($this->approval == 0)
            return response()->json(['status' => 'error', 'message' => "error insert data"], 500);

        return response()->json(["status" => "ok", "id" => $id, "message" => "success insert data"], 200);
    }

    public function update(Request $request)
    {
        if ($request->action == "Approve")
            $status = 1;
        //else
        //    $status = 0;
        DB::beginTransaction();
        $check = DB::table("precise.approval")
            ->where("approval_id", $request->id)
            ->update([
                "approval_status"   => $status,
                "approval_date_1"   => DB::raw("now()")
            ]);

        $query = DB::table("precise.approval")
            ->where("approval_id", $request->id)
            ->value("approval_json");

        $token = request()->bearerToken();

        // if ($check == 0) {
        //     DB::rollBack();
        //     return response()->json(["status" => "error", "message" => "failed approved"], 500);
        // }

        $parsing = json_decode($query, true);
        $action = $parsing['endpoint'];
        $action_data = $parsing['endpoint_value'];

        $res = Http::acceptJson()->withToken($token, 'bearer')->withBody($action_data, '')->put($action);
        DB::commit();

        $recipient = $parsing['recipient'];
        $bot_token = DB::table("precise.bot")->where("bot_id", 2)->value("bot_token");
        $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";

        if ($res['status'] == 'ok') {
            $telegramID = DB::table("precise.users")->where('user_id', $recipient['reply_to'])->value("telegram_id");
            $message = Http::post($url, ["chat_id" => $telegramID, "parse_mode" => "HTML", "text" => $recipient['reply_text']]);
            $all_messages[] =  json_decode($message, true);
            foreach ($recipient['notify_to'] as $notify) {
                $telegramID = DB::table("precise.users")->where('user_id', $notify['recipient'])->value("telegram_id");
                $message = Http::post($url, ["chat_id" => $telegramID, "parse_mode" => "HTML", "text" => $recipient['notify_text']]);

                $all_messages[] = json_decode($message, true);
            }
        } else {
            foreach ($recipient['failed_reply_to'] as $fail) {
                $telegramID = DB::table("precise.users")->where('user_id', $fail['recipient'])->value("telegram_id");
                $message = Http::post($url, ["chat_id" => $telegramID, "parse_mode" => "HTML", "text" => $recipient['failed_reply_text']]);
                $all_messages[] = json_decode($message, true);
            }
        }
        return response(["message" => $all_messages]);
    }

    public function checkApprovalPrivilege($id, $user)
    {
        $this->approval = DB::table("precise.approval")
            ->where("approval_id", $id)
            ->where(function ($query) use ($user) {
                $query->where('request_to_1', '=', $user)
                    ->orWhere("request_to_2", $user)
                    ->orWhere("request_to_3", $user);
            })->first();

        if (empty($this->approval))
            return response()->json($this->approval, 404);

        if ($this->approval->request_to_1 == $user && !is_null($this->approval->approval_date_1))
            return response()->json(["message" => "accepted"], 200);

        return response()->json($this->approval, 200);
    }
}
