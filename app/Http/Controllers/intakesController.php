<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class intakesController extends Controller
{
    public function index() {
        try {
            $intakes = DB::table("intakes")
            ->select('id', 'label', 'month', 'year', 'intake_code', 'designation')
            ->paginate(20);

            return response()->json([
                'status' => 200,
                'data' => $intakes,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getIntakeById(Request $request) {
        try {
            $intakes = DB::table("intakes")
            ->select('id', 'label', 'month', 'year', 'intake_code', 'designation')
            ->where('id', $request->intake_id)
            ->first();

            return response()->json([
                'status' => 200,
                'data' => $intakes,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function intakeByProgramType(Request $request) {
        try {
            $intakes = DB::table("intakes")
            ->select('id', 'label', 'month', 'year', 'intake_code', 'designation')
            ->where('designation', $request->program_type)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $intakes,
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
                'designation' => 'required',
                'name_of_intake' => 'required|unique:intakes,label', 
                'year' => 'required'               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $exp = explode('-', $request->year);

            DB::table('intakes')->insert([
                'designation' => $request->designation,
                'label' => $request->name_of_intake,
                'month' => $exp[1],
                'year' => $exp[0],
                'intake_code' => Carbon::createFromFormat('Y', $exp[0])->format('y').$exp[1],
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Intake has been created successfully',
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
                'designation' => 'required',
                'name_of_intake' => 'required|unique:intakes,label,'.$request->id, 
                'year' => 'required'               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $exp = explode('-', $request->year);

            DB::table('intakes')->where('id', $request->id)->update([
                'designation' => $request->designation,
                'label' => $request->name_of_intake,
                'month' => $exp[1],
                'year' => $exp[0],
                'intake_code' => Carbon::createFromFormat('Y', $exp[0])->format('y').$exp[1],
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Intake has been updated successfully',
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
