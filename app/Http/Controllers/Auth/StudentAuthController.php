<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Student;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validateUser = Validator::make($request->all(),
            [
                'computer_number' => 'required',
                'password' => 'required',
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => 422,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 422);
            }

            $student = Student::where('computer_number', $request->computer_number)->first();
    
            if(!$student || !Hash::check($request->password, $student->password)){
                return response()->json([
                    'status' => 404,
                    'message' => 'Wrong credentials. Please enter a correct computer number and password.',
                ], 404);
            } else {
                $program_name = DB::table('programs')->where('id', $student->program_id)->value('program_name');
                $intake_name = DB::table('intakes')->where('id', $student->program_id)->value('label');
                return response()->json([
                    'student' => $student,
                    'program_name' => $program_name,
                    'intake_name' => $intake_name,
                    'token' => $student->createToken('mobile', ['role:student'])->plainTextToken
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function logout(Request $req) {

        // $req->user()->tokens()->delete();

        $req->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'You have been logged out successfully'
        ], 200);
    }
}
