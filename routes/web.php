<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/generateVue', [FileController::class, 'generateVueFile']);




Route::prefix("/v1")->group(function () {
    Route::prefix('/projects')->group(function () {
       // 获取项目列表
        Route::get('/', [ProjectController::class, 'index']);

        // 获取 页码
        Route::get('/count', [ProjectController::class, 'count']);
    });

    Route::prefix('/pages')->group(function () {
        // 根据 项目id 获取 page列表
        Route::get('/', [PageController::class, 'getPageById']);
        // 创建page
        Route::post('/', [PageController::class, 'addPage']);
        // 删除
        Route::delete('/', [PageController::class, 'deletePage']);
    });

    // 获取ApiProjectPrefix 前缀
    Route::prefix('/apiProjects')->group(function () {
        // 获取 项目所有的前缀
        Route::prefix('/projectsPrefix',[]);
    });

    Route::prefix('/projectConfig')->group(function () {
        // 根据id 获取 页面里的配置
        Route::get('/', [ProjectController::class, 'getProjectConfigById']);

        // 上传 项目简介
        Route::put('/', [ProjectController::class, 'updateProjectConfig']);
    });



});
