<?php

namespace App\Http\Controllers;

use App\Models\page;
use App\Models\pageConfig;
use Illuminate\Http\Request;

class PageController extends Controller
{
    //
    public function getPageById(Request $request)
    {
        $id = $request->id;
        $data = Page::where([
            'projectId' => $id
        ])->get();
        return $this->success($data);
    }


    // 添加页面
    public function addPage(Request $request)
    {
        $name = $request->name;
        $projectId = $request->projectId;
        $data = Page::create([
            'name' => $name,
            'projectId' => $projectId
        ]);
        return $this->success($data);
    }

    // 根据id 获取 详细内容
    public function getByIdPageConfig(Request $request)
    {
        $id = $request->id;
        $projectId = $request->ProjectId;
        $data = pageConfig::firstWhere([
            'pageId' => $id,
            'projectId' => $projectId,
        ]);
        if (!$data){
            return $this->success([
                'message' => '内容不存在'
            ],500);
        }else{
            return $this->success($data);
        }
    }

    public function deletePage(Request $request)
    {
        $id = $request->id;
        page::destroy($id);
        return $this->success('删除成功');
    }


}
