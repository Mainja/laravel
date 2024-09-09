<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class settingsController extends Controller
{
    public function getDeadlines() {
        try {
            $deadlines = DB::table("registration_deadlines")
            ->join('training_years', 'registration_deadlines.year_number', '=', 'training_years.year_number')
            ->join('training_semesters', 'registration_deadlines.semester_number', '=', 'training_semesters.semester_number')
            ->select('registration_deadlines.*', 'training_years.year_label', 'training_semesters.semester_label')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data' => $deadlines,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function addDeadline(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'month_year' => 'required',
                'deadline_date' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $check = DB::table('registration_deadlines')
            ->where([
                'month_year' => $request->month_year,
                'deadline_date' => $request->deadline_date
            ])->first();

            if ($check) {
                return $this->responseMessage(403, "You have already added a deadline date for this month");
            } else {
                DB::table('registration_deadlines')->insert([
                    'month_year' => $request->month_year,
                    'deadline_date' => $request->deadline_date,
                    'penalty_fee' => $request->penalty_fee,
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    private function responseMessage($errorCode, $message) {
        return response()->json([
            'status' => $errorCode,
            'message'=> $message
        ], $errorCode);
    }
}
