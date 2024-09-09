<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class academicSetupController extends Controller
{
    private function renderOfficeName($office_id) {
        return DB::table("offices")->where("id", $office_id)->value('office_name');
    }
    
    public function studentRegistration(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'student_category' => 'required',      
                'program' => 'required'        
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $step = 0;

            foreach ($request->steps as $key => $value) {
                if (DB::table('registration_steps')->where([
                    'student_category' => $request->student_category,
                    'office_id' => $value['office'],
                    'program_id' => $request->program
                ])->count()) {
                    return response()->json([
                        'status' => 403,
                        'message'=> 'You have already added '.$this->renderOfficeName($value['office']).' for the chosen student category!'
                    ], 403);
                }
                
                $step +=1;
                
                $step_id = DB::table('registration_steps')->insertGetId([
                    'student_category' => $request->student_category,
                    'program_id' => $request->program,
                    'office_id' => $value['office'],
                    'step' => $step,
                ]);

                if ($value['requirements'] != "") {
                    foreach ($value['requirements'] as $data) {
                        DB::table('registration_requirements')->insert([
                            'step_id' => $step_id,
                            'item_id' => $data['item'],
                            'quantity' => $data['quantity'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'message'=> 'Registration setup has been created successfully'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function penaltyClearanceSteps(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'step_number' => 'required',      
                'office' => 'required'        
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (DB::table('penalty_clearance_steps')->where([
                'step_number' => $request->step_number,
            ])->count()) {
                return response()->json([
                    'status' => 403,
                    'message'=> 'You have already added step number'.$request->step_number.' !'
                ], 403);
            } elseif (DB::table('penalty_clearance_steps')->where([
                'office_id' => $request->office,
            ])->count()) {
                return response()->json([
                    'status' => 403,
                    'message'=> 'You have already added '.$this->renderOfficeName($request->office).' !'
                ], 403);
            } else {
                $max_step = DB::table('penalty_clearance_steps')
                ->max('step_number');

                if ($request->step_number > $max_step + 1) {
                    return response()->json([
                        'status' => 403,
                        'message'=> 'You have skipped some step(s) from the last step number!'
                    ], 403);
                } else {
                    DB::table('penalty_clearance_steps')->insert([
                        'office_id' => $request->office,
                        'step_number' => $request->step_number,
                    ]);

                    
                    DB::commit();

                    return response()->json([
                        'status' => 200,
                        'message'=> 'Penalty clearance step created successfully'
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function penaltyClearanceIndex() {
        try {
            $steps = DB::table('penalty_clearance_steps as steps')
            ->join('offices', 'steps.office_id', '=', 'offices.id')
            ->select('steps.*', 'offices.office_name')
            ->orderBy('step_number', 'asc')
            ->get();

            return response()->json([
                'status'=> 200,
                'data' => $steps,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getOfficeRequirements(Request $request) {
        try {
            $requirements = DB::table('student_registration_progress as srp')
            ->join('registration_steps as rs', 'srp.step_id', '=', 'rs.id')
            ->join('student_payments as sp', 'srp.registration_id', '=', 'sp.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->select('srp.*', 'training_years.year_label', 'training_semesters.semester_label', 'students.name', 'students.computer_number', 'sp.year_id', 'sp.program_id', 'sp.semester_id', 'sp.student_id')
            ->where('rs.office_id', $request->user()->office_id)
            ->where('status', 'progress')
            ->get();

            return response()->json([
                'status'=> 200,
                'data' => $requirements,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getOfficeRequirementsByStep(Request $request) {
        try {
            $summary = DB::table('student_registration_progress as srp')
            ->join('registration_steps as rs', 'srp.step_id', '=', 'rs.id')
            ->join('student_payments as sp', 'srp.registration_id', '=', 'sp.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->join('programs', 'sp.program_id', '=', 'programs.id')
            ->select('srp.id', 'sp.year_id', 'sp.program_id', 'sp.semester_id', 'rs.office_id', 'sp.student_id', 'students.name', 'students.computer_number', 'programs.program_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('srp.id', $request->request_id)
            ->first();

            $requirements = DB::table('registration_requirements as rr')
            ->join('inventory_items', 'rr.item_id', '=', 'inventory_items.id')
            ->select('rr.id', 'rr.quantity', 'rr.item_id', 'inventory_items.item_name')
            ->where('rr.step_id', $request->step_id)
            ->get();

            return response()->json([
                'status'=> 200,
                'data' => $requirements,
                'summary' => $summary
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
