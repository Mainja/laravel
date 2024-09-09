<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class applicationController extends Controller
{
    public function Apply(Request $request) {
        try {
            $validate = Validator::make($request->all(), [
                'application_type' => 'required',
                'program' => 'required',
                'intake' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'phone_number' => 'required',
                'gender' => 'required',
                'nationality' => 'required',
                'address' => 'required',
                'referral_code' => 'nullable|exists:students,referral_code',
                'nrc_file' => 'required|mimes:pdf',
                'results_file' => 'required|mimes:pdf'
            ], [
                'application_type.required' => 'Choose a program type'
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'errors' => $validate->errors()
                ], 422);
            }

            // define nrc file details
            $nrc = $request->file('nrc_file');
            $extension = $nrc->extension();

            $nrc_file_name = $request->email.uniqid().time().'.'.$extension;

            $request->file('nrc_file')->storeAs(
                'applicant_nrc', $nrc_file_name
            );

            // define results file details
            $results = $request->file('results_file');
            $extension = $results->extension();

            $results_file_name = $request->email.uniqid().time().'.'.$extension;

            $request->file('results_file')->storeAs(
                'applicant_results', $results_file_name
            );


            DB::table('applications')->insert([
                'program_id' => $request->program['id'],
                'intake_id' => $request->intake['id'],
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender['value'],
                'address' => $request->address,
                'country_id' => $request->nationality['id'],
                'results' => $results_file_name,
                'nrc' => $nrc_file_name,
                'referral_code' => $request->referral_code
            ]);

            return response()->json([
                'message' => 'Your application has been sent successfully!'
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getApplicants(Request $request) {
        try {
            $applicants = DB::table('applications')
            ->join('intakes', 'applications.intake_id', '=', 'intakes.id')
            ->join('programs', 'applications.program_id', '=', 'programs.id')
            ->join('countries', 'applications.country_id', '=', 'countries.id')
            ->select('applications.*', 'programs.program_name', 'intakes.label', 'countries.name')
            ->where('status', 'pending')
            ->paginate();

            return response()->json([
                'data' => $applicants
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function countApplicants(Request $request) {
        try {
            $applicants = DB::table('applications')
            ->join('intakes', 'applications.intake_id', '=', 'intakes.id')
            ->join('programs', 'applications.program_id', '=', 'programs.id')
            ->join('countries', 'applications.country_id', '=', 'countries.id')
            ->select('applications.*', 'programs.program_name', 'intakes.label', 'countries.name')
            ->where('status', 'pending')
            ->count();

            return response()->json([
                'data' => $applicants
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
