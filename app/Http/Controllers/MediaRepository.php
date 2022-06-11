<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Image;
use Laravel\Ui\Presets\React;
use Validator;
use URL;

class MediaRepository extends Controller
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
            $media_categories = DB::table('media_category')
                ->where('is_deleted', 0)
                ->get();

            foreach ($media_categories as $key => $category) {

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $category->id)
                    ->where('is_deleted', 0)
                    ->orderBy('date_modified', 'desc')
                    ->get();

                $media_categories[$key]->media_repositories = $media_repositories;

                foreach ($media_repositories as $key1 => $media_repo) {

                    // $media_files = DB::table('media_file')
                    //     ->where('repository_id', $media_repo->id)
                    //     ->get();
                    $media_files = DB::table('media_repository')
                        ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                        ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                        ->join('language', 'language.id', '=', 'media_file.language_id')
                        ->where('media_repository.id', $media_repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_repositories[$key1]->files = $media_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                        ->where('media_hotspot.repository_id', $media_repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'media repositories data',
                'data' => $media_categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_repository_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'repository_id' => 'required',
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

            $media_repositories = DB::table('media_repository')
                ->where('id', $request->repository_id)
                ->where('is_deleted', 0)
                ->first();

            $media_files = DB::table('media_repository')
                ->select('media_file.id', 'media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                ->join('language', 'language.id', '=', 'media_file.language_id')
                ->where('repository_id', $media_repositories->id)
                ->where('media_file.is_deleted', 0)
                ->orderBy('language.id', 'asc')
                ->get();

            $media_repositories->files = $media_files;

            $media_repositories_hotsopts = DB::table('media_repository')
                ->select('media_repository.title as repo_title', 'media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                ->where('media_hotspot.repository_id', $media_repositories->id)
                ->where('media_hotspot.is_deleted', 0)
                ->get();

            $media_repositories->hotspots = $media_repositories_hotsopts;

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Files',
                'data' => $media_repositories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_repository_files_bypass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'repository_id' => 'required',
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

            $media_repositories = DB::table('media_repository')
                ->where('id', $request->repository_id)
                ->where('is_deleted', 0)
                ->first();

            $media_files = DB::table('media_repository')
                ->select('media_file.id', 'media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                ->join('language', 'language.id', '=', 'media_file.language_id')
                ->where('repository_id', $media_repositories->id)
                ->where('media_file.is_deleted', 0)
                ->orderBy('language.id', 'asc')
                ->get();

            $media_repositories->files = $media_files;

            $media_repositories_hotsopts = DB::table('media_repository')
                ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                ->where('media_hotspot.repository_id', $media_repositories->id)
                ->where('media_hotspot.is_deleted', 0)
                ->get();

            $media_repositories->hotspots = $media_repositories_hotsopts;

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Files',
                'data' => $media_repositories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function search_in_media_repository(Request $request)
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
                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->where(function ($query) use ($keyword) {
                            $query->where('title', 'like', '%' . $keyword . '%')
                                ->orWhere('description', 'like', '%' . $keyword . '%')
                                ->orWhere('date_created', 'like', '%' . $keyword . '%');
                        })
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        // $media_files = DB::table('media_file')
                        //     ->where('repository_id', $media_repo->id)
                        //     ->get();

                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('repository_id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            } else {
                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        // $media_files = DB::table('media_file')
                        //     ->where('repository_id', $media_repo->id)
                        //     ->get();

                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('repository_id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Search Data',
                'data' => $media_categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_media_repository_files(Request $request)
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

            $repository = DB::table('media_category')
                ->select('media_category.id as media_category_id', 'media_category.title as media_category_title', 'media_repository.id as repository_id', 'media_repository.type as repository_type', 'media_repository.title as repository_title')
                ->join('media_repository', 'media_repository.media_category_id', '=', 'media_category.id')
                ->where('media_repository.id', $request->repository_id)
                ->where('media_repository.is_deleted', 0)
                ->first();

            $repository->files = DB::table('media_repository')
                ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                ->join('language', 'language.id', '=', 'media_file.language_id')
                ->where('media_repository.id', $request->repository_id)
                ->where('media_file.is_deleted', 0)
                ->get();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'Media Repository Files',
                'data' => $repository,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function add_media_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
            'description' => 'required',
            'imgBase64' => 'required',
            'languages' => 'required|array',
            'files' => 'required|array',
            'files.*' => 'mimes:jpg,jpeg,png,mp4',
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

            $getID3 = new \getID3;

            $lastId = DB::table('media_repository')->insertGetId([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    // 'thumbnail' => $file,
                    'updated_by' => 1,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            $imageCount = 0;
            $videoCount = 0;
            foreach (array_combine($request->languages, $request->file('files')) as $lang => $file) {

                if ($file->getClientOriginalExtension() == 'png' || $file->getClientOriginalExtension() == 'jpg' || $file->getClientOriginalExtension() == 'jpeg') {
                    if ($imageCount == 0) {
                        $imagePath = 'uploads/media_repository_images/';
                        $ImageUpload = Image::make($file);
                        $ImageUpload->resize(480, 270);

                        $unix_timestamp = 'thumbnail' . '_' . now()->timestamp . $file->getClientOriginalName();
                        $thumbnailImage = $imagePath . $unix_timestamp;
                        $ImageUpload = $ImageUpload->save($thumbnailImage);

                        DB::table('media_repository')->where('id', $lastId)->update([
                                'thumbnail' => $unix_timestamp . $file->getClientOriginalName(),
                                'date_modified' => date('Y-m-d H:i:s'),
                            ]);
                    }
                    $imageCount++;

                    $imagePath = 'uploads/media_repository_images/';
                    $unix_timestamp = 'file' . '_' . now()->timestamp . $file->getClientOriginalName();
                    $file->move($imagePath, $unix_timestamp);

                    $ThisFileInfo = $getID3->analyze($imagePath . $unix_timestamp);

                    DB::table('media_file')->insert([
                            'repository_id' => $request->type,
                            'language_id' => $request->type,
                            'file' => $unix_timestamp,
                            'resolution' => $ThisFileInfo['video']['resolution_x'] . 'px by' . $ThisFileInfo['video']['resolution_y'] . 'px',
                            'filesize' => $ThisFileInfo['filesize'],
                            'format' => $ThisFileInfo['fileformat'],
                            'date_created' => date('Y-m-d H:i:s'),
                            'date_modified' => date('Y-m-d H:i:s'),
                            'updated_by' => 1,
                        ]);
                } else {
                    $imagePath = 'uploads/media_repository_images/';

                    if ($videoCount == 0) {
                        $img = $request->imgBase64;
                        $img = str_replace('data:image/jpeg;base64,', '', $img);
                        $img = str_replace(' ', '+', $img);
                        $data = base64_decode($img);
                        $videoFile = 'thumbnail_' . now()->timestamp . '.jpg';
                        $success = file_put_contents($imagePath . $videoFile, $data);

                        DB::table('media_repository')->where('id', $lastId)->update([
                                'thumbnail' => $videoFile,
                                'date_modified' => date('Y-m-d H:i:s'),
                            ]);
                    }
                    $videoCount++;

                    $unix_timestamp = 'video ' . '_' . now()->timestamp . $file->getClientOriginalName();
                    $file->move($imagePath, $unix_timestamp);

                    $ThisFileInfo = $getID3->analyze($imagePath . $unix_timestamp);

                    DB::table('media_file')->insert([
                            'repository_id' => $request->type,
                            'language_id' => $lang,
                            'file' => $unix_timestamp,
                            'resolution' => $ThisFileInfo['video']['resolution_x'] . 'px by' . $ThisFileInfo['video']['resolution_y'] . 'px',
                            'filesize' => $ThisFileInfo['filesize'],
                            'duration' => $ThisFileInfo['playtime_string'],
                            'bitrate' => round($ThisFileInfo['bitrate']),
                            'format' => $ThisFileInfo['fileformat'],
                            'date_created' => date('Y-m-d H:i:s'),
                            'date_modified' => date('Y-m-d H:i:s'),
                            'updated_by' => 1,
                        ]);
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository and Files Uploaded Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function save_media_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
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

            $lastId = DB::table('media_repository')->insertGetId([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'updated_by' => $request->updated_by,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Added',
                'last_id' => $lastId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function test_save_media_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
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

            $lastId = DB::table('media_repository')->insertGetId([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'thumbnail' => $request->thumbnail,
                    'updated_by' => $request->updated_by,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            foreach ($request->media_files as $key => $media) {
                DB::table('media_file')->insert([
                        'repository_id' => $lastId,
                        'language_id' => $media['language_id'],
                        'file' => $media['file'],
                        'resolution' => $media['resolution'],
                        'width' => $media['width'],
                        'height' => $media['height'],
                        'filesize' => (isset($media['filesize'])) ? $media['filesize'] : '',
                        'duration' => (isset($media['duration'])) ? $media['duration'] : '',
                        'bitrate' => (isset($media['bitrate'])) ? $media['bitrate'] : '',
                        'format' => (isset($media['format'])) ? $media['format'] : '',
                        'fps' => (isset($media['fps'])) ? $media['fps'] : '',
                        'date_created' => $media['date_created'],
                        'date_modified' => $media['date_modified'],
                        'updated_by' => $media['updated_by'],
                    ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Added',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function test_save_media_360_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
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

            $lastId = DB::table('media_repository')->insertGetId([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'thumbnail' => $request->thumbnail,
                    'updated_by' => $request->updated_by,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            foreach ($request->media_files as $key => $media) {
                DB::table('media_file')->insert([
                        'repository_id' => $lastId,
                        'language_id' => $media['language_id'],
                        'file' => $media['file'],
                        'resolution' => $media['resolution'],
                        'width' => $media['width'],
                        'height' => $media['height'],
                        'filesize' => (isset($media['filesize'])) ? $media['filesize'] : '',
                        'duration' => (isset($media['duration'])) ? $media['duration'] : '',
                        'bitrate' => (isset($media['bitrate'])) ? $media['bitrate'] : '',
                        'format' => (isset($media['format'])) ? $media['format'] : '',
                        'fps' => (isset($media['fps'])) ? $media['fps'] : '',
                        'date_created' => $media['date_created'],
                        'date_modified' => $media['date_modified'],
                        'updated_by' => $media['updated_by'],
                    ]);
            }

            foreach ($request->media_hotspots as $key => $hotspot) {
                DB::table('media_hotspot')->insert([
                        'repository_id' => $lastId,
                        'title' => $hotspot['title'],
                        'link_repository_id' => $hotspot['link_repository_id'],
                        'date_created' => date('Y-m-d H:i:s'),
                        'date_modified' => date('Y-m-d H:i:s'),
                        "updated_by" => $request->updated_by,
                    ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Added',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function update_media_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
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

            DB::table('media_repository')->where('id', $request->repo_id)->update([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function test_update_media_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }
        // try {

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

        DB::table('media_repository')->where('id', $request->repo_id)->update([
                'media_category_id' => $request->category_id,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'thumbnail' => isset($request->thumbnail) ? $request->thumbnail : $request->old_thumbnail,
                'date_modified' => date('Y-m-d H:i:s'),
            ]);

        foreach ($request->media_files as $key => $media) {

            // return $media['language_id'];

            if ($media['insert'] == 'insert') {
                DB::table('media_file')->insert([
                        'language_id' => $media['language_id'],
                        'file' => $media['file'],
                        'resolution' => $media['resolution'],
                        'width' => $media['width'],
                        'height' => $media['height'],
                        'filesize' => (isset($media['filesize'] )) ? $media['filesize'] : '',
                        'duration' => (isset($media['duration'] )) ? $media['duration'] : '',
                        'bitrate' => (isset($media['bitrate'] )) ? $media['bitrate'] : '',
                        'format' => (isset($media['format'] )) ? $media['format'] : '',
                        'fps' => (isset($media['fps'] )) ? $media['fps'] : '',
                        'date_modified' => $media['date_modified'],
                        // 'updated_by' => $media->updated_by,
                    ]);
            } else {
                DB::table('media_file')->where('id', $media['media_file_id'])->update([
                    'language_id' => $media['language_id'],
                    'file' => $media['file'],
                    'resolution' => $media['resolution'],
                    'width' => $media['width'],
                    'height' => $media['height'],
                    'filesize' => (isset($media['filesize'] )) ? $media['filesize'] : '',
                    'duration' => (isset($media['duration'] )) ? $media['duration'] : '',
                    'bitrate' => (isset($media['bitrate'] )) ? $media['bitrate'] : '',
                    'format' => (isset($media['format'] )) ? $media['format'] : '',
                    'fps' => (isset($media['fps'] )) ? $media['fps'] : '',
                    'date_modified' => $media['date_modified'],
                    // 'updated_by' => $media->updated_by,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Repository Updated',
        ]);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'status' => 500,
        //         'error' => $e,
        //     ]);
        // }
    }

    public function test_update_media_360_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'type' => 'required',
            'title' => 'required',
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

            $lastId = $request->repo_id;

            DB::table('media_repository')->where('id', $lastId)->update([
                    'media_category_id' => $request->category_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'thumbnail' => $request->thumbnail,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            foreach ($request->media_files as $key => $media) {
                DB::table('media_file')->where('id', $media['media_file_id'])->update([
                        'repository_id' => $lastId,
                        'language_id' => $media['language_id'],
                        'file' => $media['file'],
                        'resolution' => $media['resolution'],
                        'width' => $media['width'],
                        'height' => $media['height'],
                        'filesize' => (isset($media['filesize'])) ? $media['filesize'] : '',
                        'duration' => (isset($media['duration'])) ? $media['duration'] : '',
                        'bitrate' => (isset($media['bitrate'])) ? $media['bitrate'] : '',
                        'format' => (isset($media['format'])) ? $media['format'] : '',
                        'fps' => (isset($media['fps'])) ? $media['fps'] : '',
                        'date_created' => $media['date_created'],
                        'date_modified' => $media['date_modified'],
                        'updated_by' => $media['updated_by'],
                    ]);
            }
            DB::table('media_hotspot')->where('repository_id', $lastId)->delete();

            foreach ($request->media_hotspots as $key => $hotspot) {
                DB::table('media_hotspot')->insert([
                        'title' => $hotspot['title'],
                        'link_repository_id' => $hotspot['link_repository_id'],
                        'date_created' => date('Y-m-d H:i:s'),
                        'date_modified' => date('Y-m-d H:i:s'),
                        "updated_by" => $request->updated_by,
                    ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Repository Updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_media_categories(Request $request)
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

            $media_categories = DB::table('media_category')
                ->where('is_deleted', 0)
                ->get();

            return response()->json([
                'success' => 'true',
                'status' => '200',
                'message' => 'Media Categories',
                'data' => $media_categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function media_repositories_by_category(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
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

            $media_categories = DB::table('media_category')
                ->where('id', $request->category_id)
                ->where('is_deleted', 0)
                ->first();

            $media_repositories = DB::table('media_repository')
                ->where('media_category_id', $media_categories->id)
                ->where('is_deleted', 0)
                ->orderBy('date_modified', 'desc')
                ->get();

            $media_categories->media_repositories = $media_repositories;

            foreach ($media_repositories as $key => $repo) {
                $media_repository_files = DB::table('media_file')
                    ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                    ->join('language', 'language.id', '=', 'media_file.language_id')
                    ->join('media_repository', 'media_repository.id', '=', 'media_file.repository_id')
                    ->where('media_file.repository_id', $repo->id)
                    ->where('media_file.is_deleted', 0)
                    ->get();

                $media_categories->media_repositories[$key]->files = $media_repository_files;
            }

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Media Repositories by Category',
                'data' => $media_categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function create_thumbnail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
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

            $file = $request->file('file');

            $videoPath = 'uploads/thumbnails/';

            $unix_timestamp = 'thumbnail_video' . '_' . now()->timestamp;
            $video = $videoPath . $unix_timestamp . $file->getClientOriginalName();
            $file->move($video, $file->getClientOriginalName());
            $fullPath = URL::to('/') . '/' . $video;

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'File Uploaded',
                'data' => $fullPath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function order_by_media_repository(Request $request)
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

            if ($request->order_by == 'added') {

                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->orderBy('date_created', 'desc')
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        $media_files = DB::table('media_file')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('media_file.repository_id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            } elseif ($request->order_by == 'alphabetically') {

                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->orderBy('id', 'desc')
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {
                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('media_repository.id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            } elseif ($request->order_by == 'size') {

                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->select('media_repository.*', 'media_file.filesize')
                        ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                        ->where('media_category_id', $category->id)
                        ->where('media_repository.is_deleted', 0)
                        ->orderBy('media_file.filesize', 'asc')
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {
                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('media_repository.id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            } elseif ($request->order_by == 'asc') {

                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->orderBy('id', 'asc')
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        // $media_files = DB::table('media_file')
                        //     ->where('repository_id', $media_repo->id)
                        //     ->get();
                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('media_repository.id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            } elseif ($request->order_by == 'desc') {

                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where('is_deleted', 0)
                        ->orderBy('id', 'desc')
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        // $media_files = DB::table('media_file')
                        //     ->where('repository_id', $media_repo->id)
                        //     ->get();
                        $media_files = DB::table('media_repository')
                            ->select('media_file.is_external', 'media_file.file', 'media_file.qr_code', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                            ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                            ->join('language', 'language.id', '=', 'media_file.language_id')
                            ->where('media_repository.id', $media_repo->id)
                            ->where('media_file.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;

                        $media_repositories_hotsopts = DB::table('media_repository')
                            ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                            ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                            ->where('media_hotspot.repository_id', $media_repo->id)
                            ->where('media_hotspot.is_deleted', 0)
                            ->get();

                        $media_repositories[$key1]->hotspots = $media_repositories_hotsopts;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Media Repositories',
                'data' => $media_categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function delete_media_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 200,
                'errors' => $validator->errors(),
            ];
        }
        try {

            DB::table('media_file')
                ->where('id', $request->file_id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'File Deleted successfully',
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
