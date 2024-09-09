<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use App\Mail\addAdmin;
use App\Mail\resetPassword;
use Carbon\Carbon;

class adminsController extends Controller
{
    public function index(Request $request) {
        try {
            $admins = DB::table('admins')
            ->join('offices', 'admins.office_id', '=', 'offices.id')
            ->where('admins.id', '!=', $request->user()->id)
            ->select('admins.id', 'name', 'email', 'phone_number', 'date_of_birth', 'nrc_number', 'gender', 'joining_date', 'address', 'offices.office_name')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $admins
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name"=> "required",
                "email" => "required|email|unique:admins,email",
                'phone_number' => 'required|unique:admins,phone_number',
                'gender' => 'required',
                'adminRole' => 'required',
            ], [
                'adminRole.required' => 'Please choose one or more roles for this admin!'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // if (in_array('master', $request->adminRole) && sizeof($request->adminRole) > 1) {
            //     return response()->json([
            //         'status' => 403,
            //         'message' => 'Master admin cannot take other roles'
            //     ], 403);
            // }

            if (in_array('lecturer', $request->adminRole) && in_array('master', $request->adminRole)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Lecturer cannot have master admin role'
                ], 403);
            }

            $password = Str::random(8);
            // $password = "thepassword";

            DB::beginTransaction();

            $admin_id = DB::table('admins')->insertGetId([
                'office_id' => $request->office,
                'name'=> $request->name,
                'email'=> $request->email,
                'gender'=> $request->gender,
                'date_of_birth'=> $request->date_of_birth,
                'nrc_number' => $request->nrc_number,
                'joining_date' => $request->joining_date,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($password)
            ]);


            foreach ($request->adminRole as $role) {
                // get the role ids
                $role_id = DB::table('roles')->where('role', $role)->value('id');
                DB::table('admin_roles')->insert([
                    'role_id' => $role_id,
                    'admin_id' => $admin_id
                ]);
            }

            if (sizeof($request->chosenCourses) > 0) {
                foreach ($request->chosenCourses as $key => $value) {
                    DB::table('lecturer_course')->insert([
                        'admin_id' => $admin_id,
                        'program_id' => $value['program_id'],
                        'course_id' => $value['id']
                    ]);
                }
            }

            Mail::to($request->email)->send(new addAdmin($request->name, $request->email, $password, 'https://admin.pomicollege.com'));

            DB::commit();

            return response()->json([
                'status'=> 200,
                'message'=> 'Admin added successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updateContactDetails(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                "name"=> "required",
                "date_of_birth"=> "required",
                "joining_date"=> "required",
                "nrc_number"=> "required",
                "address"=> "required",
                "email" => "required|unique:admins,email,".$request->user()->id,
                'phone_number' => 'required|unique:admins,phone_number,'.$request->user()->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('admins')->where('id', $request->user()->id)->update([
                'name' => $request->name,
                'email' => $request->phone_number,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'joining_date' => $request->joining_date,
                'nrc_number' => $request->nrc_number,
                'address' => $request->address,
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
            $curr_pasword = DB::table('admins')->where('id', $req->user()->id)->value('password');

            if (!Hash::check($req->current_password, $curr_pasword)) {
                return response()->json([
                    'status' => 404,
                    'message' => 'The provided password does not match our records'
                ], 404);
            } else {
                DB::table('admins')->where('id', $req->user()->id)->update([
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

    public function sendResetLink(Request $request) {
        date_default_timezone_set("Africa/Lusaka");
        try {
            $validate = Validator::make($request->all(),[
                'email' => 'required|email|exists:admins,email',
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
    
            $url = 'https://admin.pomicollege.com/?#/update-your-password/'.$token;
    
            DB::beginTransaction();

            // check if there are unused tokens for this email
            $check = DB::table('admin_password_reset_tokens')
            ->where(['email' => $request->email])
            ->first();

            if ($check) {
                DB::table('admin_password_reset_tokens')
                ->where(['email' => $request->email])
                ->delete();
            }
    
            DB::table('admin_password_reset_tokens')->insert([
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

    public function resetPasswordAdmin(Request $req) {
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

        $check = DB::table('admin_password_reset_tokens')
        ->where(['email' => $req->email, 'token' => $req->token])
        ->first();

        if($now > $check->expiry_date) {
            return response()->json([
                'status' => 403,
                'message' => 'The link you have used has expired. Please restart the forgot password process!',
            ], 403);
        } else {
            DB::beginTransaction();

            DB::table('admins')->where('email', $req->email)->update([
                'password' => Hash::make($req->password)
            ]);
            DB::table('admin_password_reset_tokens')
            ->where(['email' => $req->email, 'token' => $req->token])
            ->delete();
           
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'You have successfully reset your password. Login to your account now!',
            ], 200);
        }
    }

    public function getAdminCourses(Request $request) {
        try {
            $courses = DB::table('lecturer_course')
            ->join('courses', 'lecturer_course.course_id', '=', 'courses.id')
            ->select('lecturer_course.*', 'courses.course_name')
            ->where('admin_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $courses
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function chooseCourse(Request $request) {
        try {

            $course_details = DB::table('courses')->where('id', $request->course_id)->first();

            $params = [
                'course_id' => $request->course_id,
                'program_id' => $course_details->program_id,
                'admin_id' => $request->user()->id
            ];

            $check = DB::table('lecturer_course')
            ->where($params)
            ->first();

            if ($check) {
                return response()->json([
                    'status' => 403,
                    'message' => $course_details->course_name." has already been chosen"
                ], 403);
            } else {
                # add course to admin
                DB::table('lecturer_course')
                ->insert($params);

                return response()->json([
                    'status' => 201,
                    'message' => $course_details->course_name." has been selected successfully!"
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

    public function removeCourse(Request $request) {
        try {
            DB::table('lecturer_course')
            ->where('id', $request->id)
            ->delete();

            return response()->json([
                'status' => 201,
                'message' => "Course has been selected successfully!"
            ], 201);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
