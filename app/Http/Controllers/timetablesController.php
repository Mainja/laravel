<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class timetablesController extends Controller
{
    public function index() {
        try {
            $timetables = DB::table("timetables")
            ->join('intakes', 'timetables.intake_id', '=', 'intakes.id')
            ->join('programs', 'timetables.program_id', '=', 'programs.id')
            ->join('training_years', 'timetables.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'timetables.semester_id', '=', 'training_semesters.id')
            ->select('timetables.*', 'training_years.year_label', 'training_semesters.semester_label', 'programs.program_name', 'intakes.label')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $timetables,
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
            $timetables = DB::table("timetables")
            ->join('intakes', 'timetables.intake_id', '=', 'intakes.id')
            ->join('programs', 'timetables.program_id', '=', 'programs.id')
            ->join('training_years', 'timetables.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'timetables.semester_id', '=', 'training_semesters.id')
            ->select('timetables.*', 'training_years.year_label', 'intakes.label', 'training_semesters.semester_label', 'programs.program_name')
            ->where('timetables.intake_id', $request->user()->intake_id)
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $timetables,
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
                'intake' => 'required',
                'description' => 'required',
                'program' => 'required',
                'year' => 'required|numeric',
                'category' => 'required',
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

            $file = $request->file('document');
            $extension = $file->extension();

            $filename = $request->description.uniqid().time().'.'.$extension;

            $request->file('document')->storeAs(
                'timetables', $filename
            );

            DB::table('timetables')->insert([
                'author' => $request->user()->id,
                'intake_id' => $request->intake,
                'description' => $request->description,
                'program_id' => $request->program,
                'year' => $request->year,
                'category' => $request->category,
                'file_name' => $filename,
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

    public function update(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'intake' => 'required',
                'description' => 'required',
                'program' => 'required',
                'year' => 'required|numeric',
                'category' => 'required',
                'year_of_study' => 'required',
                'semester' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            

            DB::table('timetables')->where('id', $request->id)->update([
                'description' => $request->description,
                'program_id' => $request->program,
                'intake_id' => $request->intake,
                'year' => $request->year,
                'category' => $request->category,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
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
            if(!DB::table('timetables')->find($request->id)) {
                return back();
            }
    
            $file = DB::table('timetables')->where('id', $request->id)->value('file_name');
    
            if (DB::table('timetables')->where('id', $request->id)->delete()) {
                Storage::delete('timetables/'.$file);
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
