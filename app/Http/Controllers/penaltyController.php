<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class penaltyController extends Controller
{
    public function startWaiver(Request $request) {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();
            $validator = Validator::make($request->all(), [
                'comment' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            // get step number one
            $step_one = DB::table('penalty_clearance_steps')
            ->where('step_number', 1)
            ->first();

            $max_step = DB::table('penalty_clearance_steps')
            ->max('step_number');

            $steps = DB::table('penalty_clearance_steps')
            ->get();

            DB::beginTransaction();

            if (DB::table('penalty_clearance_process')
            ->where('penalty_id', $request->penalty_id)
            ->count() > 0) {
                DB::table('penalty_clearance_process')
                ->where('penalty_id', $request->penalty_id)
                ->delete();
            }

            foreach ($steps as $key => $value) {
                DB::table('penalty_clearance_process')->insert([
                    'penalty_id' => $request->penalty_id,
                    'office_id' => $value->office_id,
                    'step' => $value->step_number,
                    'comment' => $value->office_id == $request->user()->office_id ? $request->comment : '',
                    'status' => 'pending',
                ]);
            }

            DB::table('penalty_clearance_process')
            ->where([
                'penalty_id' => $request->penalty_id,
                'office_id' => $request->user()->office_id
            ])
            ->update([
                'status' => 'completed',
                'date_completed' => $today,
            ]);

            if ($max_step > 1 && $step_one->step_number == 1) {
                DB::table('penalty_clearance_process')
                ->where([
                    'penalty_id' => $request->penalty_id,
                    'step' => $step_one->step_number + 1
                ])
                ->update([
                    'status' => 'active',
                ]);
            }

            // update penalty as pending discussion
            DB::table('student_penalties')
            ->where('id', $request->penalty_id)
            ->update([
                'validity' => 'pending_discussion'
            ]);

            DB::commit();

            return response()->json([
                'status' => 201,
                'message' => 'Submitted successfully',
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getClearanceRequests(Request $request) {
        try {
            $office_requests = DB::table("penalty_clearance_process")
            ->join('student_penalties', 'penalty_clearance_process.penalty_id', '=', 'student_penalties.id')
            ->join('student_payments', 'student_penalties.registration_id', '=', 'student_payments.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('programs', 'student_payments.program_id', '=', 'programs.id')
            ->select('penalty_clearance_process.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name')
            ->where('penalty_clearance_process.office_id', $request->user()->office_id)
            ->where('penalty_clearance_process.status', 'active')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $office_requests,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function penaltyClearanceDetails(Request $request) {
        try {
            $summary = DB::table("student_penalties")
            ->join('student_payments', 'student_penalties.registration_id', '=', 'student_payments.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('programs', 'student_payments.program_id', '=', 'programs.id')
            ->select('students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'student_penalties.validity')
            ->where('student_penalties.id', $request->clearance_id)
            ->first();

            $data = DB::table('penalty_clearance_process')
            ->join('student_penalties', 'penalty_clearance_process.penalty_id', '=', 'student_penalties.id')
            ->join('offices', 'penalty_clearance_process.office_id', '=', 'offices.id')
            ->select('penalty_clearance_process.*', 'offices.office_name')
            ->where('penalty_clearance_process.status', 'completed')
            ->where('student_penalties.validity', '!=', 'active')
            ->where('penalty_clearance_process.penalty_id', $request->clearance_id)
            ->get();

            $current_office = DB::table('penalty_clearance_process')
            ->where('penalty_id', $request->clearance_id)
            ->where('status', 'active')
            ->value('office_id');

            $max_step = DB::table('penalty_clearance_process')
            ->where('penalty_id', $request->clearance_id)
            ->max('step');

            $last_office = DB::table('penalty_clearance_process')
            ->where('penalty_id', $request->clearance_id)
            ->where('step', $max_step)
            ->value('office_id');



            return response()->json([
                'status' => 200,
                'data' => $data,
                'summary' => $summary,
                'last_office' => $last_office,
                'current_office' => $current_office
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function penaltyClearanceFeedback(Request $request) {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();
            $validator = Validator::make($request->all(), [
                'comment' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $current_step = DB::table('penalty_clearance_process')
            ->where('penalty_id', $request->penalty_id)
            ->where('office_id', $request->user()->office_id)
            ->first();

            if ($current_step && $current_step->status == "active") {
                $max_step = DB::table('penalty_clearance_process')
                ->where('penalty_id', $request->penalty_id)
                ->max('step');

                if ($current_step->step < $max_step) {
                    $next_step = $current_step->step + 1;
                    // change status to completed
                    DB::beginTransaction();

                    DB::table('penalty_clearance_process')
                    ->where('penalty_id', $request->penalty_id)
                    ->where('office_id', $request->user()->office_id)
                    ->update([
                        'comment' => $request->comment,
                        'status' => 'completed',
                        'date_completed' => $today,
                    ]);

                    DB::table('penalty_clearance_process')
                    ->where('penalty_id', $request->penalty_id)
                    ->where('step', $next_step)
                    ->update([
                        'status' => 'active'
                    ]);

                    DB::commit();
                } elseif ($current_step->step == $max_step) {
                    DB::beginTransaction();

                    if ($request->final_feedback == "") {
                        return response()->json([
                            'status' => 403,
                            'message' => "Please choose an option as final decision!"
                        ], 403);
                    }

                    DB::table('penalty_clearance_process')
                    ->where('penalty_id', $request->penalty_id)
                    ->where('office_id', $request->user()->office_id)
                    ->update([
                        'comment' => $request->comment,
                        'status' => 'completed',
                        'date_completed' => $today,
                    ]);

                    DB::table('student_penalties')
                    ->where('id', $request->penalty_id)
                    ->update([
                        'validity' => $request->final_feedback
                    ]);

                    DB::commit();
                }
            } else {
                return response()->json([
                    'status'=> 403,
                    'message' => "Your office is not authorized to perform this action",
                ], 403);
            }

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
