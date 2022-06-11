<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Validator;

class Template extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required',
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

            $template = DB::table('template')
                ->where('id', $request->template_id)
                ->where('is_deleted', 0)
                ->first();

            $media_repository = DB::table('media_repository')
                ->select('media_repository.id as media_repo_id', 'media_repository.type as media_repo_type', 'media_repository.title as media_repo_title', 'media_repository.thumbnail as media_thumbnail', 'media_repository.description as media_description', 'media_category.id as media_cat_id', 'media_category.title as media_cat_title', 'sequence.id as sequence_id', 'sequence.order_id as sequence_order', 'meda_repository_type.title as media_repo_type_title', 'meda_repository_type.icon as media_repo_type_icon')
                ->join('sequence', 'sequence.media_repository_id', '=', 'media_repository.id')
                ->join('media_category', 'media_repository.media_category_id', '=', 'media_category.id')
                ->join('meda_repository_type', 'media_repository.type', '=', 'meda_repository_type.id')
                ->where('sequence.template_id', $request->template_id)
                ->where('media_repository.is_deleted', 0)
                ->where('sequence.is_deleted', 0)
                ->orderBy('sequence.order_id')
                ->get();

            foreach ($media_repository as $key => $media) {

                $media_repositories = DB::table('media_repository')
                    ->select('media_file.is_external', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'media_file.qr_code', 'language.id as lang_id', 'language.title as lang_title', 'language.code as lang_code', 'language.icon as lang_icon')
                    ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                    ->join('language', 'language.id', '=', 'media_file.language_id')
                    ->where('media_repository.id', $media->media_repo_id)
                    ->where('media_repository.is_deleted', 0)
                    ->where('media_file.is_deleted', 0)
                    ->get();

                $media_repository[$key]->files = $media_repositories;

                $media_repositories_hotsopts = DB::table('media_repository')
                    ->select('media_repository.media_category_id as link_repository_cat', 'media_repository.type as link_repository_type', 'media_hotspot.*')
                    ->join('media_hotspot', 'media_hotspot.link_repository_id', '=', 'media_repository.id')
                    ->where('media_hotspot.repository_id', $media->media_repo_id)
                    ->where('media_hotspot.is_deleted', 0)
                    ->get();

                $media_repository[$key]->hotspots = $media_repositories_hotsopts;
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Template and media repository sequences',
                'data' => [
                    'templated_data' => $template,
                    'media_repository' => $media_repository,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function all_templates(Request $request)
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
                ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                ->join('users', 'users.id', '=', 'template.user_id')
                ->join('sequence', 'sequence.template_id', '=', 'template.id')
                ->where('template.is_deleted', 0)
                ->where('sequence.is_deleted', 0)
                ->groupBy('sequence.template_id')
                ->get();

            foreach ($templates as $key => $template) {

                $media_repositories = DB::table('media_file')
                    ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                    ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                    ->join('template', 'template.id', '=', 'sequence.template_id')
                    ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                    ->where('sequence.template_id', $template->template_id)
                    ->where('media_repository.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence_id')
                    ->get();

                $templates[$key]->files = $media_repositories;

                $media_repositories_hotsopts = DB::table('media_repository')
                    ->select('media_repository.type as repository_type', 'media_hotspot.*')
                    ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                    ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                    ->where('sequence.template_id', $template->template_id)
                    ->where('media_hotspot.is_deleted', 0)
                    ->groupBy('sequence.id')
                    ->get();

                $templates[$key]->hotspots = $media_repositories_hotsopts;
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Templates Data',
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_template(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required',
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

            $template = DB::table('template')
                ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                ->join('users', 'users.id', '=', 'template.user_id')
                ->join('sequence', 'sequence.template_id', '=', 'template.id')
                ->where('template.is_deleted', 0)
                ->where('template.id', $request->template_id)
                ->where('sequence.is_deleted', 0)
                ->first();

            $media_repositories = DB::table('media_file')
                ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                ->join('template', 'template.id', '=', 'sequence.template_id')
                ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                ->where('sequence.template_id', $template->template_id)
                ->where('media_repository.is_deleted', 0)
                ->where('sequence.is_deleted', 0)
                ->groupBy('sequence_id')
                ->get();

            $template->files = $media_repositories;

            $media_repositories_hotsopts = DB::table('media_repository')
                ->select('media_repository.type as repository_type', 'media_hotspot.*')
                ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                ->where('sequence.template_id', $template->template_id)
                ->where('media_hotspot.is_deleted', 0)
                ->groupBy('sequence.id')
                ->get();

            $template->hotspots = $media_repositories_hotsopts;

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Templates Data',
                'data' => $template,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_author_templates(Request $request)
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

            $templates = DB::table('template')
                ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                ->join('users', 'users.id', '=', 'template.user_id')
                ->join('sequence', 'sequence.template_id', '=', 'template.id')
                ->where('template.user_id', $request->author_id)
                ->where('template.is_deleted', 0)
                ->where('sequence.is_deleted', 0)
                ->groupBy('sequence.template_id')
                ->get();

            foreach ($templates as $key => $template) {

                $media_repositories = DB::table('media_file')
                    ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                    ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                    ->join('template', 'template.id', '=', 'sequence.template_id')
                    ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                    ->where('sequence.template_id', $template->template_id)
                    ->where('media_repository.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence_id')
                    ->get();

                $templates[$key]->files = $media_repositories;

                $media_repositories_hotsopts = DB::table('media_repository')
                    ->select('media_repository.type as repository_type', 'media_hotspot.*')
                    ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                    ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                    ->where('sequence.template_id', $template->template_id)
                    ->where('media_hotspot.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence.id')
                    ->get();

                $templates[$key]->hotspots = $media_repositories_hotsopts;
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Templates Data',
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function get_templates_order_by(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_by' => 'required',
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

            if ($request->order_by == 'added') {

                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->orderBy('template.date_created', 'desc')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('media_repository.is_deleted', 0)
                        ->where('media_file.is_deleted', 0)
                        ->where('sequence.is_deleted', 0)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->table('media_repository.is_deleted', 0)
                        ->where('media_hotspot.is_deleted', 0)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'alphabetically') {
                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->orderBy('template.title', 'asc')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('media_repository.is_deleted', 0)
                        ->where('sequence.is_deleted', 0)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('sequence.is_deleted', 0)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'asc') {
                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->orderBy('template.id', 'asc')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('media_repository.is_deleted', 0)
                        ->where('sequence.is_deleted', 0)
                        ->where('sequence.template_id', $template->template_id)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'desc') {
                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->where('sequence.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->orderBy('template.id', 'desc')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('media_repository.is_deleted', 0)
                        ->where('template.is_deleted', 0)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Templates Data',
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function search_in_templates(Request $request)
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

            if (!empty($request->search)) {

                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->where('template.title', 'like', '%' . $request->search . '%')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('media_repository.is_deleted', 0)
                        ->where('sequence.template_id', $template->template_id)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            } else {
                $templates = DB::table('template')
                    ->select('template.title', 'template.description', 'template.user_id', 'template.date_created', 'template.date_modified', 'sequence.template_id', 'users.id', 'users.type', 'users.unique_id', 'users.email', 'users.name', DB::raw('count(sequence.template_id) as total_assets'))
                    ->join('users', 'users.id', '=', 'template.user_id')
                    ->join('sequence', 'sequence.template_id', '=', 'template.id')
                    ->where('template.is_deleted', 0)
                    ->groupBy('sequence.template_id')
                    ->get();

                foreach ($templates as $key => $template) {

                    $media_repositories = DB::table('media_file')
                        ->select('sequence.id as sequence_id', 'media_file.id', 'media_file.file', 'media_file.resolution', 'media_file.width', 'media_file.height', 'media_file.filesize', 'media_file.duration', 'media_file.bitrate', 'media_file.fps', 'media_file.format', 'sequence.media_repository_id', 'media_repository.thumbnail')
                        ->join('sequence', 'sequence.media_repository_id', '=', 'media_file.repository_id')
                        ->join('template', 'template.id', '=', 'sequence.template_id')
                        ->join('media_repository', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('media_repository.is_deleted', 0)
                        ->where('sequence.template_id', $template->template_id)
                        ->groupBy('sequence_id')
                        ->get();

                    $templates[$key]->files = $media_repositories;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->join('sequence', 'media_repository.id', '=', 'sequence.media_repository_id')
                        ->where('sequence.template_id', $template->template_id)
                        ->groupBy('sequence.id')
                        ->get();

                    $templates[$key]->hotspots = $media_repositories_hotsopts;
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Templates Data',
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function create_template(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'user_id' => 'required',
            'media_repository_ids' => 'required|array',
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

            $lastId = DB::table('template')->insertGetId([
                    'title' => $request->title,
                    'description' => $request->description,
                    'user_id' => $request->user_id,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            // foreach ($request->media_repository_ids as $key => $repo_id) {
            foreach (array_combine($request->sequenceIds, $request->media_repository_ids) as $sequence => $repo_id) {
                DB::table('sequence')->insert([
                        'type' => 1,
                        'template_id' => $lastId,
                        'media_repository_id' => $repo_id,
                        'order_id' => $sequence,
                        'date_created' => date('Y-m-d H:i:s'),
                        'date_modified' => date('Y-m-d H:i:s'),
                    ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Template Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function search_in_media_repo_category(Request $request)
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

            if (!empty($request->search)) {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $media_categories->id)
                    ->where('is_deleted', 0)
                    ->where('title', 'like', '%' . $request->search . '%')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            } else {
                $media_categories = DB::table('media_category')
                    ->where('is_deleted', 0)
                    ->where('id', $request->category_id)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('is_deleted', 0)
                    ->where('media_category_id', $media_categories->id)
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Category Media Repository',
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

    public function order_by_media_repo_category(Request $request)
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

            if ($request->order_by == 'added') {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $media_categories->id)
                    ->where('is_deleted', 0)
                    ->orderBy('date_created', 'desc')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'alphabetically') {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $media_categories->id)
                    ->where('is_deleted', 0)
                    ->orderBy('title', 'asc')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'size') {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->select('media_repository.*', 'media_file.filesize')
                    ->join('media_file', 'media_file.repository_id', '=', 'media_repository.id')
                    ->where('media_category_id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->orderBy('media_file.filesize', 'asc')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'asc') {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->orderBy('id', 'asc')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
                }
            } elseif ($request->order_by == 'desc') {

                $media_categories = DB::table('media_category')
                    ->where('id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->first();

                $media_repositories = DB::table('media_repository')
                    ->where('media_category_id', $request->category_id)
                    ->where('is_deleted', 0)
                    ->orderBy('id', 'desc')
                    ->get();

                $media_categories->media_repositories = $media_repositories;

                foreach ($media_repositories as $key => $repo) {
                    $media_repository_files = DB::table('media_file')
                        ->where('repository_id', $repo->id)
                        ->where('media_file.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->files = $media_repository_files;

                    $media_repositories_hotsopts = DB::table('media_repository')
                        ->select('media_repository.type as repository_type', 'media_hotspot.*')
                        ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                        ->where('media_repository.id', $repo->id)
                        ->where('media_hotspot.is_deleted', 0)
                        ->get();

                    $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
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

    public function add_media_repositories_in_sequence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'repo_id' => 'required|array',
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
                ->whereIn('id', $request->repo_id)
                ->where('is_deleted', 0)
                ->get();

            $media_categories->media_repositories = $media_repositories;

            foreach ($media_repositories as $key => $repo) {
                $media_repository_files = DB::table('media_file')
                    ->where('repository_id', $repo->id)
                    ->where('is_deleted', 0)
                    ->get();

                $media_categories->media_repositories[$key]->files = $media_repository_files;

                $media_repositories_hotsopts = DB::table('media_repository')
                    ->select('media_repository.type as repository_type', 'media_hotspot.*')
                    ->join('media_hotspot', 'media_hotspot.repository_id', '=', 'media_repository.id')
                    ->where('media_repository.id', $repo->id)
                    ->get();

                $media_categories->media_repositories[$key]->hotspots = $media_repositories_hotsopts;
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Category Media Repository',
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

    public function update_template(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'user_id' => 'required',
            'template_id' => 'required',
            'media_repository_ids' => 'required|array',
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

            DB::table('template')->where('id', $request->template_id)->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'date_modified' => date('Y-m-d H:i:s'),
                ]);

            DB::table('sequence')->where('template_id', $request->template_id)->delete();

            // foreach ($request->media_repository_ids as $key => $repo_id) {
            foreach (array_combine($request->sequenceIds, $request->media_repository_ids) as $sequence => $repo_id) {
                DB::table('sequence')->insert([
                        'type' => 1,
                        'template_id' => $request->template_id,
                        'media_repository_id' => $repo_id,
                        'order_id' => $sequence,
                        'date_created' => date('Y-m-d H:i:s'),
                        'date_modified' => date('Y-m-d H:i:s'),
                    ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Template Updated Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'error' => $e,
            ]);
        }
    }

    public function delete_template(Request $request)
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

            DB::table('template')
                ->where('id', $request->id)
                ->update([
                    'is_deleted' => 1,
                ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Template Deleted successfully',
            ];
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
