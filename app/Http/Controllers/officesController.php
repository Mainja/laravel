<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class officesController extends Controller
{
    public function index() {
        try {
            $offices = DB::table("offices")
            ->select('id', 'office_name', 'number_of_occupants')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $offices,
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
                'office_name' => 'required|unique:offices,office_name',
                'number_of_occupants' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('offices')->insert([
                'office_name' => $request->office_name,
                'number_of_occupants' => $request->number_of_occupants
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Office has been created successfully',
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
                'office_name' => 'required|unique:offices,office_name,'.$request->id,
                'number_of_occupants' => 'required|numeric|min:1' 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('offices')->where('id', $request->id)->update([
                'office_name' => $request->office_name,
                'number_of_occupants' => $request->number_of_occupants
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Office has been updated successfully',
            ], 200);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request) {
        try {
            $office = DB::table('offices')->where('id', $request->id)->first();

            if ($office) {
                DB::table('offices')->where('id', $request->id)->delete();

                return response()->json([
                    'status'=> 200,
                    'message'=> 'Office has been deleted',
                ], 200);
            } else {
                return response()->json([
                    'status'=> 404,
                    'message'=> 'The office you are trying to delete does not exist on this database',
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
