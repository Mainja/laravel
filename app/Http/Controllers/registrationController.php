<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class registrationController extends Controller
{
    public function officeRequests(Request $request) {
        try {
            $office_requests = DB::table("student_registration_progress as process")
            ->join('registration_steps as rs', 'process.step_id', '=', 'rs.id')
            ->join('student_payments as sp', 'process.registration_id', '=', 'sp.id')
            ->join('programs', 'sp.program_id', '=', 'programs.id')
            ->join('offices', 'rs.office_id', '=', 'offices.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            // ->join('registration_steps as rs', function ($join) {
            //     $join->on('rs.program_id', '=', 'sp.program_id')
            //          ->on('rs.office_id', '=', 'process.office_id');
            // })
            ->select('process.*', 'training_years.year_label', 'training_semesters.semester_label', 'students.name', 'students.computer_number', 'offices.office_name', 'programs.program_name', 'rs.id as step_id', 'rs.office_id', 'sp.student_id', 'sp.year_id', 'sp.semester_id', 'sp.program_id')
            ->where('rs.office_id', $request->user()->office_id)
            ->where('process.status', 'active')
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
    public function startRegistration(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'year_of_study' => 'required',                
                'semester' => 'required',     
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $max_steps = DB::table('registration_steps')
            ->where([
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'program_id' => $request->user()->program_id,
            ])
            ->max('step');

            if (!$max_steps) {
                return $this->responseMessage(404, 'The registration steps for your selected year and semester have not been defined. Please see I.T for guidance');
            }

            $params = [
                'student_id' => $request->user()->id,
                'year_number' => $request->year_of_study,
                'semester_number' => $request->semester,
            ];

            $check_exists = DB::table('student_payments')
            ->where($params)
            ->first();
            
            // if ($check_exists) {
            //     return response()->json([
            //         'status'=> 403,
            //         'message'=> 'You have already made payment for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester)
            //     ], 403);
            // }

            if ($request->year_of_study == 1 && $request->semester == 1) {
                # first year first semester
                $this->recordPay($request);

                return $this->responseMessage(201, "Submitted successfully");

            } elseif ($request->year_of_study == 1 && $request->semester == 2) {
                # check if first semester of first year was paid for
                $check_payment = DB::table('student_payments')->where([
                    'student_id' => $request->user()->id,
                    'year_number' => 1,
                    'semester_number'=> 1,
                ])->first();
                if (!$check_payment) {
                    DB::rollBack();

                    return $this->responseMessage(404, "You have not paid for first year first semester");
                } elseif ($check_payment && $check_payment->balance > 0) {
                    DB::rollBack();

                    return $this->responseMessage(403, "You have a balance for first year first semester");
                } else {
                    $this->recordPay($request);

                    return $this->responseMessage(201,"Submitted successfully");
                }
            } elseif ($request->year_of_study == 2 && $request->semester == 1) {
                # check if student paid for first year second semester
                $check_payment = DB::table('student_payments')->where([
                    'student_id' => $request->user()->id,
                    'year_number' => 1,
                    'semester_number'=> 2,
                ])->first();
                if (!$check_payment) {
                    DB::rollBack();

                    return $this->responseMessage(404, "You have not paid for first year second semester");
                } elseif ($check_payment && $check_payment->balance > 0) {
                    DB::rollBack();

                    return $this->responseMessage(403, "You have a balance for first year second semester");
                } else {
                    $this->recordPay($request);

                    return $this->responseMessage(201,"Submitted successfully");
                }
            } elseif ($request->year_of_study == 2 && $request->semester == 2) {
                # check if student paid for second year first semester
                $check_payment = DB::table('student_payments')->where([
                    'student_id' => $request->user()->id,
                    'year_number' => 2,
                    'semester_number'=> 1,
                ])->first();
                if (!$check_payment) {
                    DB::rollBack();

                    return $this->responseMessage(404, "You have not paid for second year first semester");
                } elseif ($check_payment && $check_payment->balance > 0) {
                    DB::rollBack();

                    return $this->responseMessage(403, "You have a balance for second year first semester");
                } else {
                    $this->recordPay($request);

                    return $this->responseMessage(201,"Submitted successfully");
                }
            } elseif ($request->year_of_study == 3 && $request->semester == 1) {
                # check if student paid for second year second semester
                $check_payment = DB::table('student_payments')->where([
                    'student_id' => $request->user()->id,
                    'year_number' => 2,
                    'semester_number'=> 2,
                ])->first();
                if (!$check_payment) {
                    DB::rollBack();

                    return $this->responseMessage(404, "You have not paid for second year second semester");
                } elseif ($check_payment && $check_payment->balance > 0) {
                    DB::rollBack();

                    return $this->responseMessage(403, "You have a balance for second year second semester");
                } else {
                    $this->recordPay($request);

                    return $this->responseMessage(201,"Submitted successfully");
                }
            } elseif ($request->year_of_study == 3 && $request->semester == 2) {
                # check if student paid for third year first semester
                $check_payment = DB::table('student_payments')->where([
                    'student_id' => $request->user()->id,
                    'year_number' => 3,
                    'semester_number'=> 1,
                ])->first();
                if (!$check_payment) {
                    DB::rollBack();

                    return $this->responseMessage(404, "You have not paid for third year first semester");
                } elseif ($check_payment && $check_payment->balance > 0) {
                    DB::rollBack();

                    return $this->responseMessage(403, "You have a balance for third year first semester");
                } else {
                    $this->recordPay($request);

                    return $this->responseMessage(201,"Submitted successfully");
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

    private function getYearName($year) {
        return DB::table('training_years')->where('id', $year)->value('year_label');
    }

    private function getSemesterName($semester) {
        return DB::table('training_semesters')->where('id', $semester)->value('semester_label');
    }

    private function getIntakeName($intake_id) {
        return DB::table('intakes')->where('id', $intake_id)->value('label');
    }

    private function getItemName($item_id) {
        return DB::table('inventory_items')->where('id', $item_id)->value('item_name');
    }

    private function responseMessage($errorCode, $message) {
        return response()->json([
            'status' => $errorCode,
            'message'=> $message
        ], $errorCode);
    }

    private function recordPay($request) {
        $first_step = DB::table('registration_steps')
        ->where([
            'year_id' => $request->year_of_study,
            'semester_id' => $request->semester,
            'program_id' => $request->user()->program_id,
            'step' => 1
        ])->first();

        $check_process = DB::table('student_registration_process')
        ->where([
            'student_id' => $request->user()->id,
            'year_id' => $request->year_of_study,
            'semester_id' => $request->semester,
            'step_id' => $first_step->id,
        ])
        ->first();

        if ($check_process) {
            return $this->responseMessage(403, 'You have already submitted registration request. Check for the progress of your registration!');
        } else {
            DB::table('student_registration_process')
            ->insert([
                'student_id' => $request->user()->id,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'step_id' => $first_step->id,
                'office_id' => $first_step->office_id
            ]);

            return $this->responseMessage(201,'You have successfully submitted your registration request. Keep checking for the progress of your registration!');
        }
    }

    public function registerExam(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'student' => 'required',
                'intake' => 'required',
                'program_of_study' => 'required',
                'exam_mode' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'courses' => 'required_if:exam_mode,resit',
            ], [
                'courses.required' => 'Please choose one or more courses'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            // check if student is already registered
            $params = [
                'program_id' => $request->program_of_study,
                'intake_id' => $request->intake,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'exam_mode' => $request->exam_mode,
            ];

            $check = DB::table('exam_registration')->where($params)->first();

            // cehck if student has a penalty for year and semester
            $penalty_check = DB::table('student_penalties')
            ->join('student_payments', 'student_penalties.registration_id', '=', 'student_payments.id')
            ->where([
                'student_payments.year_id' => $request->year_of_study,
                'student_payments.semester_id' => $request->semester,
                'student_payments.student_id' => $request->student,
                'student_penalties.validity' => 'valid'
            ])->first();

            if ($penalty_check) {
                return $this->responseMessage(403, 'Student has a penalty for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester));
            }

            if ($check) {
                $exam_id = $check->id;
            } else {
                $params = [
                    'student_id' => $request->student,
                    'year_id' => $request->year_of_study,
                    'semester_id' => $request->semester,
                ];
                // check if student is registered in specified semester and year
                $check_payment = DB::table("student_payments")->where($params)->first();

                $reg_check = DB::table("student_level")->where($params)->first();

                // check if all inventory requirements are met
                $inv_check = DB::table('student_submitted_inventory')
                ->join('student_payments', 'student_submitted_inventory.registration_id', '=', 'student_payments.id')
                ->select('student_submitted_inventory.*', 'student_payments.id as registration_id')
                ->where($params)
                ->where('student_submitted_inventory.balance', '>', 0)
                ->get();

                if ($inv_check) {
                    return $this->responseMessage(403, "There are pending submissions to be submitted by student. Please check student submitted inventory for ".$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester) ." for details!");
                }

                if (!$reg_check) {
                    return $this->responseMessage(403, 'Student is not registered for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester));
                } elseif ($check_payment->balance > 0) {
                    return $this->responseMessage(403, "Student has balance for ". $this->getYearName($request->year_of_study)." ".$this->getSemesterName($request->semester));
                } else {
                    DB::beginTransaction();

                    $exam_id = DB::table('exam_registration')->insertGetId($params);

                    if (DB::table('exam_student')->where([
                        'exam_id' => $exam_id,
                        'student_id' => $request->student
                    ])->count() > 0) {
                        DB::rollBack();

                        return $this->responseMessage(403, "Student is already registered for the ".$this->getYearName($request->year_of_study)." ".$this->getSemesterName($request->semester)." exams");
                    } else {
                        $exam_student_id = DB::table('exam_student')->insert([
                            'exam_id' => $exam_id,
                            'student_id' => $request->student
                        ]);
                    }

                    foreach($request->courses as $course) {
                        DB::table('exam_courses')->insert([
                            'exam_student_id' => $exam_student_id,
                            'course_id' => $course,
                        ]);
                    }

                    DB::commit();

                    return $this->responseMessage(201, "Exam registration completed successfully!");
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function examIndex() {
        try {
            $exams = DB::table('exam_registration as er')
            ->join('programs as pr', 'er.program_id', '=', 'pr.id')
            ->join('intakes', 'er.intake_id', '=', 'intakes.id')
            ->join('training_years', 'er.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'er.semester_id', '=', 'training_semesters.id')
            ->select('er.*', 'pr.program_name', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $exams
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function getExamCandidates(Request $request) {
        try {
            $summary = DB::table('exam_registration as er')
            ->join('programs as pr', 'er.program_id', '=', 'pr.id')
            ->join('intakes', 'er.intake_id', '=', 'intakes.id')
            ->join('training_years', 'er.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'er.semester_id', '=', 'training_semesters.id')
            ->select('er.*', 'pr.program_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('er.id', $request->exam_id)
            ->first();

            $candidates = DB::table('exam_student')
            ->join('students', 'exam_student.student_id', '=', 'students.id')
            ->select('exam_student.*', 'students.name', 'students.computer_number')
            ->where('exam_student.exam_id', $request->exam_id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $candidates,
                'summary' => $summary
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function examRegisteredCourses(Request $request) {
        try {
            $summary = DB::table('exam_student')
            ->join('exam_registration as er', 'exam_student.exam_id', '=', 'er.id')
            ->join('students as std', 'exam_student.student_id', '=', 'std.id')
            ->join('programs as pr', 'er.program_id', '=', 'pr.id')
            ->join('intakes', 'er.intake_id', '=', 'intakes.id')
            ->join('training_years', 'er.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'er.semester_id', '=', 'training_semesters.id')
            ->select('er.*', 'pr.program_name', 'std.name', 'std.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('exam_student.id', $request->exam_student_id)
            ->first();

            $courses = DB::table('exam_courses')
            ->join('courses', 'exam_courses.course_id', '=', 'courses.id')
            ->select('exam_courses.id', 'courses.course_name', 'courses.course_code')
            ->where('exam_student_id', $request->exam_student_id)
            ->get();

            return response()->json([
                'status'=> 200,
                'data'=> $courses,
                'summary' => $summary
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function lateRegistration() {
        try {
            $data = DB::table('student_payments')
            ->join('registration_openings', 'student_payments.opening_id', '=', 'registration_openings.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('training_years', 'student_payments.year_number', '=', 'training_years.year_number')
            ->join('training_semesters', 'student_payments.semester_number', '=', 'training_semesters.semester_number')
            ->select('student_payments.*', 'registration_openings.description', 'registration_openings.start_date', 'registration_openings.end_date', 'registration_openings.penalty_fee', 'training_years.year_label', 'training_semesters.semester_label', 'students.name', 'students.computer_number', 'intakes.label')
            ->where('student_payments.date_paid', '>', 'registration_openings.end_date')
            ->where('student_payments.year_number', '>=', 1)
            ->where('student_payments.semester_number', '>', 1)
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data'=> $data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function lateRegistrationCount() {
        try {
            $data = DB::table('student_payments')
            ->join('registration_openings', 'student_payments.opening_id', '=', 'registration_openings.id')
            ->select('student_payments.*', 'registration_openings.description', 'registration_openings.start_date', 'registration_openings.end_date', 'registration_openings.penalty_fee')
            ->where('student_payments.date_paid', '>', 'registration_openings.end_date')
            ->where('student_payments.year_number', '>=', 1)
            ->where('student_payments.semester_number', '>', 1)
            ->count();

            return response()->json([
                'status'=> 200,
                'data'=> $data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function confirmRegistrationStage(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                'date_submitted' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->date_submitted > $today) {
                return $this->responseMessage(403, "You cannot use future dates");
            }

            $student_registration = DB::table('student_payments')
            ->where([
                'year_id' => $request->year_id,
                'semester_id' => $request->semester_id,
                'student_id' => $request->student_id,
                'program_id' => $request->program_id
            ])->first();

            if ($request->date_submitted < $student_registration->date_paid) {
                return $this->responseMessage(403, "You cannot confirm on a date before the date when first payment was made");
            }

            DB::beginTransaction();

            return $this->handleStudentIssues($request);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function confirmRegistrationStageWithItems(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                'date_submitted' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->date_submitted > $today) {
                return $this->responseMessage(403, "You cannot use future dates");
            }

            // get student category
            $student_registration = DB::table('student_payments')
            ->where([
                'year_id' => $request->year_id,
                'semester_id' => $request->semester_id,
                'student_id' => $request->student_id,
                'program_id' => $request->program_id
            ])->first();

            if ($request->date_submitted < $student_registration->date_paid) {
                return $this->responseMessage(403, "You cannot confirm on a date before the date when first payment was made");
            }

            DB::beginTransaction();

            // $params = [
            //     // 'year_id' => $request->year_id,
            //     // 'semester_id' => $request->semester_id,
            //     'student_category' => $student_category,
            //     'office_id' => $request->office_id,
            //     'program_id' => $request->program_id
            // ];

            // $step_id = DB::table('registration_steps')
            // ->where($params)
            // ->value('id');


            foreach ($request->items_array as $key => $value) {
                // get the required amount to submit
                $required_quantity = DB::table('registration_requirements')
                ->where('step_id', $request->step_id)
                ->where('item_id', $value['item'])
                ->value('quantity');

                if ($value['quantity'] > $required_quantity) {
                    return $this->responseMessage(403, "The quantity needed for ".$this->getItemName($value['item'])." is ".$required_quantity.". You have typed ".$value['quantity']);
                } elseif ($value['quantity'] < 0) {
                    return $this->responseMessage(403, "The quantity for ".$this->getItemName($value['item'])." cannot be ".$value['quantity']);
                } else {
                    if (DB::table('student_submitted_inventory')->where([
                        'item_id' => $value['item'],
                        'office_id' => $request->office_id,
                        'registration_id' => $student_registration->student_category,
                        // 'student_id' => $request->student_id,
                        // 'year_id' => $request->year_id,
                        // 'semester_id' => $request->semester_id,
                        'date_submitted' => $request->date_submitted
                    ])->count() > 0) {
                        DB::rollBack();

                        return $this->responseMessage(403, "You are trying to add ".$this->getItemName($value['item']). " multiple times on the same date for the same student");
                    } else {
                        DB::table('student_submitted_inventory')->insert([
                            'author' => $request->user()->id,
                            'item_id' => $value['item'],
                            'office_id' => $request->office_id,
                            'registration_id' => $student_registration->id,
                            // 'student_id' => $request->student_id,
                            // 'year_id' => $request->year_id,
                            // 'program_id' => $request->program_id,
                            // 'semester_id' => $request->semester_id,
                            'date_submitted' => $request->date_submitted,
                            'expected_quantity' => $required_quantity,
                            'submitted' => $value['quantity'],
                            'balance' => $required_quantity - $value['quantity']
                        ]);

                        $this->recordInventory($value['item'], $request->office_id, $value['quantity']);
                    }
                    
                }
            }

            return $this->handleStudentIssues($request);

            // DB::commit();

            // return $this->responseMessage(201, "You have successfully verified and confirmed student");

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    private function recordInventory($item, $office, $quantity) {
        try {
            if (DB::table('inventory')->where([
                'item_id' => $item,
                'office_id' => $office
            ])->count() > 0) {
                $this->incrementInventory($item, $office, $quantity);
            } else {
                DB::table('inventory')->insert([
                    'item_id' => $item,
                    'office_id' => $office,
                    'quantity' => $quantity
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    private function incrementInventory($item, $office, $quantity) {
        DB::table('inventory')->where([
            'item_id' => $item,
            'office_id' => $office
        ])->increment('quantity', $quantity);
    }

    private function handleStudentIssues($request) {

        // now change the status of your stage
        // get the end date of the opening
        $reg_opening = DB::table('student_payments')
        ->join('registration_openings', 'student_payments.opening_id', '=', 'registration_openings.id')
        ->select('registration_openings.end_date', 'registration_openings.id as opening_id', 'registration_openings.penalty_fee', 'student_payments.id', 'student_payments.student_category')
        ->where('student_payments.student_id', $request->student_id)
        ->where('student_payments.year_id', $request->year_id)
        ->where('student_payments.semester_id', $request->semester_id)
        ->first();

        $studentParams = [
            // 'year_id' => $request->year_id,
            // 'semester_id' => $request->semester_id,
            'office_id' => $request->office_id,
            'registration_id' => $reg_opening->id,
            // 'program_id' => $request->program_id,
            // 'student_id' => $request->student_id
        ];

        $max_step = DB::table('student_registration_progress')
        ->join('registration_steps', 'student_registration_progress.step_id', '=', 'registration_steps.id')
        ->where([
            // 'year_id' => $request->year_id,
            // 'semester_id' => $request->semester_id,
            // 'program_id' => $request->program_id,
            // 'student_id' => $request->student_id
            'student_registration_progress.registration_id' => $reg_opening->id
        ])
        ->max('step');

        $current_step = DB::table('student_registration_progress')
        // ->select('student_registration_progress.*', 'registration_steps.id')
        // ->where('student_registration_progress.registration_id', $reg_opening->id)
        // ->where('registration_steps.office_id', $request->office_id)
        ->where('registration_id', $reg_opening->id)
        ->where('step_id', $request->step_id)
        ->first();


        DB::table('student_registration_progress')
        ->where('registration_id', $reg_opening->id)
        ->where('step_id', $current_step->step_id)
        ->update([
            'status' => 'confirmed',
            'date_confirmed' => $request->date_submitted
        ]);

        $step_number = DB::table('registration_steps')->where('id', $current_step->step_id)->value('step');


        if ($step_number < $max_step) {
            // get step number of step id
            $next_step = $step_number + 1;

            // now get the id of next step
            $next_step_id = DB::table('registration_steps')
            ->where([
                'program_id' => $request->program_id,
                'step' => $next_step,
                'student_category' => $reg_opening->student_category
            ])->value('id');

            // make next step active
            DB::table('student_registration_progress')
            ->where([
                // 'year_id' => $request->year_id,
                // 'semester_id' => $request->semester_id,
                // 'program_id' => $request->program_id,
                // 'student_id' => $request->student_id,
                'registration_id' => $reg_opening->id,
                'step_id' => $next_step_id
            ])->update([
                'status' => 'active'
            ]);

            DB::table('registered_students')
            ->where([
                'registration_id' => $reg_opening->id,
            ])->update([
                'registration_type' => $request->date_submitted > $reg_opening->end_date && $reg_opening->student_category == 'returning_students' ? 'late' : 'pending'
            ]);
        } elseif ($step_number == $max_step) {
            # register student because now last stage has arrived and confirmed
            DB::table('registered_students')
            ->where([
                'registration_id' => $reg_opening->id,
            ])->update([
                'date_registered' => $request->date_submitted,
                'status' => 'completed',
                "registration_type" => $request->date_submitted > $reg_opening->end_date && $reg_opening->student_category == 'returning_students' ? 'late' : 'normal',
            ]);
            // update student's current level
            if (DB::table('student_level')->where('student_id', $request->student_id)->count() > 0) {
                DB::table('student_level')->where('student_id', $request->student_id)->update([
                    'year_id' => $request->year_id, 'semester_id' => $request->semester_id
                ]);
            } else {
                DB::table('student_level')->insert(['student_id' => $request->student_id, 'year_id' => $request->year_id, 'semester_id' => $request->semester_id]);
            }

            if ($request->date_submitted > $reg_opening->end_date && $reg_opening->student_category == "returning_students") {
                # penalty to be registered
                if (DB::table('student_penalties')->where('registration_id', $reg_opening->id)->count() == 0) {
                    DB::table('student_penalties')->insert([
                        'registration_id' => $reg_opening->id,
                        'penalty_fee' => $reg_opening->penalty_fee,
                        'paid' => 0,
                        'balance' => $reg_opening->penalty_fee,
                        'validity' => 'active'
                    ]);
                }
            }
        }

        DB::commit();

        return $this->responseMessage(201, "You have successfully verified and confirmed student");
    }

    public function getRegistrationProgress(Request $request) {
        try {
            $progress = DB::table('student_registration_progress as srp')
            ->join('registration_steps as rs', 'srp.step_id', '=', 'rs.id')
            ->join('student_payments as sp', 'srp.registration_id', '=', 'sp.id')
            ->join('programs', 'sp.program_id', '=', 'programs.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->join('offices', 'rs.office_id', '=', 'offices.id')
            ->select('srp.*', 'students.name', 'students.computer_number', 'programs.program_name', 'training_years.year_label', 'training_semesters.semester_label', 'offices.office_name', 'intakes.label')
            ->orderBy('rs.step', 'asc')
            ->where('srp.registration_id', $request->payment_id)
            ->get();

            return response()->json([
                'status'=> 200,
                'data'=> $progress
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function penaltyCharges() {
        try {
            $charges = DB::table('student_penalties as penalty')
            ->join('student_payments as sp', 'penalty.registration_id', '=', 'sp.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('programs', 'sp.program_id', '=', 'programs.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->select('penalty.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'intakes.label')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data'=> $charges
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }

    public function firstStepOffice() {
        try {
            $data = DB::table('penalty_clearance_steps')
            ->where('step_number', 1)
            ->select('office_id')
            ->first();

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage()
            ], 500);
        }
    }
}
