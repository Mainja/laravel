<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class programsController extends Controller
{
    public function index() {
        try {
            $programs = DB::table("programs")->get();

            return response()->json([
                'status' => 200,
                'data' => $programs,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getProgramById(Request $request) {
        try {
            $programs = DB::table("programs")->where('id', $request->id)->first();

            return response()->json([
                'status' => 200,
                'data' => $programs,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getProgramsByType(Request $request) {
        try {
            $programs = DB::table("programs")->where('program_type', $request->program_type)->get();

            return response()->json([
                'status' => 200,
                'data' => $programs,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'program_type' => 'required',
                'program_name' => 'required|unique:programs,program_name',                
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::table('programs')->insert([
                'program_name' => $request->program_name,
                'program_code' => $request->program_code,
                'program_slug' => Str::slug($request->program_name),
                'program_type' => $request->program_type,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Program has been created successfully',
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
                'program_type' => 'required',
                'program_name' => 'required|unique:programs,program_name,'.$request->id,                
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 403,
                    'errors' => $validator->errors(),
                ], 403);
            }

            DB::table('programs')->where('id', $request->id)->update([
                'program_name' => $request->program_name,
                'program_code' => $request->program_code,
                'program_slug' => Str::slug($request->program_name),
                'program_type' => $request->program_type,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Program has been updated successfully',
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
            $program = DB::table('programs')->where('id', $request->id)->first();

            if ($program->count() == 1) {
                DB::table('programs')->where('id', $request->id)->delete();

                return response()->json([
                    'status'=> 200,
                    'messages' => 'Program has been deleted successfully',
                ]);
            }
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }
}
