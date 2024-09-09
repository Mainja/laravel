<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class paymentsController extends Controller
{
    public function recordPayment(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();

        try {
            $validator = Validator::make($request->all(), [
                'student_category' => 'required',
                'registration_opening' => 'required',
                'program_of_study' => 'required',
                'student' => 'required',
                'invoice_number' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'amount_paid' => 'required|numeric|min:1',
                'date_paid' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            if (DB::table('student_payments')->where([
                'opening_id' => $request->registration_opening,
                'student_id' => $request->student,
            ])->count() > 0) {
                return $this->responseMessage(403, "You have already recorded a semester payment for this student in this registration opening!");
            }

            if ($request->date_paid > $today) {
                return response()->json([
                    'status'=> 403,
                    'message' => 'You cannot use future dates'
                ], 403);
            }

            // get the numbers on year of study and semester
            $year_number = DB::table('training_years')->where('id', $request->year_of_study)->value('year_number');
            $semester_number = DB::table('training_semesters')->where('id', $request->semester)->value('semester_number');
            // check if student has completed a previous registration

            if ($request->student_category == "new_students" || $request->student_category == "transin_students") {
                # check if they have some records
                $check_pay = DB::table('student_payments')
                ->where([
                    'student_id' => $request->student,
                ])->count();

                $check_exists = DB::table('student_level')
                ->where([
                    'student_id' => $request->student,
                ])->count();

                if ($check_exists > 0) {
                    return $this->responseMessage(403, "Students marked as new or transin should not have an existing registration record!");
                }

                if ($check_pay > 0) {
                    return $this->responseMessage(403, "Students marked as new or transin should not have an existing record of payments!");
                }
            }
            if ($request->student_category == "returning_students" && $year_number == 1 && $semester_number == 1) {
                return $this->responseMessage(403, "Returning students cannot be first year first semester!");
            }

            // get the amount payable
            $intake_id = DB::table('students')->where('id', $request->student)->value('intake_id');
            $program_id = DB::table('students')->where('id', $request->student)->value('program_id');

            // check if student is local or foreign
            $country_id = DB::table('students')->where('id', $request->student)->value('country_id');

            $local_country_id = DB::table('local_country')->value('country_id');
            // get fee id
            $fee_id = DB::table('program_fees')
            ->where(['program_id' => $program_id, 'intake_id' => $intake_id])
            ->value('id');
            // get actual fee
            $get_amounts = DB::table('level_semester_fees')
            ->where([
                'program_fee_id' => $fee_id,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ])
            ->first();

            if (!$get_amounts) {
                return $this->responseMessage(404, 'You have not added the fees for '.$this->getIntakeName($intake_id).' '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester));
            }

            if ($country_id == $local_country_id) {
                $tuition_fee = $get_amounts->local_student_tuition;
            } else {
                $tuition_fee = $get_amounts->foreign_student_tuition;
            }

            $params = [
                'student_id' => $request->student,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ];

            $check_exists = DB::table('student_payments')
            ->where($params)
            ->first();

            $check_reg = DB::table('student_level')
            ->where($params)
            ->first();
            
            // if ($check_reg) {
            //     return response()->json([
            //         'status'=> 403,
            //         'message'=> 'Student is already registered for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester)
            //     ], 403);
            // }

            if ($check_exists) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You have already made payment for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester)
                ], 403);
            }

            $amount_payable = $tuition_fee + $get_amounts->exam_fee + $get_amounts->other_requirements;

            if ($request->amount_paid > $amount_payable) {
                return response()->json([
                    'status'=> 403,
                    'message' => 'The amount paid should not be greater than the total fee'
                ], 403);
            } 
            elseif ($request->student_category != "transin_students") {
                if ($year_number == 1 && $semester_number == 1) {
                    # first year first semester
                    return $this->recordPay($amount_payable, $request);

                } else if ($year_number == 1 && $semester_number == 2) {
                    // return $this->semesterYearIds($year_number, $semester_number);
                    # check if first semester of first year was paid for
                    // get year id of year number 1 and id of semester number 1
                    // check registration of previous
                    $reg_check = DB::table('student_level')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(1, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(1, 1)['semester_id'],
                    ])->first();

                    if (!$reg_check) {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not completed/started registration for first year first semester");
                    }

                    $check_payment = DB::table('student_payments')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(1, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(1, 1)['semester_id'],
                    ])->first();
                    if (!$check_payment && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not paid for first year first semester");
                    } elseif ($check_payment && $check_payment->balance > 0 && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student has a balance for first year first semester");
                    } else {
                        return $this->recordPay($amount_payable, $request);

                        
                    }
                } elseif ($year_number == 2 && $semester_number == 1) {
                    # check if student paid for first year second semester
                    $reg_check = DB::table('student_level')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(1, 2)['year_id'],
                        'semester_id'=> $this->semesterYearIds(1, 2)['semester_id'],
                    ])->first();

                    if (!$reg_check) {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not completed/started registration for first year second semester");
                    }

                    $check_payment = DB::table('student_payments')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(1, 2)['year_id'],
                        'semester_id'=> $this->semesterYearIds(1, 2)['semester_id'],
                    ])->first();
                    if (!$check_payment && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not paid for first year second semester");
                    } elseif ($check_payment && $check_payment->balance > 0 && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student has a balance for first year second semester");
                    } else {
                        return $this->recordPay($amount_payable, $request);
                    }
                } elseif ($year_number == 2 && $semester_number == 2) {
                    $reg_check = DB::table('student_level')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(2, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(2, 1)['semester_id'],
                    ])->first();

                    if (!$reg_check) {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not completed/started registration for second year first semester");
                    }
                    # check if student paid for second year first semester
                    $check_payment = DB::table('student_payments')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(2, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(2, 1)['semester_id'],
                    ])->first();
                    if (!$check_payment && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not paid for second year first semester");
                    } elseif ($check_payment && $check_payment->balance > 0 && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student has a balance for second year first semester");
                    } else {
                        return $this->recordPay($amount_payable, $request);

                        
                    }
                } elseif ($year_number == 3 && $semester_number == 1) {
                    $reg_check = DB::table('student_level')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(2, 2)['year_id'],
                        'semester_id'=> $this->semesterYearIds(2, 2)['semester_id'],
                    ])->first();

                    if (!$reg_check) {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not completed/started registration for second year second semester");
                    }

                    # check if student paid for second year second semester
                    $check_payment = DB::table('student_payments')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(2, 2)['year_id'],
                        'semester_id'=> $this->semesterYearIds(2, 2)['semester_id'],
                    ])->first();
                    if (!$check_payment && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not paid for second year second semester");
                    } elseif ($check_payment && $check_payment->balance > 0 && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student has a balance for second year second semester");
                    } else {
                        return $this->recordPay($amount_payable, $request);

                    }
                } elseif ($year_number == 3 && $semester_number == 2) {
                    $reg_check = DB::table('student_level')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(3, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(3, 1)['semester_id'],
                    ])->first();

                    if (!$reg_check) {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not completed/started registration for third year first semester");
                    }
                    # check if student paid for third year first semester
                    $check_payment = DB::table('student_payments')->where([
                        'student_id' => $request->student,
                        'year_id' => $this->semesterYearIds(3, 1)['year_id'],
                        'semester_id'=> $this->semesterYearIds(3, 1)['semester_id'],
                    ])->first();
                    if (!$check_payment && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(404, "Student has not paid for third year first semester");
                    } elseif ($check_payment && $check_payment->balance > 0 && $request->skip_payment == "no") {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student has a balance for third year first semester");
                    } else {
                        return $this->recordPay($amount_payable, $request);
                    }
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

    public function getInstallments(Request $request) {
        // get the program id and the intake id
        $intake_id = DB::table('students')->where('id', $request->student)->value('intake_id');
        $program_id = DB::table('students')->where('id', $request->student)->value('program_id');
        // now let us get the program fee id
        $program_fee_id = DB::table('program_fees')
        ->where([
            'program_id' => $program_id,
            'intake_id' => $intake_id
        ])->value('id');

        $level = DB::table('level_semester_fees')
        ->where([
            'program_fee_id' => $program_fee_id,
            'year_id' => $request->year_of_study,
            'semester_id' => $request->semester,
        ])->first();

        $country_id = DB::table('students')->where('id', $request->student)->value('country_id');

        $local_country_id = DB::table('local_country')->value('country_id');

        if ($country_id == $local_country_id) {
            $report_fee = $level->local_reporting_payment;
        } else {
            $report_fee = $level->foreign_reporting_payment;
        }
        // get what has been paid
        $paid_total = DB::table('student_payments')
        ->where([
            'student_id' => $request->student,
            'year_id' => $request->year_of_study,
            'semester_id'=> $request->semester,
        ])->value('amount_paid');

        $max_installment = DB::table('payment_installments')->where([
            'program_fee_id' => $program_fee_id,
            'level_id' => $level->id,
        ])->max('installment_number');

        $installment_shape = DB::table('payment_installments')->where([
            'program_fee_id' => $program_fee_id,
            'level_id' => $level->id,
            // ['installment_number', '!=', $max_installment,]
        ])->orderBy('installment_number', 'asc')->get();
    }

    private function recordPay($amount_payable, $request) {
        try {
            $pay_date = Carbon::createFromFormat('Y-m-d', $request->date_paid);

            DB::beginTransaction();

            $pay_id = DB::table('student_payments')->insertGetId([
                'opening_id' => $request->registration_opening,
                'student_id' => $request->student,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'amount_payable' => $amount_payable,
                'program_id' => $request->program_of_study,
                'amount_paid' => $request->amount_paid,
                'balance' => $amount_payable - $request->amount_paid,
                'percentage_paid' => ($request->amount_paid / $amount_payable) *100,
                'date_paid' => $request->date_paid,
                'student_category' => $request->student_category,
            ]);

            DB::table('payment_records')->insert([
                'author' => $request->user()->id,
                'invoice_number' => $request->invoice_number,
                'payment_id' => $pay_id,
                'date_paid' => $request->date_paid,
                'amount' => $request->amount_paid
            ]);

            // get the program id and the intake id
            $intake_id = DB::table('students')->where('id', $request->student)->value('intake_id');
            $program_id = DB::table('students')->where('id', $request->student)->value('program_id');
            // now let us get the program fee id
            $program_fee_id = DB::table('program_fees')
            ->where([
                'program_id' => $program_id,
                'intake_id' => $intake_id
            ])->value('id');

            $level = DB::table('level_semester_fees')
            ->where([
                'program_fee_id' => $program_fee_id,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ])->first();

            $country_id = DB::table('students')->where('id', $request->student)->value('country_id');

            $local_country_id = DB::table('local_country')->value('country_id');

            if ($country_id == $local_country_id) {
                $report_fee = $level->local_reporting_payment;
            } else {
                $report_fee = $level->foreign_reporting_payment;
            }
            // get what has been paid
            $paid_total = DB::table('student_payments')
            ->where([
                'student_id' => $request->student,
                'year_id' => $request->year_of_study,
                'semester_id'=> $request->semester,
            ])->value('amount_paid');

            $max_installment = DB::table('payment_installments')->where([
                'program_fee_id' => $program_fee_id,
                'level_id' => $level->id,
            ])->max('installment_number');

            $installment_shape = DB::table('payment_installments')->where([
                'program_fee_id' => $program_fee_id,
                'level_id' => $level->id,
                // ['installment_number', '!=', $max_installment,]
            ])->orderBy('installment_number', 'asc')->get();

            $registration_steps = DB::table('registration_steps')->where([
                'program_id' => $request->program_of_study,
                'student_category' => $request->student_category,
            ])->orderBy('step', 'asc')->get();

            $registration_steps_count = DB::table('registration_steps')->where([
                'program_id' => $request->program_of_study,
                'student_category' => $request->student_category,
            ])->count();
            
            $check_installment_exists = DB::table('student_installment_payments')
            ->where([
                // 'student_id' => $request->student,
                // 'year_id' => $request->year_of_study,
                // 'semester_id' => $request->semester,
                'registration_id' => $pay_id
            ])->first();

            
            if ($country_id == $local_country_id) {
                $local = true;
            } else {
                $local = false;
            }
            if (!$check_installment_exists) {
                foreach ($installment_shape as $value) {
                    // register the installments
                    DB::table('student_installment_payments')->insert([
                        // 'student_id' => $request->student,
                        // 'year_id' => $request->year_of_study,
                        // 'semester_id' => $request->semester,
                        'registration_id' => $pay_id,
                        'installment_number' => $value->installment_number,
                        'installment_amount' => $country_id == $local_country_id ? $value->amount_local : $value->amount_foreign,
                        'amount_paid' => 0,
                        'balance' => $country_id == $local_country_id ? $value->amount_local : $value->amount_foreign,
                        'date_expected' => ($value->installment_number == $max_installment ? $value->date_of_payment : 
                        $request->year_of_study == 1 && $request->semester == 1) ? $pay_date->addMonths($value->installment_number)->endOfMonth()->format('Y-m-d') : $value->date_of_payment,
                        'amount_expected' => $country_id == $local_country_id ? $value->expected_paid_local_amount : $value->expected_paid_foreign_amount,
                        'carry_over' => 0,
                    ]);
                }
                // update the installment payments
                if ($paid_total > $report_fee) {
                    // try wiith forloop
                    $payment = $paid_total - $report_fee;
                    for ($i=1; $i <= $max_installment; $i++) { 
                        $params = [
                            'registration_id' => $pay_id,
                            'installment_number' => $i,
                        ];
                        if ($i == 1) {
                            $installment = $this->getInstallment($i, $pay_id);
                            DB::table('student_installment_payments')->where($params)->update([
                                // 'student_id' => $request->student,
                                // 'year_id' => $request->year_of_study,
                                // 'semester_id' => $request->semester,
                                'installment_number' => $i,
                                'amount_paid' => $payment > $installment->installment_amount ? $installment->installment_amount : $payment,
                                'carry_over' => $payment > $installment->installment_amount ? $payment - $installment->installment_amount : 0,
                                'balance' => $installment->installment_amount - ($payment > $installment->installment_amount ? $installment->installment_amount : $payment)
                            ]);
                        } elseif ($i > 1 && $i <= $max_installment) {
                            $installment = $this->getInstallment($i, $pay_id);
                            $previous_installment = $this->getInstallment($i-1, $pay_id);
                            DB::table('student_installment_payments')->where($params)->update([
                                // 'student_id' => $request->student,
                                // 'year_id' => $request->year_of_study,
                                // 'semester_id' => $request->semester,
                                'installment_number' => $i,
                                'amount_paid' => $previous_installment->carry_over > $installment->installment_amount ? $installment->installment_amount  : $previous_installment->carry_over,
                                'carry_over' => $previous_installment->carry_over > $installment->installment_amount ? $previous_installment->carry_over - $installment->installment_amount : 0,
                                'balance' => $installment->installment_amount - ($previous_installment->carry_over > $installment->installment_amount ? $installment->installment_amount : $previous_installment->carry_over)
                            ]);
                        }
                    }
                    // end of try with forloop
                }
            }
            if ($registration_steps_count > 0) {
                foreach ($registration_steps as $key => $value) {
                    DB::table('student_registration_progress')->insert([
                        // 'student_id' => $request->student,
                        // 'semester_id' => $request->semester,
                        // 'year_id' => $request->year_of_study,
                        // 'program_id' => $value->program_id,
                        'registration_id' => $pay_id,
                        'step_id' => $value->id,
                        // 'office_id' => $value->office_id,
                        // 'step' => $value->step,
                        'status' => $value->step == 1 ? 'active' : 'pending'
                    ]);
                }
            } else {
                DB::rollBack();

                return $this->responseMessage(403, "The registration steps for the chosen student category have not been added");
            }
            $opening = DB::table('registration_openings')
            ->where('id', $request->registration_opening)
            ->first();

            DB::table('registered_students')->insert([
                // 'program_id' => $request->program_of_study,
                // 'student_id' => $request->student, 
                // 'year_id' => $request->year_of_study,
                // 'semester_id' => $request->semester,
                'registration_id' => $pay_id,
                'status' => 'pending',
                'registration_type' => $request->date_paid > $opening->end_date && $request->student_category == "returning_students" ? 'late' : 'pending'
            ]);

            if ($request->date_paid > $opening->end_date && $request->student_category == "returning_students") {
                if (DB::table('student_penalties')->where('registration_id', $pay_id)->count() == 0) {
                    DB::table('student_penalties')->insert([
                        // 'program_id' => $request->program_of_study,
                        // 'student_id' => $request->student, 
                        // 'year_id' => $request->year_of_study,
                        // 'semester_id' => $request->semester,
                        'registration_id' => $pay_id,
                        'paid' => 0,
                        'balance' => $opening->penalty_fee,
                        'penalty_fee' => $opening->penalty_fee,
                        'validity' => 'active',
                    ]);
                }
            }
            
            DB::commit();
            return $this->responseMessage(201,"Payment recorded successfully");
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function paymentHistory() {
        try {
            $payments = DB::table('student_payments')
            ->join('registration_openings', 'student_payments.opening_id', '=', 'registration_openings.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->select('student_payments.*', 'training_years.year_label', 'training_semesters.semester_label', 'students.name', 'students.computer_number', 'intakes.label', 'registration_openings.description')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data'=> $payments,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentPayments(Request $request) {
        try {
            $payments = DB::table('student_payments')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->select('student_payments.*', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('student_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $payments,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function paymentRecords(Request $request) {
        try {
            $payments = DB::table('payment_records')
            ->join('student_payments', 'payment_records.payment_id', '=', 'student_payments.id')
            ->join('admins', 'payment_records.author', '=', 'admins.id')
            ->select('payment_records.*', 'admins.name as author_name', 'student_payments.student_id')
            ->where('payment_id', $request->payment_id)
            ->where('student_payments.student_id', $request->student_id)
            ->get();

            $summary_data = DB::table('student_payments')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->select('student_payments.amount_payable','student_payments.amount_paid', 'student_payments.balance', 'training_years.year_label', 'training_semesters.semester_label', 'students.name', 'students.computer_number', 'intakes.label')
            ->where('student_payments.id', $request->payment_id)
            ->where('student_payments.student_id', $request->student_id)
            ->first();

            return response()->json([
                'status' => 200,
                'data'=> $payments,
                'summary' => $summary_data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function updateBalance(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                'balance' => 'required|numeric|min:1',
                'date_paid' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->date_paid > $today) {
                return response()->json([
                    'status'=> 403,
                    'message' => 'You cannot use future dates'
                ], 403);
            }

            $get_record = DB::table('student_payments')->where('id', $request->id)->first();

            $percent = (($get_record->amount_paid + $request->balance) / $get_record->amount_payable) * 100;

            if ($request->balance > $get_record->balance) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'The amount you typed is greater than the balance remaining'
                ], 403);
            }

            DB::beginTransaction();

            DB::table('student_payments')->where('id', $request->id)->decrement('balance', $request->balance);

            DB::table('student_payments')->where('id', $request->id)->increment('amount_paid', $request->balance);

            DB::table('student_payments')->where('id', $request->id)->update(
                [
                    'percentage_paid' => $percent
                ]
            );

            DB::table('payment_records')->insert([
                'invoice_number' => $request->invoice_number,
                'author' => $request->user()->id,
                'payment_id' => $get_record->id,
                'date_paid' => $request->date_paid,
                'amount' => $request->balance
            ]);

            // get the program id and the intake id
        $intake_id = DB::table('students')->where('id', $get_record->student_id)->value('intake_id');
        $program_id = DB::table('students')->where('id', $get_record->student_id)->value('program_id');
        // now let us get the program fee id
        $program_fee_id = DB::table('program_fees')
        ->where([
            'program_id' => $program_id,
            'intake_id' => $intake_id
        ])->value('id');

        $level = DB::table('level_semester_fees')
        ->where([
            'program_fee_id' => $program_fee_id,
            'year_id' => $get_record->year_id,
            'semester_id' => $get_record->semester_id,
        ])->first();

        $country_id = DB::table('students')->where('id', $get_record->student_id)->value('country_id');

        $local_country_id = DB::table('local_country')->value('country_id');

        if ($country_id == $local_country_id) {
            $report_fee = $level->local_reporting_payment;
        } else {
            $report_fee = $level->foreign_reporting_payment;
        }

        // // get what has been paid
        $paid_total = DB::table('student_payments')
        ->where([
            'student_id' => $get_record->student_id,
            'year_id' => $get_record->year_id,
            'semester_id'=> $get_record->semester_id,
        ])->value('amount_paid');

        $max_installment = DB::table('student_installment_payments')->where([
            'registration_id' => $get_record->id,
        ])->max('installment_number');

        // if ($paid_total >= $report_fee) {
        //     # whole new payment goes to installments
        //     $payment = $request->balance;
        // } elseif ($paid_total < $report_fee) {
        //     $new_total = $request->balance + $paid_total;
        //     if ($new_total > $report_fee) {
        //         $payment = $new_total - $report_fee;
        //     }
        // }
        
        DB::table('student_installment_payments')
        ->where('registration_id', $get_record->id)
        ->update([
            'carry_over' => 0
        ]);

        // $amount = 0;
        // $carry = 0;
        // for ($i=1; $i <= $max_installment; $i++) {
        //     $current = $this->getInstallment($i, $get_record->id);

        //     if ($i == 1) {
        //         if ($current->balance > 0) {
        //             if ($payment > $current->balance) {
        //                 $amount = $current->balance;
        //                 $carry = $payment - $current->balance;
        //             } elseif ($payment <= $current->balance) {
        //                 $amount = $payment;
        //                 $carry = 0;
        //             }
        //             DB::table('student_installment_payments')->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])->increment('amount_paid', $amount);
    
        //             DB::table('student_installment_payments')->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])->decrement('balance', $amount);

        //             DB::table('student_installment_payments')
        //             ->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])
        //             ->update([
        //                 'carry_over' => $carry
        //             ]);
        //         }
        //     } elseif ($i > 1 && $i <= $max_installment) {
        //         $previous = $this->getInstallment($i-1, $get_record->id);
        //         $new_payment = $payment + $previous->carry_over;

        //         return $new_payment;

        //         if ($current->balance > 0 && $previous->carry_over > 0) {
        //             if ($new_payment > $current->balance) {
        //                 $amount = $current->balance;
        //                 $carry = $new_payment - $current->balance;
        //             } elseif ($new_payment <= $current->balance) {
        //                 $amount = $new_payment;
        //                 $carry = 0;
        //             }
        //             DB::table('student_installment_payments')->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])->increment('amount_paid', $amount);
    
        //             DB::table('student_installment_payments')->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])->decrement('balance', $amount);

        //             DB::table('student_installment_payments')
        //             ->where([
        //                 'registration_id' => $get_record->id,
        //                 'installment_number' => $i
        //             ])
        //             ->update([
        //                 'carry_over' => $carry
        //             ]);
        //         }
        //     }            
        // }

        

        if ($paid_total > $report_fee) {
            $payment = $paid_total - $report_fee;
            for ($i=1; $i <= $max_installment; $i++) { 
                $params = [
                    'registration_id' => $get_record->id,
                    'installment_number' => $i,
                ];
                if ($i == 1) {
                    $installment = $this->getInstallment($i, $get_record->id);
                    DB::table('student_installment_payments')->where($params)->update([
                        // 'student_id' => $request->student,
                        // 'year_id' => $request->year_of_study,
                        // 'semester_id' => $request->semester,
                        'installment_number' => $i,
                        'amount_paid' => $payment > $installment->installment_amount ? $installment->installment_amount : $payment,
                        'carry_over' => $payment > $installment->installment_amount ? $payment - $installment->installment_amount : 0,
                        'balance' => $installment->installment_amount - ($payment > $installment->installment_amount ? $installment->installment_amount : $payment)
                    ]);
                } elseif ($i > 1 && $i <= $max_installment) {
                    $installment = $this->getInstallment($i, $get_record->id);
                    $previous_installment = $this->getInstallment($i-1, $get_record->id);
                    DB::table('student_installment_payments')->where($params)->update([
                        // 'student_id' => $request->student,
                        // 'year_id' => $request->year_of_study,
                        // 'semester_id' => $request->semester,
                        'installment_number' => $i,
                        'amount_paid' => $previous_installment->carry_over > $installment->installment_amount ? $installment->installment_amount  : $previous_installment->carry_over,
                        'carry_over' => $previous_installment->carry_over > $installment->installment_amount ? $previous_installment->carry_over - $installment->installment_amount : 0,
                        'balance' => $installment->installment_amount - ($previous_installment->carry_over > $installment->installment_amount ? $installment->installment_amount : $previous_installment->carry_over)
                    ]);
                }
            }

            DB::table('student_installment_payments')
            ->where('registration_id', $get_record->id)
            ->update([
                'carry_over' => 0
            ]);
        }

        

            DB::commit();

            return response()->json([
                'status'=> 201,
                'message'=> 'Balance updated successfully!'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function updateAmountPaid(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'date_paid' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment_record = DB::table('payment_records')->where('id', $request->id)->first();

            $overall_payment = DB::table('student_payments')->where('id', $payment_record->payment_id)->first();

            $difference = 0;

            if ($request->amount > $payment_record->amount) {
                # an increase in payment, a decrease in balance
                $difference = $request->amount - $payment_record->amount;
                if (($overall_payment->balance - $difference) < 0) {
                    return response()->json([
                        'status'=> 403,
                        'message'=> 'The new amount you entered will cause the overall balance to be less than zero. Check your amount again!'
                    ], 403);
                } else {
                    DB::beginTransaction();

                    DB::table('payment_records')->where('id', $request->id)->update([
                        'amount' => $request->amount,
                        'date_paid' => $request->date_paid,
                    ]);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->decrement('balance', $difference);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->increment('amount_paid', $difference);
                    
                    $updated_payment = DB::table('student_payments')->where('id', $payment_record->payment_id)->first();

                    $percent = round(($updated_payment->amount_paid / $updated_payment->amount_payable) *100, 2);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->update([
                        'percentage_paid' => $percent
                    ]);

                    DB::commit();

                    return response()->json([
                        'status'=> 200,
                        'message'=> 'Amount has been updated successfully!'
                    ]);
                }
            } elseif ($request->amount < $payment_record->amount) {
                # an increase in balance, a decrease in payment
                $difference = $payment_record->amount - $request->amount;

                DB::beginTransaction();

                    DB::table('payment_records')->where('id', $request->id)->update([
                        'amount' => $request->amount,
                        'date_paid' => $request->date_paid,
                    ]);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->decrement('amount_paid', $difference);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->increment('balance', $difference);

                    $updated_payment = DB::table('student_payments')->where('id', $payment_record->payment_id)->first();

                    $percent = round(($updated_payment->amount_paid / $updated_payment->amount_payable) *100, 2);

                    DB::table('student_payments')->where('id', $payment_record->payment_id)->update([
                        'percentage_paid' => $percent
                    ]);

                    DB::commit();

                    return response()->json([
                        'status'=> 200,
                        'message'=> 'Amount has been updated successfully!'
                    ]);
            } elseif ($request->amount == $payment_record->amount) {
                DB::table('payment_records')->where('id', $request->id)->update([
                    'amount' => $request->amount,
                    'date_paid' => $request->date_paid,
                ]);

                return response()->json([
                    'status'=> 200,
                    'message'=> 'Amount has been updated successfully!'
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    // public function getTodayInstallments(Request $request) {
    //     date_default_timezone_set("Africa/Lusaka");
    //     try {
    //         $today = Carbon::today()->toDateString();
    //         $installments = DB::table('student_installment_payments')
    //         ->where('')
    //     } catch (\Throwable $th) {
    //         //throw $th;
    //     }
    // }

    private function getYearName($year) {
        return DB::table('training_years')->where('id', $year)->value('year_label');
    }

    private function getSemesterName($semester) {
        return DB::table('training_semesters')->where('id', $semester)->value('semester_label');
    }

    private function getIntakeName($intake_id) {
        return DB::table('intakes')->where('id', $intake_id)->value('label');
    }

    private function responseMessage($errorCode, $message) {
        return response()->json([
            'status' => $errorCode,
            'message'=> $message
        ], $errorCode);
    }

    private function getInstallment($installment, $pay_id) {
        $installment = DB::table('student_installment_payments')->where([
            'registration_id' => $pay_id,
            'installment_number' => $installment,
        ])->first();

        return $installment;
    }

    private function semesterYearIds($year_number, $semester_number) {
        $year_id = DB::table('training_years')->where('year_number', $year_number)->value('id');
        $semester_id = DB::table('training_semesters')->where('semester_number', $semester_number)->value('id');

        return [
            'year_id' => $year_id,
            'semester_id' => $semester_id
        ];
    }
}
