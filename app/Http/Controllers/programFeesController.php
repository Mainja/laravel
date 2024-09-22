<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class programFeesController extends Controller
{
    public function index() {
        try {
            $program_intakes = DB::table('program_fees')
            ->join('programs', 'program_fees.program_id', '=', 'programs.id')
            ->join('intakes', 'program_fees.intake_id', '=', 'intakes.id')
            ->select('program_fees.*', 'intakes.intake_code', 'programs.program_name', 'intakes.label')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data'=> $program_intakes
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    private function getLevelNames($year_id, $semester_id) {
        $year_name = DB::table('training_years')->where('id', $year_id)->value('year_label');
        $semester_name = DB::table('training_semesters')->where('id', $semester_id)->value('semester_label');

        return [
            'year' => $year_name,
            'semester' => $semester_name
        ];
    }
    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'program' => 'required',
                'intake' => 'required',
            ], [
                'program.required' => 'The program of study is required.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            // if (sizeof($request->levels) == 0) {
            //     return response()->json([
            //         'status' => 403,
            //         'message' => 'Please add fees to levels of study!'
            //     ], 403);
            // }

            $check_program_intake = DB::table('program_fees')
            ->where(['program_id' => $request->program, 'intake_id' => $request->intake])
            ->first();

            DB::beginTransaction();

            if ($check_program_intake) {
                $program_intake = $check_program_intake->id;
            } else {
                $program_intake = DB::table('program_fees')->insertGetId([
                    'program_id' => $request->program,
                    'intake_id' => $request->intake
                ]);
            }

            

            // $country_id = DB::table('students')->where('id', $request->student)->value('country_id');

            // $local_country_id = DB::table('local_country')->value('country_id');
            
            
            foreach ($request->levels as $data) {
                $installment = 0;
                $check = DB::table('level_semester_fees')
                ->where([
                    'program_fee_id' => $program_intake, 
                    'year_id' => $data['year_of_study'],
                    'semester_id' => $data['semester'],
                ])
                ->first();
                if ($check) {
                    return response()->json([
                        'status' => 403,
                        'message' => 'You have already added for '.$this->getLevelNames($data['year_of_study'], $data['semester'])['year'].' '.$this->getLevelNames($data['year_of_study'], $data['semester'])['semester']
                    ], 403);
                } else {
                    $level_id = DB::table('level_semester_fees')->insertGetId([
                        'author' => $request->user()->id,
                        'program_fee_id' => $program_intake,
                        'year_id' => $data['year_of_study'],
                        'semester_id' => $data['semester'],
                        'local_student_tuition' => $data['local_student_tuition_fee'],
                        'foreign_student_tuition' => $data['foreign_student_tuition_fee'],
                        'exam_fee' => $data['exam_fee'],
                        // 'local_reporting_payment' => $data['local_reporting_payment'],
                        // 'foreign_reporting_payment' => $data['foreign_reporting_payment'],
                        'other_requirements' => $data['other_requirements'],
                    ]);

                    $total_local_installment = 0;
                    $total_foreign_installment = 0;
                    $count = 0;
                    $local_accumulator = 0;
                    $foreign_accumulator = 0;
                    // foreach ($data['installments'] as $value) {
                    //     $count++;
                    //     $total_local_installment += $value['amount_local'];
                    //     $total_foreign_installment += $value['amount_foreign'];
                    //     $installment +=1;
                    //     // $local_installment_accumulator = $value['amount_local'] += $value['amount_local'];
                    //     // $foreign_installment_accumulator = $value['amount_local'] += $value['amount_foreign'];
                    //     $local_accumulator += $value['amount_local'];
                    //     $foreign_accumulator += $value['amount_foreign'];

                    //     DB::table('payment_installments')->insert([
                    //         'author' => $request->user()->id,
                    //         'program_fee_id' => $program_intake,
                    //         'level_id' => $level_id,
                    //         'installment_number' => $installment,
                    //         'amount_local' => $value['amount_local'],
                    //         'amount_foreign' => $value['amount_foreign'],
                    //         'expected_paid_local_amount' => $data['local_reporting_payment'] + $local_accumulator,
                    //         'expected_paid_foreign_amount' => $data['foreign_reporting_payment'] + $foreign_accumulator,
                    //         'date_of_payment' => $value['date_to_be_paid']
                    //     ]);
                    // }

                    // return $total_local_installment;

                    // if (($total_local_installment + $data['local_reporting_payment']) != ($data['local_student_tuition_fee'] + $data['exam_fee'] + $data['other_requirements'])) {
                    //     DB::rollBack();
                    //     return response()->json([
                    //         'status' => 403,
                    //         'message' => 'The total installments amount for local student is not equal to the local student total fee on '.$this->getLevelNames($data['year_of_study'], $data['semester'])['year'].' '.$this->getLevelNames($data['year_of_study'], $data['semester'])['semester']
                    //     ], 403);
                    // }
                    // if (($total_foreign_installment + $data['foreign_reporting_payment']) != ($data['foreign_student_tuition_fee'] + $data['exam_fee'] + $data['other_requirements'])) {
                    //     DB::rollBack();
                    //     return response()->json([
                    //         'status' => 403,
                    //         'message' => 'The total installments amount for foreign student is not equal to the foreign student total fee on '.$this->getLevelNames($data['year_of_study'], $data['semester'])['year'].' '.$this->getLevelNames($data['year_of_study'], $data['semester'])['semester']
                    //     ], 403);
                    // }
                }
            }

            DB::commit();

            return response()->json([
                'status'=> 201,
                'message'=> 'Fees added successfully!'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function feeDetails(Request $request) {
        try {
            $fees = DB::table('level_semester_fees')
            ->join('training_years', 'level_semester_fees.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'level_semester_fees.semester_id', '=', 'training_semesters.id')
            ->join('admins', 'level_semester_fees.author', '=', 'admins.id')
            ->select('level_semester_fees.*', 'training_years.year_label', 'training_semesters.semester_label', 'admins.name as author_name')            
            ->where('program_fee_id', $request->program_fee_id)
            ->get();

            return response()->json([
                'status'=> 200,
                'data'=> $fees
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'local_student_tuition' => 'required|numeric|min:1',
                'foreign_student_tuition' => 'required|numeric|min:1',
                'exam_fee' => 'required|numeric|min:1',
                'other_requirements' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('level_semester_fees')->where('id', $request->id)->update([
                'local_student_tuition' => $request->local_student_tuition,
                'foreign_student_tuition' => $request->foreign_student_tuition,
                'exam_fee' => $request->exam_fee,
                'other_requirements' => $request->other_requirements,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'The fees have been updated successfully'
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
