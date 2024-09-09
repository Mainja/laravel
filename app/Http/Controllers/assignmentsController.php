<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class assignmentsController extends Controller
{
    public function index() {
        try {
            $assignments = DB::table("assignments")
            ->join('intakes', 'assignments.intake_id', '=', 'intakes.id')
            ->join('admins', 'assignments.author', '=', 'admins.id')
            ->join('programs', 'assignments.program_id', '=', 'programs.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('training_years', 'assignments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'assignments.semester_id', '=', 'training_semesters.id')
            ->select('assignments.*', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name', 'courses.course_name')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $assignments,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function studentIndex(Request $request) {
        try {
            $assignments = DB::table("assignments")
            ->join('intakes', 'assignments.intake_id', '=', 'intakes.id')
            ->join('admins', 'assignments.author', '=', 'admins.id')
            ->join('programs', 'assignments.program_id', '=', 'programs.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('training_years', 'assignments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'assignments.semester_id', '=', 'training_semesters.id')
            ->select('assignments.*', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'courses.course_name', 'intakes.label')
            ->where('intake_id', $request->user()->intake_id)
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $assignments,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getAssignmentDetails(Request $request) {
        try {
        $assignments = DB::table("assignments")
            ->join('admins', 'assignments.author', '=', 'admins.id')
            ->join('programs', 'assignments.program_id', '=', 'programs.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('training_years', 'assignments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'assignments.semester_id', '=', 'training_semesters.id')
            ->select('assignments.assignment_number', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'courses.course_name')
            ->where('assignments.id', $request->assignment_id)
            ->first();

            $submissions = DB::table('submitted_assignments')
            ->join('students', 'submitted_assignments.student_id', '=', 'students.id')
            ->select('students.name', 'students.computer_number', 'submitted_assignments.*')
            ->where('assignment_id', $request->assignment_id)
            ->get();

            return response()->json([
                'status'=> 200,
                'data' => $submissions,
                'summary' => $assignments
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
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:assignments,title',
                'due_date' => 'required',
                'due_time' => 'required',
                'assignment_number' => 'required',
                'program' => 'required',
                'course' => 'required',
                // 'question' => 'required',
                // 'instructions' => 'required',
                'document' => 'required|mimes:pdf,PDF',
                'year_of_study' => 'required',
                'semester' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $check_courses = DB::table('lecturer_course')
            ->where([
                'admin_id' => $request->user()->id,
                'course_id' => $request->course
            ])->first();

            if (!$check_courses) {
                return $this->responseMessage(403, "The chosen course is not linked to your account. Please go to settings and choose your courses!");
            }

            if (DB::table('assignments')->where([
                'assignment_number' => $request->assignment_number,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ])->count() > 0) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You have already added an assignment in the selected course, year of study, semester and number of assignment'
                ], 403);
            }

            if ($request->due_date < $today) {
                return response()->json([
                    'status'=> 403,
                    'message' => 'The due date cannot be a date before the current date!'
                ], 403);
            }

            $file = $request->file('document');
            $extension = $file->extension();

            $filename = $request->title.uniqid().time().'.'.$extension;

            $request->file('document')->storeAs(
                'assignments', $filename
            );

            DB::table('assignments')->insert([
                'intake_id' => $request->intake,
                'author' => $request->user()->id,
                'title' => $request->title,
                'due_date' => $request->due_date,
                'due_time' => $request->due_time,
                'assignment_number' => $request->assignment_number,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'file_name' => $filename,
                // 'question' => $request->question,
                // 'instructions' => $request->instructions,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Uploaded successfully'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function studentSubmit(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        $today = Carbon::today()->toDateString();
        $time = Carbon::createFromFormat("Y-m-d", $today)->format('H:i:j');
        try {
            $validator = Validator::make($request->all(), [
                'assignment' => 'required|mimes:pdf,PDF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }
             
            $get_ass_details = DB::table('assignments')->where('id', $request->assignment_id)->first();

            if (DB::table('submitted_assignments')->where('assignment_id', $request->assignment_id)->count() > 0) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You have already submitted for this assignment'
                ], 403);
            } elseif ($today > $get_ass_details->due_date) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You cannot submit after the due date!'
                ], 403);
            } elseif ($today == $get_ass_details->due_date && $time > $get_ass_details->due_time) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'The time to submit has passed!'
                ], 403);
            } else {
                $file = $request->file('assignment');
                $extension = $file->extension();

                $filename = $request->description.uniqid().time().'.'.$extension;

                $request->file('assignment')->storeAs(
                    'submitted_assignments', $filename
                );

                DB::table('submitted_assignments')->insert([
                    'student_id' => $request->user()->id,
                    'assignment_id' => $request->assignment_id,
                    'date_submitted' => $today,
                    'time_submitted' => $time,
                    'students_remarks' => $request->message,
                    'file_name' => $filename
                ]);

                return response()->json([
                    'status'=> 201,
                    'message'=> 'You have successfully submitted your assignment'
                ], 201);
            }
            
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
                'title' => 'required|unique:assignments,title,'.$request->id,
                'due_date' => 'required',
                'due_time' => 'required',
                'assignment_number' => 'required',
                'program' => 'required',
                'course' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                // 'question' => 'required',
                // 'instructions' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('assignments')->where('id', $request->id)->update([
                'title' => $request->title,
                'intake_id' => $request->intake,
                'due_date' => $request->due_date,
                'due_time' => $request->due_time,
                'assignment_number' => $request->assignment_number,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                // 'question' => $request->question,
                // 'instructions' => $request->instructions,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Updated successfully'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            if(!DB::table('notes')->find($request->id)) {
                return back();
            }
    
            $file = DB::table('notes')->where('id', $request->id)->value('file_name');
    
            if (DB::table('notes')->where('id', $request->id)->delete()) {
                Storage::delete('notes/'.$file);
            }

            return response()->json([
                'status'=> 200,
                'message'=> 'Assignment deleted successfully'
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
