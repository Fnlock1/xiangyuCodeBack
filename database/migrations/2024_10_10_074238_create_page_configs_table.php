<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
//        这个数据库 的存放的是json文件清单
        Schema::create('page_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('pageId');
            $table->integer('projectId');
            $table->longText('pageContent');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_configs');
    }
};
