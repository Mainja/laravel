<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\resetPassword;
use App\Mail\addStudent;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class studentsController extends Controller
{
    public function index(Request $req) {
        try {
            $students = DB::table('students')
            ->join('countries', 'students.country_id', '=', 'countries.id')
            ->join('programs', 'students.program_id', '=', 'programs.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('admins', 'students.author', '=', 'admins.id')
            ->select('students.*', 'programs.program_name', 'intakes.label', 'admins.name as author_name', 'countries.name as country_name')
            ->when($req->program_filter, function($query, $search) {
                if ($search != "all") {
                    $query->where('students.program_id', $search);
                }
            })
            ->when($req->intake_filter, function($query, $search) {
                if ($search != "all") {
                    $query->where('students.intake_id', $search);
                }
            })
            ->when($req->search, function($query, $search) {
                $query->where('students.name', 'LIKE', '%'.$search.'%')
                    ->orWhere('students.email', 'LIKE', '%'.$search.'%')
                    ->orWhere('students.computer_number', 'LIKE', '%'.$search.'%')
                    ->orWhere('students.phone_number', 'LIKE', '%'.$search.'%')
                    ->orWhere('students.nrc_number', 'LIKE', '%'.$search.'%');
            })
            ->paginate();

            return response()->json([
                'status' => 200,
                'data'=> $students,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentById(Request $request) {
        try {
            $students = DB::table('students')
            ->join('countries', 'students.country_id', '=', 'countries.id')
            ->join('programs', 'students.program_id', '=', 'programs.id')
            ->join('intakes', 'students.intake_id', '=', 'intakes.id')
            ->join('admins', 'students.author', '=', 'admins.id')
            ->select('students.*', 'programs.program_name', 'intakes.label', 'admins.name as author_name', 'countries.name as country_name')
            ->where('students.id', $request->student_id)
            ->first();

            $intake_code = DB::table('intakes')
            ->where('id', $students->intake_id)
            ->value('intake_code');

            return response()->json([
                'status' => 200,
                'data'=> $students,
                // 'intake_code' => $intake_code,
                // 'position' => $students->positional_index
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentByProgram(Request $request) {
        try {
            $students = DB::table('students')
            ->select('students.id', 'students.name', 'students.intake_id', 'students.computer_number')
            ->where('students.program_id', $request->program_id)
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $students,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentByProgramAndIntake(Request $request) {
        try {
            $students = DB::table('students')
            ->select('students.id', 'students.name', 'students.intake_id', 'students.computer_number')
            ->where('students.program_id', $request->program_id)
            ->where('students.intake_id', $request->intake_id)
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $students,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentByIntake(Request $request) {
        try {
            $students = DB::table('students')
            ->select('students.id', 'students.name', 'students.intake_id', 'students.computer_number')
            ->where('students.intake_id', $request->intake_id)
            ->get();

            return response()->json([
                'status' => 200,
                'data'=> $students,
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
                'nationality' => 'required',
                'program_of_study' => 'required',                
                'intake' => 'required',          
                'name' => 'required',
                'email' => 'email|unique:students,email',
                'computer_number' => 'required|unique:students,computer_number',
                'nrc_number' => 'required',
                'phone_number' => 'required|unique:students,phone_number',
                'gender' => 'required',
                'year_of_study' => 'required_if:registered,yes',
                'semester' => 'required_if:registered,yes',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $intake_code = DB::table('intakes')->where('id', $request->intake)->value('intake_code');

            DB::beginTransaction();
             
            $student = DB::table('students')->insertGetId([
                'intake_id' => $request->intake,
                'program_id' => $request->program_of_study,
                'country_id' => $request->nationality,
                // 'state_id' => $request->state,
                'author' => $request->user()->id,
                // 'positional_index' => $request->computer_number,
                'name' => $request->name,
                'email' => $request->email,
                'computer_number' => $request->computer_number,
                'index_number' => $request->index_number,
                'date_of_birth' => $request->date_of_birth,
                'nrc_number' => $request->nrc_number,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'sponsor_name' => $request->sponsor_name,
                'sponsor_relation' => $request->sponsor_relation,
                'sponsor_phone_number' => $request->sponsor_phone_number,
                'next_of_kin_name' => $request->next_of_kin_name,
                'next_of_kin_relation' => $request->next_of_kin_relation,
                'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
                'physical_address' => $request->physical_address,
                'password' => Hash::make($request->computer_number),
            ]);

            DB::table('student_level')->insert(['student_id' => $student, 'year_id' => $request->year_of_study, 'semester_id' => $request->semester]);

            if ($request->email != "") {
                Mail::to($request->email)->send(new addStudent($request->name, $request->computer_number, 'https://student.pomicollege.com'));
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message'=> 'Student has been created successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function storeCSV(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'program' => 'required',                
                'intake' => 'required',          
                'year_of_study' => 'required',
                'semester' => 'required',
                'document' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('document');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));

            DB::beginTransaction();

            $count = 0;

            foreach ($data as $value) {
                $count++;
                if ($count == 1) { continue; }

                if ($value[15] != "" && $value['6'] != "") {
                    $check_computer_number = DB::table('students')->where('computer_number', $value[7])->first();
                    $check_phone_number = DB::table('students')->where('phone_number', $value[6])->first();
                    $check_email = DB::table('students')->where('email', $value[15])->first();
                    $check_nrc = DB::table('students')->where('nrc_number', $value[4])->first();

                    if ($check_computer_number) {
                        return response()->json([
                            'status'=> 404,
                            'message' => 'The computer number '.$value[7].' already exists on '.$value['1']
                        ], 404);
                    } elseif($check_phone_number) {
                        return response()->json([
                            'status'=> 404,
                            'message' => 'The phone number '.$value[6].' already exists on '.$value['1']
                        ], 404);
                    } elseif($check_email) {
                        return response()->json([
                            'status'=> 404,
                            'message' => 'The email '.$value[15].' already exists on '.$value['1']
                        ], 404);
                    } elseif($check_nrc) {
                        return response()->json([
                            'status'=> 404,
                            'message' => 'The NRC number '.$value[4].' already exists on '.$value['1']
                        ], 404);
                    } else {
                        // all is good, save now
                        $student = DB::table('students')->insertGetId([
                            'intake_id' => $request->intake,
                            'program_id' => $request->program,
                            'country_id' => $request->nationality,
                            // 'state_id' => $request->state,
                            'author' => $request->user()->id,
                            // 'positional_index' => $request->computer_number,
                            'name' => $value['1'],
                            'email' => $value['15'],
                            'computer_number' => $value['7'],
                            'date_of_birth' => Carbon::createFromFormat("d/m/Y", $value['5'])->format('Y-m-d'),
                            'nrc_number' => $value['4'],
                            'gender' => strtolower($value['2']) == "m" ? 'male' : 'female', 
                            'phone_number' => $value['6'],
                            'sponsor_name' => $value['8'],
                            'sponsor_relation' => $value['9'],
                            'sponsor_phone_number' => $value['10'],
                            'next_of_kin_name' => $value['12'],
                            'next_of_kin_relation' => $value['15'],
                            'next_of_kin_phone_number' => $value['13'],
                            'physical_address' => $value['11'],
                            'password' => Hash::make($value['7']),
                        ]);

                        DB::table('student_level')->insert(['student_id' => $student, 'year_id' => $request->year_of_study, 'semester_id' => $request->semester]);

                        if ($value['15'] != "") {
                            Mail::to($value['15'])->send(new addStudent($value['1'], $value['7'], 'https://student.pomicollege.com'));
                        }
                    }
                }

                // skip the existing records
                // check if all provided computer numbers have a student in the db
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message'=> 'Students have been added successfully'
            ], 200);
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
                'country_id' => 'required',
                // 'state' => 'required',
                'program_id' => 'required',                
                'intake_id' => 'required',          
                'name' => 'required',
                'email' => 'email:unique:students,email',
                'computer_number' => 'required|unique:students,computer_number,'.$request->id,
                'nrc_number' => 'required',
                'phone_number' => 'required:unique:students,phone_number',
                'gender' => 'required',
            ], [
                'program_id.required' => 'Please choose a program of study',
                'intake_id.required' => 'Please choose an intake',
                'country_id.required' => 'Please choose nationality'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $intake_code = DB::table('intakes')->where('id', $request->intake)->value('intake_code');


            DB::beginTransaction();
             
            DB::table('students')->where('id', $request->id)->update([
                'intake_id' => $request->intake_id,
                'program_id' => $request->program_id,
                'author' => $request->user()->id,
                'country_id' => $request->country_id,
                // 'state_id' => $request->state,
                // 'positional_index' => $request->computer_number,
                'name' => $request->name,
                'email' => $request->email,
                'computer_number' => $request->computer_number,
                'date_of_birth' => $request->date_of_birth,
                'nrc_number' => $request->nrc_number,
                'gender' => $request->gender,
                'index_number' => $request->index_number,
                'phone_number' => $request->phone_number,
                'sponsor_name' => $request->sponsor_name,
                'sponsor_relation' => $request->sponsor_relation,
                'sponsor_phone_number' => $request->sponsor_phone_number,
                'next_of_kin_name' => $request->next_of_kin_name,
                'next_of_kin_relation' => $request->next_of_kin_relation,
                'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
                'physical_address' => $request->physical_address,
            ]);

            DB::commit(); 

            return response()->json([
                'status' => 200,
                'message'=> 'Student has been updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function getLastPosition(Request $request) {
        try {
            // $position_ids = DB::table('students')
            // ->select('positional_index')
            // ->orderBy('positional_index')
            // ->where('intake_id', $request->intake_id)
            // ->get();
            // $maximum = $position_ids->pop(); // this will get the maximum
            $maximum = DB::table('students')
            ->select('positional_index')
            ->orderby('positional_index', 'desc')
            ->where('intake_id', $request->intake_id)
            ->first();

            if ($maximum) {
                return response()->json([
                    'status' => 200,
                    'data' => $maximum->positional_index + 1
                ], 200);
            } else {
                return response()->json([
                    'status' => 200,
                    'data' => 0001
                ], 200);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function registeredStudents(Request $request) {
        try {
            $data = DB::table('registered_students as rs')
            ->join('student_payments as sp', 'rs.registration_id', '=', 'sp.id')
            ->join('students as std', 'sp.student_id', '=', 'std.id')
            ->join('programs as pr', 'sp.program_id', '=', 'pr.id')
            ->join('intakes', 'std.intake_id', '=', 'intakes.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->select('rs.*', 'training_years.year_label', 'training_semesters.semester_label', 'std.name', 'std.computer_number', 'intakes.label', 'pr.program_name')
            ->where('rs.status', 'completed')
            ->orderBy('date_registered', 'asc')
            ->paginate(50);

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function sendResetLink(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        try {
            $validate = Validator::make($request->all(),[
                'email' => 'required|email|exists:students,email',
            ], [
                'email.exists' => 'The email you provided does not match any existing account!'
            ]);

            if($validate->fails()){
                return response()->json([
                    'status' => 422,
                    'message' => 'validation error',
                    'errors' => $validate->errors()
                ], 422);
            }

            $token = Str::random(128);

            \date_default_timezone_set('Africa/Lusaka');
    
            $expiry = Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s');
    
            $url = 'https://student.pomicollege.com/?#/update-your-password/'.$token;
    
            DB::beginTransaction();

            // check if there are unused tokens for this email
            $check = DB::table('student_password_reset_tokens')
            ->where(['email' => $request->email])
            ->first();

            if ($check) {
                DB::table('student_password_reset_tokens')
                ->where(['email' => $request->email])
                ->delete();
            }
    
            DB::table('student_password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'expiry_date' => $expiry
            ]);
    
            Mail::to($request->email)->send(new resetPassword($token, $url));
    
            DB::commit();

            return response()->json([
                'status' => 201,
                'message' => "We have sent you an email with a link to reset your password!",
            ], 201);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function resetPasswordStudent(Request $req) {
        $validate = Validator::make($req->all(),[
            'email' => 'required|exists:admins,email|email',
            'password' => ['required', 
            Password::min(8)
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised(3)],
            'password_confirmation' => 'required|same:password'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 422,
                'message' => 'validation error',
                'errors' => $validate->errors()
            ], 422);
        }

        \date_default_timezone_set('Africa/Lusaka');

        $now = Carbon::now()->format('Y-m-d H:i:s');

        $check = DB::table('student_password_reset_tokens')
        ->where(['email' => $req->email, 'token' => $req->token])
        ->first();

        if($now > $check->expiry_date) {
            return response()->json([
                'status' => 403,
                'message' => 'The link you have used has expired. Please restart the forgot password process!',
            ], 403);
        } else {
            DB::beginTransaction();

            DB::table('students')->where('email', $req->email)->update([
                'password' => Hash::make($req->password)
            ]);
            DB::table('student_password_reset_tokens')
            ->where(['email' => $req->email, 'token' => $req->token])
            ->delete();
           
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'You have successfully reset your password. Login to your account now!',
            ], 200);
        }
    }

    public function updateContactDetails(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                "name"=> "required",
                "email" => "required|unique:students,email,".$request->user()->id,
                'phone_number' => 'required|unique:students,phone_number,'.$request->user()->id,
                'index_number' => 'nullable|unique:students,index_number,'.$request->user()->id,
                'nrc_number' => 'required|unique:students,nrc_number,'.$request->user()->id,
                "date_of_birth"=> "required",
                "sponsor_name"=> "required",
                "sponsor_phone_number"=> "required",
                "sponsor_relation"=> "required",
                "next_of_kin_name"=> "required",
                "next_of_kin_phone_number"=> "required",
                "next_of_kin_relation"=> "required",
                "physical_address"=> "required",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('students')->where('id', $request->user()->id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'nrc_number' => $request->nrc_number,
                'phone_number' => $request->phone_number,
                'sponsor_name' => $request->sponsor_name,
                'sponsor_relation' => $request->sponsor_relation,
                'sponsor_phone_number' => $request->sponsor_phone_number,
                'next_of_kin_name' => $request->next_of_kin_name,
                'next_of_kin_relation' => $request->next_of_kin_relation,
                'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
                'physical_address' => $request->physical_address,
                'index_number' => $request->index_number,
            ]);

            return response()->json([
                'status' => 201,
                'message' => "You have updated your profile details",
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updatePassword(Request $req) {
        try {
            $validate = Validator::make($req->all(),[
                'current_password' => 'required',
                'new_password' => ['required', Password::min(8)
                ->letters()
                ->numbers()
                ->uncompromised(3)],
                'password_confirmation' => ['required', 'same:new_password']
            ]);

            if($validate->fails()){
                return response()->json([
                    'status' => 422,
                    'message' => 'validation error',
                    'errors' => $validate->errors()
                ], 422);
            }

            // get user password
            $curr_pasword = DB::table('students')->where('id', $req->user()->id)->value('password');

            if (!Hash::check($req->current_password, $curr_pasword)) {
                return response()->json([
                    'status' => 404,
                    'message' => 'The provided password does not match our records'
                ], 404);
            } else {
                DB::table('students')->where('id', $req->user()->id)->update([
                    'password' => Hash::make($req->new_password)
                ]);

                return response()->json([
                    'status' => 201,
                    'message' => "You have updated your password",
                ], 201);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function registerExam(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'exam_mode' => 'required',
                'year_of_study' => 'required',        
                'semester' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // check if student is registered for the chosen year and semester
            $params = [
                'student_id' => $request->user()->id,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
            ];

            $check_payments = DB::table('student_payments')
            ->where($params)
            ->first();

            $check_reg = DB::table('student_level')
            ->where($params)
            ->first();

            if (!$check_reg) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You are not registered for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester)
                ], 403);
            }

            // cehck if student has a penalty for year and semester
            $penalty_check = DB::table('student_penalties')
            ->join('student_payments', 'student_penalties.registration_id', '=', 'student_payments.id')
            ->where([
                'student_payments.year_id' => $request->year_of_study,
                'student_payments.semester_id' => $request->semester,
                'student_payments.student_id' => $request->user()->id,
                'student_penalties.validity' => 'valid'
            ])->first();

            if ($penalty_check) {
                return $this->responseMessage(403, 'You have has a penalty for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester));
            }

            // check if all inventory requirements are met
            $inv_check = DB::table('student_submitted_inventory')
            ->join('student_payments', 'student_submitted_inventory.registration_id', '=', 'student_payments.id')
            ->select('student_submitted_inventory.*', 'student_payments.id as registration_id')
            ->where($params)
            ->where('student_submitted_inventory.balance', '>', 0)
            ->get();

            if ($inv_check) {
                return $this->responseMessage(403, "You have pending submissions. Please check your inventory submissions for ".$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester) ." for details!");
            }

            if ($check_payments && $check_payments->balance > 0) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You have not cleared your balance for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester)
                ], 403);
            }

            $exam_params = [
                'program_id' => $request->user()->program_id,
                'intake_id' => $request->user()->intake_id,
                'year_id' => $request->year_of_study,
                'semester_id' => $request->semester,
                'exam_mode' => $request->exam_mode
            ];

            // check if already registered for this exam
            $check_exam_reg = DB::table('exam_registration')
            ->where($exam_params)->first();

            if ($check_exam_reg) {
                return response()->json([
                    'status'=> 403,
                    'message'=> 'You have already registered for the '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester).' exam'
                ], 403);
            } else {
                $get_courses = DB::table('courses')
                ->select('id')
                ->where([
                    'program_id' => $request->user()->program_id,
                    'year_id' => $request->year_of_study,
                    'semester_id' => $request->semester
                ])->get();

                DB::beginTransaction();

                $exam_id = DB::table('exam_registration')->insertGetId($exam_params);

                $exam_student_id = DB::table('exam_student')->insertGetId([
                    'student_id' => $request->user()->id,
                    'exam_id' => $exam_id
                ]);

                foreach ($get_courses as $value) {
                    DB::table('exam_courses')->insert([
                        'exam_student_id' => $exam_student_id,
                        'course_id' => $value->id
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => 201,
                    'message' => 'Exam registration for '.$this->getYearName($request->year_of_study).' '.$this->getSemesterName($request->semester).' completed successfully!'
                ], 201);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function examRegistrationHistory(Request $request) {
        try {
            $exam_registrations = DB::table('exam_registration')
            ->join('exam_student', 'exam_student.exam_id', '=', 'exam_registration.id')
            ->join('training_years', 'exam_registration.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'exam_registration.semester_id', '=', 'training_semesters.id')
            ->select('exam_registration.*', 'exam_student.id as exam_student_id', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('exam_student.student_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $exam_registrations,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function dashboardData(Request $request) {
        try {
            $current_level = DB::table('student_level')
            ->join('training_years', 'student_level.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'student_level.semester_id', '=', 'training_semesters.id')
            ->select('student_level.id', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('student_id', $request->user()->id)
            ->first();

            return response()->json([
                'status' => 200,
                'current_level' => $current_level,
                'program_name' => $this->getProgramName($request->user()->program_id),
                'intake_name' => $this->getIntakeName($request->user()->intake_id),
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    private function getYearName($year) {
        return DB::table('training_years')->where('id', $year)->value('year_label');
    }

    private function getProgramName($program_id) {
        return DB::table('programs')->where('id', $program_id)->value('program_name');
    }

    private function getIntakeName($intake_id) {
        return DB::table('intakes')->where('id', $intake_id)->value('label');
    }

    private function getSemesterName($semester) {
        return DB::table('training_semesters')->where('id', $semester)->value('semester_label');
    }
}
