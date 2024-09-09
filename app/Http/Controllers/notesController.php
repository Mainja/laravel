<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class notesController extends Controller
{
    public function index() {
        try {
            $notes = DB::table("notes")
            ->join('intakes', 'notes.intake_id', '=', 'intakes.id')
            ->join('admins', 'notes.author', '=', 'admins.id')
            ->join('programs', 'notes.program_id', '=', 'programs.id')
            ->join('courses', 'notes.course_id', '=', 'courses.id')
            ->join('training_years', 'notes.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'notes.semester_id', '=', 'training_semesters.id')
            ->select('notes.*', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'intakes.label', 'programs.program_name', 'courses.course_name')
            ->paginate();

            return response()->json([
                'status'=> 200,
                'data' => $notes,
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
            $notes = DB::table("notes")
            ->join('intakes', 'notes.intake_id', '=', 'intakes.id')
            ->join('admins', 'notes.author', '=', 'admins.id')
            ->join('programs', 'notes.program_id', '=', 'programs.id')
            ->join('courses', 'notes.course_id', '=', 'courses.id')
            ->join('training_years', 'notes.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'notes.semester_id', '=', 'training_semesters.id')
            ->select('notes.*', 'admins.name as author_name', 'intakes.label', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'courses.course_name')
            ->where('notes.intake_id', $request->user()->intake_id)
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $notes,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getNotesByCategory(Request $request) {
        try {
            if ($request->category == "all") {
                $notes = DB::table("notes")
                ->join('admins', 'notes.author', '=', 'admins.id')
                ->join('programs', 'notes.program_id', '=', 'programs.id')
                ->join('courses', 'notes.course_id', '=', 'courses.id')
                ->join('training_years', 'notes.year_id', '=', 'training_years.id')
                ->join('training_semesters', 'notes.semester_id', '=', 'training_semesters.id')
                ->select('notes.*', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'courses.course_name')
                ->paginate(50);
            } else {
                $notes = DB::table("notes")
                ->join('admins', 'notes.author', '=', 'admins.id')
                ->join('programs', 'notes.program_id', '=', 'programs.id')
                ->join('courses', 'notes.course_id', '=', 'courses.id')
                ->join('training_years', 'notes.year_id', '=', 'training_years.id')
                ->join('training_semesters', 'notes.semester_id', '=', 'training_semesters.id')
                ->select('notes.*', 'admins.name as author_name', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'courses.course_name')
                ->where('notes.category', $request->category)
                ->paginate(50);
            }

            return response()->json([
                'status'=> 200,
                'data' => $notes,
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
        try {
            $today = Carbon::today()->toDateString();
            $validator = Validator::make($request->all(), [
                'intake' => 'required',
                'title' => 'required|unique:notes,title',
                'program' => 'required',
                'course' => 'required',
                'document' => 'required|mimes:pdf,PDF',
                'year_of_study' => 'required',
                'semester' => 'required',
                'category' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('document');
            $extension = $file->extension();

            $filename = $request->title.uniqid().time().'.'.$extension;

            $request->file('document')->storeAs(
                'notes', $filename
            );

            DB::table('notes')->insert([
                'intake_id' => $request->intake,
                'author' => $request->user()->id,
                'dateposted' => $today,
                'title' => $request->title,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'file_name' => $filename,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'category' => $request->category
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

    public function update(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:notes,title,'.$request->id,
                'intake' => 'required',
                'program' => 'required',
                'course' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
                'category' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('notes')->where('id', $request->id)->update([
                'intake_id' => $request->intake,
                'title' => $request->title,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'category' => $request->category
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
                'message'=> 'Deleted successfully'
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
