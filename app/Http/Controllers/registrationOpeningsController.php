<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class registrationOpeningsController extends Controller
{
    public function index() {
        try {
            $openings = DB::table("registration_openings")
            ->select('id', 'description', 'start_date', 'end_date', 'penalty_fee')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data' => $openings,
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
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required|unique:registration_openings,description', 
                'start_date' => 'required',           
                'end_date' => 'required',
                'penalty_fee' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('registration_openings')->insert([
                'description'=> $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'penalty_fee' => $request->penalty_fee
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Registration opening has been created successfully',
            ], 200);
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
                'description' => 'required|unique:registration_openings,description,'.$request->id, 
                'start_date' => 'required',           
                'end_date' => 'required',
                'penalty_fee' => 'required|numeric|min:1'      
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('registration_openings')->where('id', $request->id)->update([
                'description'=> $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'penalty_fee' => $request->penalty_fee
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Registration opening has been updated successfully',
            ], 200);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }
}
