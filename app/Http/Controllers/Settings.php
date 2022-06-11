<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;

class Settings extends Controller
{
    public function add_presenter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'required',
            'gender' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            $checkUser = DB::table('users')
                ->where('email', $request->email)
                ->first();

            if ($checkUser) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'errors' => 'This user email already exits',
                ]);
            }

            $unique_id = strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 12));

            DB::table('users')->insert([
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'type' => 2,
                    'unique_id' => $unique_id,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Presenter Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function update_presenter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'gender' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            DB::table('users')->where('id', $request->user_id)->update([
                    'email' => $request->email,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Presenter Updated Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function add_operator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'required',
            'gender' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            $checkUser = DB::table('users')
                ->where('email', $request->email)
                ->first();

            if ($checkUser) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'errors' => 'This user email already exits',
                ]);
            }

            $unique_id = strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 12));

            DB::table('users')->insert([
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'type' => 1,
                    'unique_id' => $unique_id,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Operator Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function update_operator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'gender' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            DB::table('users')->where('id', $request->user_id)->update([
                    'email' => $request->email,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Operator Updated Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function add_settings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'media_path' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            DB::table('settings')->insert([
                    'title' => $request->title,
                    'description' => $request->description,
                    'media_path' => $request->media_path,
                    'status' => (isset($request->status) ? $request->status : 0),
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Settings Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function update_settings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'media_path' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'false',
                'status' => '422',
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

            DB::table('settings')->where('id',$request->settings_id)->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'media_path' => $request->media_path,
                    'status' => (isset($request->status) ? $request->status : 0),
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Settings Updated Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function get_settings(Request $request)
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

            $settings = DB::table('settings')
                ->where('id', $request->id)
                ->where('is_deleted', 0)
                ->first();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'Settings Details',
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function all_settings(Request $request)
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

            $studio = DB::table('settings')
            ->where('is_deleted', 0)
            ->get();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Settings',
                'data' => $studio,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function delete_presenter(Request $request)
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
                'message' => 'Presenter Deleted successfully',
            ];

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_operator(Request $request)
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
                'message' => 'Operator Deleted successfully',
            ];

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_studio(Request $request)
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

            DB::table('studio')
                ->where('id', $request->id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Studio Deleted successfully',
            ];

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_settings(Request $request)
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

            DB::table('settings')
                ->where('id', $request->id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Settings Deleted successfully',
            ];

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
