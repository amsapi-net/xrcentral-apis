<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guests;
use App\Http\Controllers\Users;
use App\Http\Controllers\ZoomMeeting;
use App\Http\Controllers\MainMenu;
use App\Http\Controllers\MediaRepository;
use App\Http\Controllers\Settings;
use App\Http\Controllers\Studio;
use App\Http\Controllers\Template;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::controller(ZoomMeeting::class)->group(function () {
    Route::get('user-accept-invitation', 'user_accept_invitation')->name('user-accept-invitation');
    Route::get('user-reject-invitation', 'user_reject_invitation')->name('user-reject-invitation');
});

// Users Controller Routes
Route::controller(Users::class)->group(function () {
    Route::post('check-email', 'check_email')->name('check-email');
    Route::post('update-password', 'update_password')->name('update-password');
    Route::post('check-forgot-password', 'check_forgot_password')->name('check-forgot-password');
    Route::get('forgot-password', 'forgot_password')->name('forgot-password');
    Route::post('update-forgot-password', 'update_forgot_password')->name('update-forgot-password');
    Route::post('user-log-in', 'login')->name('user-log-in');
});

Route::middleware('APIToken')->group(function () {

    // Guests Controller Routes
    Route::controller(Guests::class)->group(function () {
        Route::get('get-all-guests', 'get_all_guests')->name('get-all-guests');
        Route::get('guest-detail', 'guest_detail')->name('guest-detail');
        Route::post('save-guest', 'save_guest')->name('save-guest');
        Route::post('update-guest', 'update_guest')->name('update-guest');
        Route::get('delete-guest', 'delete_guest')->name('delete-guest');
    });

    // Users Controller Routes
    Route::controller(Users::class)->group(function () {
        Route::get('get-user', 'get_user')->name('get-user');
        Route::get('search-user', 'search_user')->name('search-user');
        Route::get('all-users', 'get_all_users')->name('all-users');
        Route::post('change-password', 'change_password')->name('change-password');
    });

    // ZoomMeeting Controller Routes
    Route::controller(ZoomMeeting::class)->group(function () {
        Route::get('delete-invitations', 'delete_invitations')->name('delete-invitations');
        Route::get('meeting-list', 'meeting_list')->name('meeting-list');
        Route::get('meeting-details', 'meeting_details')->name('meeting-details');
        Route::get('get-zoom-meeting-details', 'get_meeting_status')->name('get-zoom-meeting-details');
        Route::get('update-zoom-meeting-status', 'update_zoom_meeting_status')->name('update-zoom-meeting-status');
        Route::get('template-and-language', 'templates')->name('template-and-language');
        Route::post('create-invitation', 'create_invitation')->name('create-invitation');
        Route::post('create-meeting', 'create_meeting')->name('create-meeting');
        Route::get('filter-meeting-by-author', 'filter_meeting_by_author')->name('filter-meeting-by-author');
        Route::get('filter-meeting-by-status', 'filter_meeting_by_status')->name('filter-meeting-by-status');
        Route::get('search-invitations', 'search_invitations')->name('search-invitations');
        Route::post('update-invitation', 'update_invitation')->name('update-invitation');
        Route::post('update-meeting-host', 'update_meeting_host')->name('update-meeting-host');
    });

    // Template Controller Routes
    Route::controller(Template::class)->group(function () {
        Route::get('get-template-data', 'index')->name('get-template-data');
        Route::get('all-templates', 'all_templates')->name('all-templates');
        Route::get('get-template', 'get_template')->name('get-template');
        Route::get('get-author-templates', 'get_author_templates')->name('get-author-templates');
        Route::get('get-templates-order-by', 'get_templates_order_by')->name('get-templates-order-by');
        Route::get('search-in-templates', 'search_in_templates')->name('search-in-templates');
        Route::post('create-template', 'create_template')->name('create-template');
        Route::post('update-template', 'update_template')->name('update-template');
        Route::get('search-in-media-repo-category', 'search_in_media_repo_category')->name('search-in-media-repo-category');
        Route::get('order-by-in-media-repo-category', 'order_by_media_repo_category')->name('order-by-in-media-repo-category');
        Route::post('add-media-repositories-in-sequence', 'add_media_repositories_in_sequence')->name('add-media-repositories-in-sequence');
        Route::get('delete-template', 'delete_template')->name('delete-template');
    });

    // MediaRepository Controller Routes
    Route::controller(MediaRepository::class)->group(function () {
        Route::get('order-by-media-repository', 'order_by_media_repository')->name('order-by-media-repository');
        Route::get('media-repositories', 'index')->name('media-repositories');
        Route::get('get-repository-files', 'get_repository_files')->name('get-repository-files');
        Route::get('get-repository-files-bypass', 'get_repository_files_bypass')->name('get-repository-files-bypass');
        Route::get('search-in-media-repository', 'search_in_media_repository')->name('search-in-media-repository');
        Route::get('get-media-repository-files', 'get_media_repository_files')->name('get-media-repository-files');
        Route::post('add-media-files', 'add_media_files')->name('add-media-files');
        Route::post('save-media-files', 'save_media_files')->name('save-media-files');
        Route::post('test-save-media-files', 'test_save_media_files')->name('test-save-media-files');
        Route::post('test-save-media-360-files', 'test_save_media_360_files')->name('test-save-media-360-files');
        Route::post('update-media-files', 'update_media_files')->name('update-media-files');
        Route::post('test-update-media-files', 'test_update_media_files')->name('test-update-media-files');
        Route::post('test-update-media-360-files', 'test_update_media_360_files')->name('test-update-media-files');
        Route::post('create-thumbnail', 'create_thumbnail')->name('create-thumbnail');
        Route::get('get-media-categories', 'get_media_categories')->name('get-media-categories');
        Route::get('media-repositories-by-category', 'media_repositories_by_category')->name('media-repositories-by-category');
        Route::get('delete-media-file', 'delete_media_file')->name('delete-media-file');
    });

    // MainMenu Controller Routes
    Route::controller(MainMenu::class)->group(function () {
        Route::get('main-menu-data', 'main_menu_data')->name('main-menu-data');
    });

    // Settings Controller Routes
    Route::controller(Settings::class)->group(function () {
        Route::post('add-presenter', 'add_presenter')->name('add-presenter');
        Route::post('update-presenter', 'update_presenter')->name('update-presenter');
        Route::post('add-operator', 'add_operator')->name('add-operator');
        Route::post('update-operator', 'update_operator')->name('update-operator');
        Route::post('add-settings', 'add_settings')->name('add-settings');
        Route::post('update-settings', 'update_settings')->name('update-settings');
        Route::get('get-settings', 'get_settings')->name('get-settings');
        Route::get('all-settings', 'all_settings')->name('all-settings');
        Route::get('delete-presenter', 'delete_presenter')->name('delete-presenter');
        Route::get('delete-operator', 'delete_operator')->name('delete-operator');
        Route::get('delete-studio', 'delete_studio')->name('delete-studio');
        Route::get('delete-settings', 'delete_settings')->name('delete-settings');
    });

    // Studio Controller Routes
    Route::controller(Studio::class)->group(function () {
        Route::post('add-studio', 'add_studio')->name('add-studio');
        Route::post('update-studio', 'update_studio')->name('update-studio');
        Route::get('get-studio-details', 'index')->name('get-studio-details');
        Route::get('studios', 'studios')->name('studios');
    });
});
