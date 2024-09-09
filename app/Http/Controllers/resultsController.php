<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class resultsController extends Controller
{
    public function index() {
        try {
            $results = DB::table("results")
            ->join('programs', 'results.program_id', '=', 'programs.id')
            ->join('intakes', 'results.intake_id', '=', 'intakes.id')
            ->join('training_years', 'results.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'results.semester_id', '=', 'training_semesters.id')
            ->select('results.*', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentCA(Request $request) {
        try {
            $results = DB::table("results")
            ->join('result_details', 'result_details.result_id', '=', 'results.id')
            ->join('students', 'result_details.student_id', '=', 'students.id')
            ->join('programs', 'results.program_id', '=', 'programs.id')
            ->join('intakes', 'results.intake_id', '=', 'intakes.id')
            ->join('training_years', 'results.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'results.semester_id', '=', 'training_semesters.id')
            ->select('results.id', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name')
            ->where('result_details.student_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentCAByStudent(Request $request) {
        try {
            $results = DB::table("results")
            ->join('result_details', 'result_details.result_id', '=', 'results.id')
            ->join('training_years', 'results.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'results.semester_id', '=', 'training_semesters.id')
            ->select('results.id', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('result_details.student_id', $request->user()->id)
            ->distinct()
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentResults(Request $request) {
        try {
            $results = DB::table("results")
            ->join('exam_results', 'exam_results.result_id', '=', 'results.id')
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->join('programs', 'results.program_id', '=', 'programs.id')
            ->join('intakes', 'results.intake_id', '=', 'intakes.id')
            ->join('training_years', 'results.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'results.semester_id', '=', 'training_semesters.id')
            ->select('results.*', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name')
            ->where('exam_results.student_id', $request->user()->id)
            ->where('exam_results.exam_type', 'first_attempt')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentResitResults(Request $request) {
        try {
            $results = DB::table("results")
            ->join('exam_results', 'exam_results.result_id', '=', 'results.id')
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->join('programs', 'results.program_id', '=', 'programs.id')
            ->join('intakes', 'results.intake_id', '=', 'intakes.id')
            ->join('training_years', 'results.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'results.semester_id', '=', 'training_semesters.id')
            ->select('results.*', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name')
            ->where('exam_results.student_id', $request->user()->id)
            ->where('exam_results.exam_type', 'resit')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getResultDetails(Request $request) {
        try {
            $results = DB::table("result_details")
            ->join('admins', 'result_details.author', '=', 'admins.id')
            ->join('students', 'result_details.student_id', '=', 'students.id')
            ->join('courses', 'result_details.course_id', '=', 'courses.id')
            ->select('result_details.*', 'courses.course_name', 'admins.name as author_name', 'students.name', 'students.computer_number')
            ->where('result_id', $request->id)
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentResultDetails(Request $request) {
        try {
            $results = DB::table("exam_results")
            ->join('admins', 'exam_results.author', '=', 'admins.id')
            ->join('courses', 'exam_results.course_id', '=', 'courses.id')
            ->select('exam_results.id', 'exam_results.final_mark', 'exam_results.grade', 'courses.course_name')
            ->where('result_id', $request->id)
            ->where('student_id', $request->user()->id)
            ->where('exam_type', 'first_attempt')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentResitResultsDetails(Request $request) {
        try {
            $results = DB::table("exam_results")
            ->join('admins', 'exam_results.author', '=', 'admins.id')
            ->join('courses', 'exam_results.course_id', '=', 'courses.id')
            ->select('exam_results.id', 'exam_results.final_mark', 'exam_results.grade', 'courses.course_name')
            ->where('result_id', $request->id)
            ->where('student_id', $request->user()->id)
            ->where('exam_type', 'resit')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentCADetails(Request $request) {
        try {
            $results = DB::table("result_details")
            ->join('admins', 'result_details.author', '=', 'admins.id')
            ->join('courses', 'result_details.course_id', '=', 'courses.id')
            ->select('result_details.id', 'result_details.assignment_1_percent', 'result_details.test_1_percent', 'result_details.assignment_2_percent', 'result_details.test_2_percent', 'result_details.ca', 'courses.course_name')
            ->where('result_id', $request->id)
            ->where('student_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $results,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    private function responseMessage($errorCode, $message) {
        return response()->json([
            'status' => $errorCode,
            'message'=> $message
        ], $errorCode);
    }

   
    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'program_of_study' => 'required',
                'course' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'assessment' => 'required',
                'results' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            // check if admin has chosen course
            $check_courses = DB::table('lecturer_course')
            ->where([
                'admin_id' => $request->user()->id,
                'course_id' => $request->course
            ])->first();

            if (!$check_courses && !auth()->user()->tokenCan('role:admin')) {
                return $this->responseMessage(403, "The chosen course is not linked to your account. Please go to settings and choose your courses!");
            }

            $file = $request->file('results');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));

            $params = [
                'program_id' => $request->program_of_study,
                'intake_id' => $request->intake,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester 
            ];

            $check = DB::table('results')
            ->where($params)
            ->first();

            DB::beginTransaction();

            if ($check) {
                $result_id = DB::table('results')->where($params)->value('id');
            } else {
                $result_id = DB::table('results')->insertGetId($params);
            }

            $count = 0;


            foreach ($data as $value) {
                $count++;
                if ($count == 1) { continue; }

                $check = DB::table('students')->where('computer_number', $value[1])->first();

                if (!$check) {
                    return response()->json([
                        'status'=> 404,
                        'message' => 'The student number '.$value[1].' has no student record in the database'
                    ], 404);
                } else {
                    $student_id = DB::table("students")
                    ->where('computer_number', $value[1])
                    ->value('id');

                    $result_params = [
                        'result_id' => $result_id,
                        'course_id' => $request->course,
                        'student_id' => $student_id
                    ];

                    $check_result = DB::table('result_details')
                    ->where($result_params)->first();

                    if ($request->assessment == "ass1") {
                        if ($value[2] == "") {
                            return $this->responseMessage(404, "The assignment 1 column is empty for computer number: ".$value[1]);
                        } else {
                            if (!$check_result) {
                                // get student id
                                $student_id = DB::table("students")
                                ->where('computer_number', $value[1])
                                ->value('id');

                                DB::table('result_details')->insert([
                                    'author' => $request->user()->id,
                                    'result_id' => $result_id,
                                    'course_id' => $request->course,
                                    'student_id' => $student_id,
                                    'assignment_1' => $value[2],
                                    'assignment_1_percent' => ($value[2] / 100) * 10
                                ]);

                                DB::commit();

                                return $this->responseMessage(201, 'Results have been uploaded successfully');
                            } elseif ($check_result && $check_result->assignment_1 == "") {
                                $student_id = DB::table("students")
                                ->where('computer_number', $value[1])
                                ->value('id');

                                DB::table('result_details')->insert([
                                    'author' => $request->user()->id,
                                    'result_id' => $result_id,
                                    'course_id' => $request->course,
                                    'student_id' => $student_id,
                                    'assignment_1' => $value[2],
                                    'assignment_1_percent' => ($value[2] / 100) * 10
                                ]);
                                DB::commit();

                                return $this->responseMessage(201, 'Results have been uploaded successfully');
                            } elseif ($check_result && $check_result->assignment_1 != "") {
                                
                                DB::rollBack();

                                return $this->responseMessage(403, 'You have already uploaded assignment 1 for computer number '.$value[1]);
                            }
                        }
                    } elseif ($request->assessment == "test1") {
                        // check if ass 1 exists
                        if ($value[3] == "") {
                            return $this->responseMessage(404, "The Test 1 column is empty for computer number: ".$value[1]);
                        }elseif (!$check_result) {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Assignment 1 first in order to upload Test 1 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->assignment_1 == '') {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Assignment 1 first in order to upload Test 1 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->test_1 == "") {
                            DB::table('result_details')->where($result_params)->update([
                                'test_1' => $value[3],
                                'test_1_percent' => ($value[3] / 100) * 10
                            ]);
                            DB::commit();

                            return $this->responseMessage(201, 'Results have been updated successfully');
                        } elseif ($check_result && $check_result->test_1 != "") {
                            
                            DB::rollBack();

                            return $this->responseMessage(403, 'You have already uploaded test 1 for computer number '.$value[1]);
                        }
                    } elseif ($request->assessment == "ass2") {
                        // check if test 1 exists
                        if ($value[4] == "") {
                            return $this->responseMessage(404, "The Assignment 2 column is empty for computer number: ".$value[1]);
                        }elseif (!$check_result) {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Test 1 first in order to upload Assignment 2 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->test_1 == '') {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Test 1 first in order to upload Assignment 2 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->assignment_2 == "") {
                            DB::table('result_details')->where($result_params)->update([
                                'assignment_2' => $value[4],
                                'assignment_2_percent' => ($value[4] / 100) * 10
                            ]);
                            DB::commit();

                            return $this->responseMessage(201, 'Results have been updated successfully');
                        } elseif ($check_result && $check_result->assignment_2 != "") {
                            
                            DB::rollBack();

                            return $this->responseMessage(403, 'You have already uploaded Assignment 2 for computer number '.$value[1]);
                        }
                    } elseif ($request->assessment == "test2") {
                        // check if test 1 exists
                        if ($value[5] == "") {
                            return $this->responseMessage(404, "The Test 2 column is empty for computer number: ".$value[1]);
                        }elseif (!$check_result) {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Assignment 2 first in order to upload Test 2 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->assignment_2 == '') {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Assignment 2 first in order to upload Test 2 for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->test_2 == "") {
                            // finally update the CA

                            DB::table('result_details')->where($result_params)->update([
                                'test_2' => $value[5],
                                'test_2_percent' => ($value[5] / 100) * 10,
                                'ca' => round($check_result->assignment_1_percent + $check_result->test_1_percent + $check_result->assignment_2_percent + (($value[5] / 100) * 10))
                            ]);

                            DB::commit();

                            return $this->responseMessage(201, 'Results have been updated successfully');
                        } elseif ($check_result && $check_result->test_2 != "") {
                            
                            DB::rollBack();

                            return $this->responseMessage(403, 'You have already uploaded Test 2 for computer number '.$value[1]);
                        }
                    } elseif ($request->assessment == "end_of_course") {
                        // check if test 1 exists
                        if ($value[6] == "") {
                            return $this->responseMessage(404, "The End of Course column is empty for computer number: ".$value[1]);
                        }elseif (!$check_result) {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Test 2 first in order to upload End of course exam for computer number '.$value[1]);
                        } elseif ($check_result && $check_result->test_2 == '') {
                            DB::rollback();

                            return $this->responseMessage(404, 'Upload Test 2 first in order to upload End of course exam for computer number '.$value[1]);
                        } else {
                            $check_exam = DB::table('exam_results')
                            ->where($result_params)
                            ->where('exam_type', $request->exam_type)
                            ->first();

                            if ($check_exam) {
                                DB::rollBack();

                                $type = $request->exam_type == 'first_attempt' ? 'first attempt' : 'resit';
    
                                return $this->responseMessage(403, 'You have already uploaded the '.$type.' end of course exam for computer number '.$value[1]);
                            } else {
                                // $student_id = DB::table("students")
                                // ->where('computer_number', $value[1])
                                // ->value('id');
                                // now update the final mark
                                DB::table('exam_results')->insert([
                                    'author' => $request->user()->id,
                                    'exam_type' => $request->exam_type,
                                    'student_id' => $student_id,
                                    'course_id' => $request->course,
                                    'result_id' => $result_id,
                                    'end_of_course' => $value[6],
                                    'end_of_course_percent' => ($value[6] / 100) * 60,
                                    'final_mark' => round($check_result->ca + (($value[6] / 100) * 60))
                                ]);
    
                                DB::commit();
    
                                return $this->responseMessage(201, 'Results have been updated successfully');
                            } 
                            // elseif ($check_result && $check_exam->end_of_course != "") {
                                
                            //     DB::rollBack();
    
                            //     return $this->responseMessage(403, 'You have already uploaded the End of Course exam for computer number '.$value[1]);
                            // }
                        } 
                    }
                }

                // skip the existing records
                // check if all provided computer numbers have a student in the db
            }

            
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getExamResultDetails(Request $request) {
        try {
            $result_details = DB::table('exam_results')
            ->join('results', 'exam_results.result_id', '=', 'results.id')
            ->join('courses', 'exam_results.course_id', '=', 'courses.id')
            ->select('exam_results.*', 'courses.course_name')
            ->where([
                'exam_results.exam_type' => $request->exam_type,
                'results.year_id' => $request->year_of_study,
                'results.semester_id' => $request->semester,
                'exam_results.student_id' => $request->student,
                'results.program_id' => $request->program_of_study
            ])
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $result_details
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
