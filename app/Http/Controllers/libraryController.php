<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class libraryController extends Controller
{
    public function index() {
        try {
            $books = DB::table("digital_library")
            ->join('programs', 'digital_library.program_id', '=', 'programs.id')
            ->join('courses', 'digital_library.course_id', '=', 'courses.id')
            ->join('admins', 'digital_library.author', '=', 'admins.id')
            ->select('digital_library.*', 'programs.program_name', 'admins.name as author_name', 'courses.course_name')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $books,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getBooksByProgram(Request $request) {
        try {
            $books = DB::table("digital_library")
            ->join('programs', 'digital_library.program_id', '=', 'programs.id')
            ->join('courses', 'digital_library.course_id', '=', 'courses.id')
            ->select('digital_library.*', 'programs.program_name', 'courses.course_name')
            ->where('program_id', $request->user()->program_id)
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $books,
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
                'book_title' => 'required|unique:digital_library,book_title',
                'program' => 'required',
                'course' => 'required',
                'document' => 'required|mimes:pdf,PDF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('document');
            $extension = $file->extension();

            $filename = Str::slug($request->book_title.uniqid().time(), '-').'.'.$extension;

            $request->file('document')->storeAs(
                'library', $filename
            );

            DB::table('digital_library')->insert([
                'author' => $request->user()->id,
                'program_id' => $request->program,
                'course_id' => $request->course,
                'book_title' => $request->book_title,
                'file_name' => $filename,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Book uploaded successfully'
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
                'book_title' => 'required|unique:digital_library,book_title,'.$request->id,
                'program' => 'required',
                'course' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('digital_library')->where('id', $request->id)->update([
                'book_title' => $request->book_title,
                'program_id' => $request->program,
                'course_id' => $request->course,
            ]);

            return response()->json([
                'status'=> 201,
                'message'=> 'Book details updated successfully'
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
            if(!DB::table('digital_library')->find($request->id)) {
                return back();
            }
    
            $file = DB::table('digital_library')->where('id', $request->id)->value('file_name');
    
            if (DB::table('digital_library')->where('id', $request->id)->delete()) {
                Storage::delete('library/'.$file);
            }

            return response()->json([
                'status'=> 200,
                'message'=> 'Book deleted successfully'
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
