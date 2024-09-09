<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Admin;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validateUser = Validator::make($request->all(),
            [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => 422,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 422);
            }

            $admin = Admin::where('email', $request->email)->first();
    
            if(!$admin || !Hash::check($request->password, $admin->password)){
                return response()->json([
                    'status' => 404,
                    'message' => 'Wrong credentials. Please enter a correct username and password.',
                ], 404);
            } else {
                $roles = DB::table('admin_roles')
                ->join('roles', 'admin_roles.role_id', '=', 'roles.id')
                ->select('roles.role')
                ->where('admin_roles.admin_id', $admin->id)
                ->pluck('role');

                return response()->json([
                    'status' => 200,
                    'admin' => $admin,
                    'roles' => $roles,
                    'message' => 'logged in successfully',
                    'token' => $admin->createToken('mobile', ['role:admin'])->plainTextToken
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
        // if (!$admin || !Hash::check($request->password, $admin->password)) {
        //     throw ValidationException::withMessages([
        //         'email' => ['The provided credentials are incorrect.'],
        //     ]);
        // }

    }

    public function logout(Request $req) {

        // $req->user()->tokens()->delete();

        $req->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'You have been logged out successfully'
        ], 200);
    }
}
