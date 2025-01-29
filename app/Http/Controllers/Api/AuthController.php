<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect'
            ], 401);
        }
        $token = $user->createToken($user->name . 'Auth-Token')->plainTextToken;
        return response()->json([
            'message' => 'Login Successful',
            'user' => new UserResource($user),
            'token_type' => 'Bearer',
            'access_token' => $token,
        ], 200);
    }

    public function registerRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);
        try {
            // check if user exist
            $current_user = User::where('email', $request->email)->first();

            if ($current_user) {
                if ($current_user->email_verified_at) {
                    return response()->json([
                        'message' => 'You already registered.',
                    ], 400);
                } else {
                    return response()->json([
                        'message' => 'Verify code sent to you by email.',
                        'verify_email_code' => $current_user->verify_email_code
                    ], 200);
                }
            } else {
                $verify_email_code = Str::random(20);
                $user = User::create([
                    'email' => $request->email,
                    'verify_email_code' => $verify_email_code,
                ]);

                if ($user) {
                    return response()->json([
                        'message' => 'Verify code sent to you by email.',
                        'verify_email_code' => $verify_email_code
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Something went wrong! while registeration.'
                    ], 500);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong! while registeration.'
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'verify_email_code' => 'required|string',
            'password' => 'required|string|min:8|max:255',
        ]);
        try {
            $user = User::where('verify_email_code', $request->verify_email_code)->first();


            if ($user) {
                // update user
                $user->name = $request->name;
                $user->family = $request->family;
                $user->email_verified_at = Carbon::now();
                $user->password = Hash::make($request->password);
                $user->save();

                $token = $user->createToken($user->name . 'Auth-Token')->plainTextToken;

                return response()->json([
                    'message' => 'Registeration Successful',
                    'user' => new UserResource($user),
                    'token_type' => 'Bearer',
                    'access_token' => $token
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Something went wrong! while registeration.'
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong! while registeration.'
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        return response()->json([
            'message' => 'User informatoin',
            'user' => new UserResource($request->user()),
        ], 200);
    }


    public function forgotPasswordRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);
        try {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $verify_forgot_password_code = Str::random(20);
                // update user
                $user->verify_forgot_password_code = $verify_forgot_password_code;
                $user->save();

                return response()->json([
                    'message' => 'Verify code sent to you by email.',
                    'verify_forgot_password_code' => $verify_forgot_password_code
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Something went wrong!'
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'verify_forgot_password_code' => 'required|string',
            'password' => 'required|string|min:8|max:255',
        ]);
        try {
            $user = User::where('verify_forgot_password_code', $request->verify_forgot_password_code)->first();

            if ($user) {
                // update user
                $user->password = Hash::make($request->password);
                $user->save();

                $token = $user->createToken($user->name . 'Auth-Token')->plainTextToken;

                return response()->json([
                    'message' => 'Password changed successfully.',
                    'user' => new UserResource($user),
                    'token_type' => 'Bearer',
                    'access_token' => $token
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Something went wrong!'
                ], 500);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    public function logout(Request $request)
    {

        $user = User::where('id', $request->user()->id)->first();
        if ($user) {
            $user->tokens()->delete();
            return response()->json([
                'message' => 'Logged out successfully'
            ], 200);
        } else {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }
}
