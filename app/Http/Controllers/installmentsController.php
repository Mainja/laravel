<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class installmentsController extends Controller
{
    public function installmentsPendingToday() {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();

            $data = DB::table('student_installment_payments')
            ->join('student_payments', 'student_installment_payments.registration_id', '=', 'student_payments.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->select('student_installment_payments.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->orderBy('installment_number', 'asc')
            ->where([
                'date_expected' => $today,
                [
                    'student_installment_payments.balance', '>', 0
                ]
            ])
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data'=> $data,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function installmentsPendingTodayCount() {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();

            $data = DB::table('student_installment_payments')
            ->join('student_payments', 'student_installment_payments.registration_id', '=', 'student_payments.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->select('student_installment_payments.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->orderBy('installment_number', 'asc')
            ->where([
                'date_expected' => $today,
                [
                    'student_installment_payments.balance', '>', 0
                ]
            ])
            ->count();

            return response()->json([
                'status' => 200,
                'data'=> $data,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function installmentsPastPaymentDate() {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();

            $data = DB::table('student_installment_payments')
            ->join('student_payments', 'student_installment_payments.registration_id', '=', 'student_payments.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->select('student_installment_payments.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->orderBy('installment_number', 'asc')
            ->where('date_expected', '<', $today)
            ->where('student_installment_payments.balance', '>', 0)
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data'=> $data,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function installmentsPastPaymentDateCount() {
        try {
            date_default_timezone_set("Africa/Lusaka");
            $today = Carbon::today()->toDateString();

            $data = DB::table('student_installment_payments')
            ->join('student_payments', 'student_installment_payments.registration_id', '=', 'student_payments.id')
            ->join('training_years', 'student_payments.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_payments.semester_id', '=', 'training_semesters.id')
            ->join('students', 'student_payments.student_id', '=', 'students.id')
            ->select('student_installment_payments.*', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->orderBy('installment_number', 'asc')
            ->where('date_expected', '<', $today)
            ->where('student_installment_payments.balance', '>', 0)
            ->count();

            return response()->json([
                'status' => 200,
                'data'=> $data,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getByRegistration(Request $request) {
        try {
            $installments = DB::table('student_installment_payments')
            ->select('id', 'installment_number', 'installment_amount', 'amount_paid', 'balance', 'date_expected', 'amount_expected')
            ->where('registration_id', $request->payment_id)
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $installments,
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
