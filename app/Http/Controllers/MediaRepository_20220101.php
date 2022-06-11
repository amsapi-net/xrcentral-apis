<?php

namespace App\Http\Controllers\xrcentralApis;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Image;
use Response;
use Thumbnail;
use Validator;

class MediaRepository extends Controller
{
    public function index()
    {
        try {

            $token = $request->header('Authorization');
            $user = DB::connection('mysql2')
                ->table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $media_categories = DB::connection('mysql2')
                ->table('media_category')
                ->where('is_deleted', 0)
                ->get();

            foreach ($media_categories as $key => $category) {

                $media_repositories = DB::connection('mysql2')
                    ->table('media_repository')
                    ->where('media_category_id', $category->id)
                ->where('is_deleted', 0)
                    ->get();

                $media_categories[$key]->media_repositories = $media_repositories;

                foreach ($media_repositories as $key1 => $media_repo) {

                    $media_files = DB::connection('mysql2')
                        ->table('media_file')
                        ->where('repository_id', $media_repo->id)
                ->where('is_deleted', 0)
                        ->get();

                    $media_repositories[$key1]->files = $media_files;
                }
            }

            return response()->json([
                'success' => false,
                'status' => 500,
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
            $user = DB::connection('mysql2')
                ->table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $media_repositories = DB::connection('mysql2')
                ->table('media_repository')
                ->where('id', $request->repository_id)
                ->first();

            $media_files = DB::connection('mysql2')
                ->table('media_file')
                ->where('repository_id', $media_repositories->id)
                ->get();

            $media_repositories->files = $media_files;


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
            $user = DB::connection('mysql2')
                ->table('users')
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
                $media_categories = DB::connection('mysql2')
                    ->table('media_category')
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::connection('mysql2')
                        ->table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->where(function ($query) use ($keyword) {
                            $query->where('title', 'like', '%' . $keyword . '%')
                                ->orWhere('description', 'like', '%' . $keyword . '%')
                                ->orWhere('date_created', 'like', '%' . $keyword . '%');
                        })
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        $media_files = DB::connection('mysql2')
                            ->table('media_file')
                            ->where('repository_id', $media_repo->id)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;
                    }
                }
            } else {
                $media_categories = DB::connection('mysql2')
                    ->table('media_category')
                    ->get();

                foreach ($media_categories as $key => $category) {

                    $media_repositories = DB::connection('mysql2')
                        ->table('media_repository')
                        ->where('media_category_id', $category->id)
                        ->get();

                    $media_categories[$key]->media_repositories = $media_repositories;

                    foreach ($media_repositories as $key1 => $media_repo) {

                        $media_files = DB::connection('mysql2')
                            ->table('media_file')
                            ->where('repository_id', $media_repo->id)
                            ->get();

                        $media_repositories[$key1]->files = $media_files;
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
            $user = DB::connection('mysql2')
                ->table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $repository = DB::connection('mysql2')
                ->table('media_category')
                ->select('media_category.id as media_category_id', 'media_category.title as media_category_title', 'media_repository.id as repository_id', 'media_repository.type as repository_type', 'media_repository.title as repository_title')
                ->join('media_repository', 'media_repository.media_category_id', '=', 'media_category.id')
                ->where('media_repository.id', $request->repository_id)
                ->first();

            $repository->files = DB::connection('mysql2')
                ->table('media_file')
                ->select('media_file.id as file_id', 'media_file.is_external as is_external', 'media_file.repository_id as repository_id', 'media_file.language_id as language_id', 'media_file.file as file', 'media_file.file_url as file_url', 'media_file.qr_code as qr_code', 'language.id as lang_id', 'language.title', 'language.code', 'language.icon')
                ->join('language', 'language.id', '=', 'media_file.language_id')
                ->where('media_file.repository_id', $request->repository_id)
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
            'tile' => 'required',
            'description' => 'required',
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
        $imageCount = 0;
        $videoCount = 0;
        foreach ($request->file('files') as $key => $file) {

            if ($file->getClientOriginalExtension() == 'png' || $file->getClientOriginalExtension() == 'jpg' || $file->getClientOriginalExtension() == 'jpeg') {
                if ($imageCount == 0) {

                    $imagePath = 'uploads/media_repository_images/';
                    $ImageUpload = Image::make($file);
                    $ImageUpload->resize(480, 270);

                    $unix_timestamp = 'thumbnail' . '_' . now()->timestamp;
                    $thumbnailImage = $imagePath . $unix_timestamp . $file->getClientOriginalName();
                    $ImageUpload = $ImageUpload->save($thumbnailImage);
                }
                $imageCount++;
            } else {

                // set storage path to store the file (actual video)
                $destination_path = 'uploads/media_repository_images/';

                // get file extension
                $extension = $file->getClientOriginalExtension();

                $unix_timestamp = 'thumbnail' . '_' . now()->timestamp;

                // $timestamp = str_replace([' ', ':'], '-', Carbon::now()->toDateTimeString());

                $file_name = $destination_path . $unix_timestamp . $file->getClientOriginalName();
                // $ImageUpload = $ImageUpload->save($thumbnailImage);

                $upload_status = $file->move($destination_path, $file_name);

                // die;

                if ($upload_status) {

                    $video_path = $file_name;

                    // set thumbnail image name
                    $thumbnail_image = $unix_timestamp . ".jpg";

                    $thumbnail_status = Thumbnail::getThumbnail($video_path, $destination_path, $thumbnail_image);
                    if ($thumbnail_status) {
                        echo "Thumbnail generated";
                    } else {
                        echo "thumbnail generation has failed";
                    }
                }

                $videoCount++;
            }
        }

        echo $imageCount . '<br>';
        echo $videoCount;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_media_categories()
    {
        try {
            $token = $request->header('Authorization');
            $user = DB::connection('mysql2')
                ->table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }
            $media_categories = DB::connection('mysql2')
                ->table('media_category')->get();

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
            $user = DB::connection('mysql2')
                ->table('users')
                ->where('api_token', $token)
                ->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'Your Token is expired or not a valid',
                ]);
            }

            $media_categories = DB::connection('mysql2')
                ->table('media_category')
                ->where('id', $request->category_id)
                ->first();

            $media_repositories = DB::connection('mysql2')
                ->table('media_repository')
                ->where('media_category_id', $media_categories->id)
                ->get();

            $media_categories->media_repositories = $media_repositories;

            foreach ($media_repositories as $key => $repo) {
                $media_repository_files = DB::connection('mysql2')
                    ->table('media_file')
                    ->where('repository_id', $repo->id)
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
}
