<?php

namespace App\Http\Controllers;

use App\Mail\sendBulkMessage;
use Illuminate\Http\Request;
use App\Mail\sendMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class messageController extends Controller
{
    public function sendMessage(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'sender_email' => 'required|email',
            'sent_message' => 'required',
            'phone_number' => 'required'
        ], [
            'sent_message.required' => 'Please type a message to send!',
            'sender_email.required' => 'Your email is required',
            'sender_email.email' => 'Please type in a valid email address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=> 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        Mail::to('pominstitute@gmail.com')->send(new sendMessage($request->name, $request->sender_email, $request->sent_message, $request->phone_number));

        return response()->json([
            'status'=> 200,
            'message'=> 'Message sent successfully',
        ], 200);
    }

    public function sendBukMail(Request $request) {
        $validator = Validator::make($request->all(), [
            'intake' => 'required',
            'heading' => 'required',
            'sent_message' => 'required',
        ], [
            'sent_message.required' => 'Please type a message to send!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=> 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        // get all students in the specified intake
        $students = DB::table('students')->where('intake_id', $request->intake)->select('email')->get();

        foreach ($students as $value) {
            if ($value->email != "") {
                Mail::to($value->email)->send(new sendBulkMessage($request->heading, $request->sent_message));
            }
        }

        return response()->json([
            'status'=> 200,
            'message'=> 'Message sent successfully',
        ], 200);
    }
}
