<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class trainingLevelsController extends Controller
{
    public function getSemesters() {
        try {
            $semesters = DB::table('training_semesters')
            ->select('id', 'semester_label', 'semester_number')
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $semesters
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getYears() {
        try {
            $years = DB::table('training_years')
            ->select('id', 'year_label', 'year_number')
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $years
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
