<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Hash;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Str;
use Mail;

class Users extends Controller
{
    protected $maxAttempts = 3; // Default is 5
    protected $decayMinutes = 2; // Default is 1

    private $apiToken;
    public function __construct()
    {
        // Unique Token
        $this->apiToken = uniqid(base64_encode(str_random(60)));
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }

        try {

            $checkBlockedUser = DB::table('users')->where('email', $request->email)->first();

            if (!isset($checkBlockedUser)) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'errors' => 'This email is not found in our records',
                ]);
            }

            if ($checkBlockedUser->status == 0) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'errors' => 'Your Account is blocked, please contact with Admin.',
                ]);
            }

            if ($request->email != $checkBlockedUser->email || !Hash::check($request->password, $checkBlockedUser->password)) {

                if ($request->login_attempt == 3) {
                    DB::table('users')->where('email', $request->email)->update([
                        'status' => 0
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => 204,
                        'errors' => 'Your Account is blocked please contact with admin to reactivate',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'errors' => 'Your Password is incorrect Please try again',
                ]);
            } else {
                $postArray = ['api_token' => $this->apiToken];
                $login = DB::table('users')
                    ->where('email', $request->email)
                    ->update($postArray);

                if ($login) {
                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'errors' => 'Logged in Successfully',
                        'access_token' => $this->apiToken,
                        'data' => $checkBlockedUser,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_all_users(Request $request)
    {
        try {
            $users = DB::table('users')
                ->where('is_deleted', 0)
                ->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'errors' => 'All Users',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_user(Request $request)
    {
        try {

            $user = DB::table('users')->where('id', $request->user_id)
                ->where('is_deleted', 0)
                ->first();

            return response()->json([
                'success' => true,
                'status' => 200,
                'errors' => 'User Details',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function search_user(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            $user = DB::table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            // if (empty($request->q)) {
            //     $user = DB::table('users')->where('id', $request->user_id)
            //         ->where('is_deleted', 0)
            //         ->first();
            // } else {
            $user = DB::table('users')
                ->where('name', 'like', '%' . $request->q . '%')
                ->where('is_deleted', 0)
                ->get();
            // }

            return response()->json([
                'success' => true,
                'status' => 200,
                'errors' => 'User Details',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function change_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:6',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        try {
            $token = $request->header('Authorization');
            $user = DB::table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $user = DB::table('users')->where('id', $request->user_id)->update([
                    'password' => bcrypt($request->password)
                ]);

            if ($user) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Password Changed Successfully',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function upload_profile_pic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '401',
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        try {

            $token = $request->header('Authorization');
            $user = DB::table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $file = $request->file;
            $u_id = $request->user_id;

            $unix_timestamp = now()->timestamp;
            $image_name = 'uploads/images/profile/profile_' . $unix_timestamp . '.' . '.png';

            // $image_name = 'uploads/profile-pics/' . time() . '.png';;
            // $decoded_file = base64_decode($file);
            file_put_contents($image_name, $file);

            $img_full_url = URL::to('/') . '/' . $image_name;

            DB::table('users')->where('id', $u_id)->update([
                'image_profile' => $img_full_url,
            ]);

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'image_url' => $img_full_url,
                'message' => 'User Profile Picture Updated Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function check_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }

        try {
            $user = DB::table('users')->where('email', $request->email)
                ->where('is_deleted', 0)
                ->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'errors' => 'User Details',
                    'data' => $user,
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User Not Found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function update_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:6',
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        try {

            $user = DB::table('users')->where('email', $request->email)->update([
                    'password' => bcrypt($request->password)
                ]);

            if ($user) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Password Changed Successfully',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function check_forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }

        try {
            $user = DB::table('users')->where('email', $request->email)
                ->where('is_deleted', 0)
                ->first();

            if ($user) {
                $random = Str::random(40);

                DB::table('users')->where('email', $request->email)->update([
                        'api_token' => $random
                    ]);

                $data = array(
                    'token' => $random,
                );

                $to_name = $user->name;
                $to_email = $user->email;

                Mail::send('emails.forgot-password', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email, $to_name)
                        ->subject('Reset Password');
                    $message->from('info@iwc.com', 'IWC XR CENTRAL');
                });

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'We have sent email Please your email.',
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User Not Found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        try {
            $token = $request->input('token');

            $user = DB::table('users')->where('api_token', $token)
                ->where('is_deleted', 0)
                ->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'User Details',
                    'data' => $user
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User Not Found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function update_forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        try {
            $token = $request->token;

            $user = DB::table('users')->where('api_token', $token)
                ->where('is_deleted', 0)
                ->first();

            if ($user) {

                DB::table('users')->where('api_token', $token)->update([
                        'password' => bcrypt($request->password)
                    ]);

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Password Updated Successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User Not Found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }
}
