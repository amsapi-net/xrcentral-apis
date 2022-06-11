<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class MainMenu extends Controller
{
    public function main_menu_data(Request $request)
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

            $active_invitations = DB::table('meeting')
                ->whereDate('meeting_date', '>=', date('Y-m-d'))
                ->where('is_deleted', 0)
                ->count();

            $total_guests = DB::table('users')
                ->where('type', 9)
                ->where('is_deleted', 0)
                ->count();

            $total_template = DB::table('template')
                ->where('is_deleted', 0)
                ->count();

            $media_assets = DB::table('media_repository')
                ->join('media_file','media_file.repository_id','=','media_repository.id')
                ->where('media_repository.is_deleted', 0)
                ->count();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Main Menu Data',
                'active_invitations' => $active_invitations,
                'total_guests' => $total_guests,
                'total_template' => $total_template,
                'media_assets' => $media_assets,
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
