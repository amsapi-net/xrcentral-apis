<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ZoomJWT;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Validator;

class ZoomMeeting extends Controller
{
    public function __construct()
    {
        date_default_timezone_set("Europe/Zurich");
    }

    use ZoomJWT;

    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;

    public function meeting_list(Request $request)
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
            $meetings = DB::table('meeting')
                ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'meeting.created_by', 'language.title as language')
                ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                ->join('language', 'language.id', '=', 'meeting.language_id')
                ->where('meeting.is_deleted', 0)
                ->whereDate('meeting.meeting_date', '>=', date('Y-m-d'))
                ->orderBy('meeting.meeting_date', 'asc')
                ->orderBy('meeting.start_time', 'asc')
                ->get();

            foreach ($meetings as $key => $meeting) {
                $guests = DB::table('accepted_invitations')
                    ->select('accepted_invitations.meeting_id', 'accepted_invitations.user_id', 'accepted_invitations.status', 'accepted_invitations.token', 'users.name')
                    ->join('users', 'users.id', '=', 'accepted_invitations.user_id')
                    ->where([
                        ['accepted_invitations.meeting_id', $meeting->id],
                        ['users.type', 9],
                    ])
                    ->get();

                $meetings[$key]->guests = $guests;
            }

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Meeting List',
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function filter_meeting_by_author(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'author_id' => 'required',
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
            $meetings = DB::table('meeting')
                ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'meeting.created_by', 'language.title as language')
                ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                ->join('language', 'language.id', '=', 'meeting.language_id')
                ->where('meeting.is_deleted', 0)
                ->where('author.id', $request->author_id)
                ->orderBy('meeting.meeting_date', 'asc')
                ->orderBy('meeting.start_time', 'asc')
                ->get();

            foreach ($meetings as $key => $meeting) {
                $guests = DB::table('accepted_invitations')
                    ->select('accepted_invitations.meeting_id', 'accepted_invitations.user_id', 'accepted_invitations.status', 'accepted_invitations.token', 'users.name')
                    ->join('users', 'users.id', '=', 'accepted_invitations.user_id')
                    ->where([
                        ['accepted_invitations.meeting_id', $meeting->id],
                        ['users.type', 9],
                    ])
                    ->get();

                $meetings[$key]->guests = $guests;
            }

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Meeting List',
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function filter_meeting_by_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
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

            if ($request->status == 'upcoming') {
                $meetings = DB::table('meeting')
                    ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'meeting.created_by', 'language.title as language')
                    ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                    ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                    ->join('language', 'language.id', '=', 'meeting.language_id')
                    ->where('meeting.is_deleted', 0)
                    ->whereDate('meeting.meeting_date', '>=', date('Y-m-d'))
                    ->orderBy('meeting.meeting_date', 'asc')
                    ->orderBy('meeting.start_time', 'asc')
                    ->get();
            } else {
                $meetings = DB::table('meeting')
                    ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'meeting.created_by', 'language.title as language')
                    ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                    ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                    ->join('language', 'language.id', '=', 'meeting.language_id')
                    ->where('meeting.is_deleted', 0)
                    ->whereDate('meeting.meeting_date', '<', date('Y-m-d'))
                    ->get();
            }

            foreach ($meetings as $key => $meeting) {
                $guests = DB::table('accepted_invitations')
                    ->select('accepted_invitations.meeting_id', 'accepted_invitations.user_id', 'accepted_invitations.status', 'accepted_invitations.token', 'users.name')
                    ->join('users', 'users.id', '=', 'accepted_invitations.user_id')
                    ->where([
                        ['accepted_invitations.meeting_id', $meeting->id],
                        ['users.type', 9],
                    ])
                    ->get();

                $meetings[$key]->guests = $guests;
            }

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Meeting List',
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function search_invitations(Request $request)
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
            $keyword = $request->search;

            if (!empty($keyword)) {
                $meetings = DB::table('meeting')
                    ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'meeting.created_by', 'language.title as language')
                    ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                    ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                    ->join('language', 'language.id', '=', 'meeting.language_id')
                    ->where('meeting.is_deleted', 0)
                    ->orderBy('meeting.meeting_date', 'asc')
                    ->orderBy('meeting.start_time', 'asc')
                    ->where(function ($query) use ($keyword) {
                        $query->where('meeting.meeting_id', 'like', '%' . $keyword . '%')
                            ->orWhere('meeting.title', 'like', '%' . $keyword . '%')
                            ->orWhere('meeting.meeting_date', 'like', '%' . $keyword . '%');
                    })
                    ->get();
            } else {
                $meetings = DB::table('meeting')
                    ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'language.title as language')
                    ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                    ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                    ->join('language', 'language.id', '=', 'meeting.language_id')
                    ->where('meeting.is_deleted', 0)
                    ->whereDate('meeting.meeting_date', '>=', date('Y-m-d'))
                    ->orderBy('meeting.meeting_date', 'asc')
                    ->orderBy('meeting.start_time', 'asc')
                    ->get();
            }

            foreach ($meetings as $key => $meeting) {
                $guests = DB::table('accepted_invitations')
                    ->select('accepted_invitations.meeting_id', 'accepted_invitations.user_id', 'accepted_invitations.status', 'accepted_invitations.token', 'users.name')
                    ->join('users', 'users.id', '=', 'accepted_invitations.user_id')
                    ->where([
                        ['accepted_invitations.meeting_id', $meeting->id],
                        ['users.type', 9],
                    ])
                    ->get();

                $meetings[$key]->guests = $guests;
            }

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'All Meeting List',
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function meeting_details(Request $request)
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
            $meeting = DB::table('meeting')
                ->select('meeting.id', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'meeting.title', 'meeting.meeting_id', 'meeting.password', 'meeting.start_url', 'meeting.join_url', 'host.name as host_name', 'host.id as host_id', 'host.occupation as host_occupation', 'meeting.created_by', 'author.name as author_name', 'author.id as author_id', 'language.id as lang_id', 'language.title as language')
                ->leftJoin('users as host', 'host.id', '=', 'meeting.host_user_id')
                ->leftJoin('users as author', 'author.id', '=', 'meeting.created_by')
                ->join('language', 'language.id', '=', 'meeting.language_id')
                ->where('meeting.id', $request->id)
                ->where('meeting.is_deleted', 0)
                ->first();

            if ($meeting) {

                $guests = DB::table('accepted_invitations')
                    ->select('accepted_invitations.id', 'accepted_invitations.meeting_id', 'accepted_invitations.user_id as accepted_user_id', 'accepted_invitations.status', 'accepted_invitations.token', 'users.name', 'users.email', 'users.id as user_id')
                    ->join('users', 'users.id', '=', 'accepted_invitations.user_id')
                    ->where([
                        ['accepted_invitations.meeting_id', $meeting->id],
                        ['users.type', 9],
                        ['accepted_invitations.is_deleted', 0],
                    ])
                    ->get();

                $meeting->guests = $guests;

                return response()->json([
                    'success' => 'true',
                    'status' => '200',
                    'message' => 'Meeting Details',
                    'data' => $meeting,
                ]);
            }
            return response()->json([
                'success' => 'false',
                'status' => '401',
                'message' => 'Meeting Not Found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function templates(Request $request)
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

            $templates = DB::table('template')
                ->where('is_deleted', 0)
                ->get();
            $languages = DB::table('language')
                ->where('is_deleted', 0)
                ->get();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'templates & languages',
                'templates' => $templates,
                'languages' => $languages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 'false',
                'status' => '500',
                'error' => $e,
            ]);
        }
    }

    public function create_invitation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'meeting_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'users' => 'required|array',
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

            $id = DB::table('meeting')->insertGetId([
                    'title' => $request->title,
                    'created_by' => $request->created_by,
                    'meeting_date' => $request->meeting_date,
                    'language_id' => $request->language_id,
                    'start_time' => date("H:i:s", strtotime($request->start_time)),
                    'end_time' => date("H:i:s", strtotime($request->end_time)),
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            foreach ($request->users as $key => $user) {

                $random = Str::random(40);

                DB::table('accepted_invitations')->insert([
                        'meeting_id' => $id,
                        'user_id' => $user,
                        'token' => $random,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $email_users = DB::table('users')
                ->select('meeting.title', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'users.name', 'users.email', 'accepted_invitations.token')
                ->join('accepted_invitations', 'accepted_invitations.user_id', '=', 'users.id')
                ->join('meeting', 'meeting.id', '=', 'accepted_invitations.meeting_id')
                ->where([
                    ['users.type', 9],
                    ['accepted_invitations.meeting_id', $id],
                    ['meeting.is_deleted', 0],
                ])
                ->whereIn('users.id', $request->users)
                ->get();

            foreach ($email_users as $key => $e_user) {

                $data = array(
                    'name' => $e_user->name,
                    'title' => $e_user->title,
                    'meeting_date' => date('j F Y', strtotime($e_user->meeting_date)),
                    'title_meeting_date' => date('F jS', strtotime($e_user->meeting_date)),
                    'start_time' => date("h:i A", strtotime($e_user->start_time)),
                    'end_time' => date("h:i A", strtotime($e_user->end_time)),
                    'token' => $e_user->token,
                );

                $to_name = $e_user->name;
                $to_email = $e_user->email;

                Mail::send('emails.meeting-invitation-template', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email, $to_name)
                        ->subject('Meeting invitation');
                    $message->from('info@iwc.com', 'IWC XR CENTRAL');
                });
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Invitation send successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function update_invitation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'meeting_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'id' => 'required',
            'status' => 'required|array',
            'users' => 'required|array',
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

            DB::table('meeting')->where('id', $request->id)->update([
                    'title' => $request->title,
                    'meeting_date' => $request->meeting_date,
                    'language_id' => $request->language_id,
                    'start_time' => date("H:i:s", strtotime($request->start_time)),
                    'end_time' => date("H:i:s", strtotime($request->end_time)),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            DB::table('accepted_invitations')->where('meeting_id', $request->id)->delete();

            foreach (array_combine(array_unique($request->users), $request->status) as $user_id => $status) {

                $random = Str::random(40);

                DB::table('accepted_invitations')->insert([
                        'meeting_id' => $request->id,
                        'user_id' => $user_id,
                        'token' => $random,
                        'status' => $status,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $email_users = DB::table('users')
                ->select('meeting.title', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'users.name', 'users.email', 'accepted_invitations.token')
                ->join('accepted_invitations', 'accepted_invitations.user_id', '=', 'users.id')
                ->join('meeting', 'meeting.id', '=', 'accepted_invitations.meeting_id')
                ->where([
                    ['users.type', 9],
                    ['accepted_invitations.meeting_id', $request->id],
                    ['meeting.is_deleted', 0],
                ])
                ->whereIn('users.id', $request->users)
                ->get();

            foreach ($email_users as $key => $e_user) {

                $data = array(
                    'name' => $e_user->name,
                    'title' => $e_user->title,
                    'meeting_date' => date('j F Y', strtotime($e_user->meeting_date)),
                    'title_meeting_date' => date('F jS', strtotime($e_user->meeting_date)),
                    'start_time' => date("h:i A", strtotime($e_user->start_time)),
                    'end_time' => date("h:i A", strtotime($e_user->end_time)),
                    'token' => $e_user->token,
                );

                $to_name = $e_user->name;
                $to_email = $e_user->email;

                Mail::send('emails.meeting-invitation-template', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email, $to_name)
                        ->subject('Meeting invitation');
                    $message->from('info@iwc.com', 'IWC XR CENTRAL');
                });
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Invitation send successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function user_accept_invitation(Request $request)
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
            
            $checkToken = DB::table('accepted_invitations')
                ->where('token', $request->token)
                ->first();

            if ($checkToken != null) {
                DB::table('accepted_invitations')
                    ->where('token', $request->token)
                    ->update([
                        'status' => 1,
                    ]);

                return view('emails.accepted');
            } else {
                echo '<script>alert("Sorry! This token is not found in or records ")</script>';
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function user_reject_invitation(Request $request)
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
            
            $checkToken = DB::table('accepted_invitations')
                ->where('token', $request->token)
                ->first();

            if ($checkToken != null) {
                DB::table('accepted_invitations')
                    ->where('token', $request->token)
                    ->update([
                        'status' => 2,
                    ]);

                return view('emails.reject');
            } else {
                echo '<script>alert("Sorry! This token is not found in or records ")</script>';
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function create_meeting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'meeting_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'created_by' => 'required',
            'users' => 'required|array',
            'meeting_id' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
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
            $data = $validator->validated();

            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);

            $duration = $end_time - $start_time;

            $pass = rand(10000, 1000000);

            $path = 'users/me/meetings';
            $response = $this->zoomPost($path, [
                'topic' => $data['title'],
                'type' => self::MEETING_TYPE_SCHEDULE,
                // 'start_time' => $this->toZoomTimeFormat($data['start_time']),
                'start_time' => $request->time_zone,
                'duration' => $duration / 60,
                "password" => $pass,
                "timezone" => 'Europe/Zurich',
                'settings' => [
                    'host_video' => false,
                    'participant_video' => false,
                    'waiting_room' => true,
                ],
            ]);

            $result = json_decode($response->body(), true);

            DB::table('meeting')
                ->where('id', $data['meeting_id'])
                ->update([
                    'Type' => 0,
                    'meeting_id' => $result['id'],
                    'meeting_type' => self::MEETING_TYPE_SCHEDULE,
                    'status' => 0,
                    'start_url' => $result['start_url'],
                    'join_url' => $result['join_url'],
                    'password' => $result['password'],
                    'settings' => $result['settings'],
                    'host_user_id' => ($request->host_id == null) ? '' : $request->host_id,
                    'created_by' => $data['created_by'],
                ]);

            $email_users = DB::table('users')
                ->select('meeting.title', 'meeting.meeting_date', 'meeting.start_time', 'meeting.end_time', 'users.name', 'users.email', 'accepted_invitations.token')
                ->join('accepted_invitations', 'accepted_invitations.user_id', '=', 'users.id')
                ->join('meeting', 'meeting.id', '=', 'accepted_invitations.meeting_id')
                ->where([
                    ['users.type', 9],
                    ['accepted_invitations.meeting_id', $request->meeting_id],
                ])
                ->whereIn('users.id', $request->users)
                ->get();

            foreach ($email_users as $key => $e_user) {
                $data = array(
                    'name' => $e_user->name,
                    'title' => $e_user->title,
                    'meeting_date' => date('j F Y', strtotime($e_user->meeting_date)),
                    'title_meeting_date' => date('F jS', strtotime($e_user->meeting_date)),
                    'start_time' => date("h:i A", strtotime($e_user->start_time)),
                    'end_time' => date("h:i A", strtotime($e_user->end_time)),
                    'token' => $e_user->token,
                    'meeting_id' => $result['id'],
                    'meeting_link' => $result['join_url'],
                    'password' => $result['password'],
                );

                $to_name = $e_user->name;
                $to_email = $e_user->email;

                Mail::send('emails.meeting-link', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email, $to_name)
                        ->subject('Meeting invitation');
                    $message->from('info@iwc.com', 'IWC XR CENTRAL');
                });
            }

            return [
                'success' => $response->status() === 201,
                'status' => 200,
                'message' => 'Zoom Meeting Created Successfully',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function test()
    {
        return view('emails.forgot-password');
    }

    public function get_meeting_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
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
            $path = 'meetings/' . $request->meeting_id;
            $response = $this->zoomGet($path);

            $data = json_decode($response->body(), true);
            if ($response->ok()) {
                $data['start_at'] = $this->toUnixTimeStamp($data['start_time'], $data['timezone']);
            }

            return [
                'success' => $response->ok(),
                'status' => 200,
                'message' => 'Zoom Meeting Details',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_zoom_meeting_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
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

            $path = 'meetings/' . $request->meeting_id . '/status';
            $response = $this->zoomPut($path, [
                'action' => $request->status,
            ]);

            return [
                'success' => $response->status() === 204,
                'status' => 200,
                'message' => 'Zoom Meeting Status Updated',
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_meeting_host(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'nullable|integer',
            'host_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
        }
        try {

            DB::table('meeting')
                ->where('id', $request->meeting_id)
                ->update([
                    'host_user_id' => ($request->host_id) ? $request->host_id : 'Null',
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Host Updated successfully',
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_invitations(Request $request)
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

            DB::table('meeting')
                ->where('id', $request->id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Invitation Deleted successfully',
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
