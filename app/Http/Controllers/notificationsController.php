<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class notificationsController extends Controller
{
    public function index() {
        try {
            $notifications = DB::table("notifications")
            ->select('id', 'title', 'details', 'dateposted')
            ->paginate();

            return response()->json([
                'status' => 200,
                'data' => $notifications,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function dashboardNotifications() {
        try {
            $latest_post = DB::table('notifications')->orderby('dateposted', 'desc')->first();

            // $other_posts = DB::table('notifications')
            // ->orderby('dateposted', 'desc')
            // ->where('id', '!=', $latest_post->id)
            // ->get();

            return response()->json([
                'status'=> 200,
                'latest_post'=> $latest_post,
                // 'other_posts' => $other_posts,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required', 
                'details' => 'required'               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('notifications')->insert([
                'author' => $request->user()->id,
                'dateposted' => $today,
                'title' => $request->title,
                'details' => $request->details,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Notification added successfully',
            ], 201);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required', 
                'details' => 'required'               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('notifications')->where('id', $request->id)->update([
                'title' => $request->title,
                'details' => $request->details,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Notification updated successfully',
            ], 201);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request) {
        try {
            $data = DB::table('notifications')->where('id', $request->id)->first();
            if ($data) {
                DB::table('notifications')->where('id', $request->id)->delete();

                return response()->json([
                    'status'=> 200,
                    'message'=> 'Notification has been deleted'
                ], 200);
            } else {
                return response()->json([
                    'status'=> 404,
                    'message'=> 'The notificatin you are trying to delete does not exist'
                ], 404);
            }
            
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }
}
