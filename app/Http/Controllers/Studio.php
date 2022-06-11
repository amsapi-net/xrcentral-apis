<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Validator;

class Studio extends Controller
{
    public function index(Request $request)
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

            $studio = DB::table('studio')
                ->orWhere('id', $request->id)
                ->orWhere('unique_id', $request->unique_id)
                ->where('is_deleted', 0)
                ->first();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'Studio Details',
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

    public function studios(Request $request)
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

            $studio = DB::table('studio')
            ->where('is_deleted', 0)
            ->get();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Studios',
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

    public function add_studio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'host_name' => 'required',
            'description' => 'required',
            'local_media_path' => 'required',
            'local_ip_address' => 'required',
            'obs_websocket_port' => 'required',
            'obs_websocket_password' => 'required',
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
            $unique_id = strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 12));

            DB::table('studio')->insert([
                    'title' => $request->title,
                    'host_name' => $request->host_name,
                    'description' => $request->description,
                    'local_media_path' => $request->local_media_path,
                    'local_ip_address' => $request->local_ip_address,
                    'obs_websocket_port' => $request->obs_websocket_port,
                    'obs_websocket_password' => $request->obs_websocket_password,
                    'unique_id' => $unique_id,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Studio Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function update_studio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'host_name' => 'required',
            'description' => 'required',
            'local_media_path' => 'required',
            'local_ip_address' => 'required',
            'obs_websocket_port' => 'required',
            'obs_websocket_password' => 'required',
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

            DB::table('studio')->where('id', $request->studio_id)->update([
                    'title' => $request->title,
                    'host_name' => $request->host_name,
                    'description' => $request->description,
                    'local_media_path' => $request->local_media_path,
                    'local_ip_address' => $request->local_ip_address,
                    'obs_websocket_port' => $request->obs_websocket_port,
                    'obs_websocket_password' => $request->obs_websocket_password,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => '200',
                'message' => 'Studio Update Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }
}
