<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class coursesController extends Controller
{
    public function index() {
        try {
            $courses = DB::table("courses")
            ->join('programs', 'courses.program_id', '=', 'programs.id')
            ->join('training_years', 'courses.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'courses.semester_id', '=', 'training_semesters.id')
            ->select('courses.*', 'courses.program_id', 'programs.program_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $courses,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getCoursesByProgram(Request $request) {
        try {
            $courses = DB::table("courses")
            ->join('programs', 'courses.program_id', '=', 'programs.id')
            ->join('training_years', 'courses.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'courses.semester_id', '=', 'training_semesters.id')
            ->select('courses.*', 'courses.program_id', 'programs.program_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('program_id', $request->program_id)->get();

            return response()->json([
                'status' => 200,
                'data' => $courses,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getCourseById(Request $request) {
        try {
            $courses = DB::table("courses")
            ->join('programs', 'courses.program_id', '=', 'programs.id')
            ->join('training_years', 'courses.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'courses.semester_id', '=', 'training_semesters.id')
            ->select('courses.*', 'courses.program_id', 'programs.program_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('id', $request->id)->first();

            return response()->json([
                'status' => 200,
                'data' => $courses,
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
                'program' => 'required',
                'course_name' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'course_code' => 'required',        
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            if(DB::table('courses')->where([
                'course_name' => $request->course_name,
                'course_code' => $request->course_code
            ])->count() > 0) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You have already added this course with the specified course code!'
                ], 403);
            }

            DB::table('courses')->insert([
                'program_id' => $request->program,
                'course_name' => $request->course_name,
                'course_slug' => Str::slug($request->course_name),
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'course_code' => $request->course_code,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Course has been created successfully',
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
                'program' => 'required',
                'course_name' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'course_code' => 'required',           
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 403,
                    'errors' => $validator->errors(),
                ], 403);
            }

            if(DB::table('courses')->where([
                'course_name' => $request->course_name,
                'course_code' => $request->course_code,
                [
                    'id', '!=', $request->id
                ]
            ])->count() > 0) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You have already added this course with the specified course code!'
                ], 403);
            }

            DB::table('courses')->where('id', $request->id)->update([
                'course_name' => $request->course_name,
                'program_id' => $request->program,
                'course_slug' => Str::slug($request->course_name),
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'course_code' => $request->course_code,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Course has been updated successfully',
            ], 200);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }
    public function destroy(Request $request) {
        try {
            $Course = DB::table('courses')->where('id', $request->id)->first();

            if ($Course->count() == 1) {
                DB::table('courses')->where('id', $request->id)->delete();

                return response()->json([
                    'status'=> 200,
                    'messages' => 'Course has been deleted successfully',
                ]);
            }
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }
}
