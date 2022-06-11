<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use URL;
use Validator;

class Guests extends Controller
{
    public function get_all_guests(Request $request)
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

            $guests = DB::table('users')
                ->where('type', 9)
                ->where('is_deleted', 0)
                ->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'All Guests',
                'data' => $guests,
            ]);

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function guest_detail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
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

            $guest = DB::table('users')
                ->where('id', $request->user_id)
                ->where('is_deleted', 0)
                ->first();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Guest user details',
                'data' => $guest,
            ]);

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function save_guest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
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

            $checkEmail = DB::table('users')
                ->where('email', $request->email)
                ->where('is_deleted', 0)
                ->first();

            if ($checkEmail != null) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Email Already Exists',
                ]);
            }

            if ($request->file('image_profile') != null) {
                $unix_timestamp = now()->timestamp;
                $image_profile = 'profile_' . $unix_timestamp . '.' . $request->image_profile->extension();
                $request->image_profile->move(public_path('uploads/xrcentral-images/users'), $image_profile);
                $profile_image = URL::to('/') . '/uploads/xrcentral-images/users/' . $image_profile;
            }

            $random = strtoupper(Str::random(10));

            $last_id = DB::table('users')
                ->insertGetId([
                    'unique_id' => $random,
                    'salutation' => $request->salutation,
                    'name' => $request->name,
                    'email' => $request->email,
                    'company' => $request->company,
                    'occupation' => $request->occupation,
                    'image_profile' => (empty($profile_image)) ? '' : $profile_image,
                    'notes' => $request->notes,
                    'type' => 9,
                    'updated_by' => $request->updated_by,
                ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Guest Created Successfully',
                'guest_id' => $last_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function update_guest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'user_id' => 'required',
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

            if ($request->file('image_profile') != null) {
                $unix_timestamp = now()->timestamp;
                $image_profile = 'profile_' . $unix_timestamp . '.' . $request->image_profile->extension();
                $request->image_profile->move(public_path('uploads/xrcentral-images/users'), $image_profile);
                $profile_image = URL::to('/') . '/uploads/xrcentral-images/users/' . $image_profile;
            }


            $last_id = DB::table('users')
                ->where('id',$request->user_id)
                ->update([
                    'salutation' => $request->salutation,
                    'name' => $request->name,
                    'email' => $request->email,
                    'company' => $request->company,
                    'occupation' => $request->occupation,
                    'image_profile' => (empty($profile_image)) ? '' : $profile_image,
                    'notes' => $request->notes,
                    'type' => 9,
                ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Guest Update Successfully',
                'guest_id' => $last_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function delete_guest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
        }
        try {

            DB::table('users')
                ->where('id', $request->id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Guest Deleted successfully',
            ];

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
