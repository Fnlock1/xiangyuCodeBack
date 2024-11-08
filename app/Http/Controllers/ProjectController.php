<?php

namespace App\Http\Controllers;

use App\Models\pageConfig;
use App\Models\project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    //
    public function index() {
        $page = request('page', 1);  // 获取当前页码，默认为1
        $size = request('size', 15); // 获取每页的记录数，默认为15
        $data = project::paginate($size, ['*'], 'page', $page)->toArray(); // 使用分页，指定每页大小和当前页
        return $this->success($data);
    }

    public function count()
    {
        $count = project::total();
        return $this->success($count);
    }

    public function getProjectConfigById(Request $request)
    {
        $pageId = $request->get('pageId', );
        $projectId = $request->get('projectId');
        $data = pageConfig::where([
            'pageId' => $pageId,
            'projectId' => $projectId
        ])->first();
        if (empty($data)) {
           $create = pageConfig::create([
               'pageId' => $pageId,
               'projectId' => $projectId,
               'pageContent' => rawurlencode('[]')
           ]);
           return $this->success($create);
        }
        return $this->success($data);
    }


    public function updateProjectConfig(Request $request)
    {
        $pageId = $request->post('pageId');
        $projectId = $request->post('projectId');
        $v =  pageConfig::where([
          'pageId' => $pageId,
          'projectId' => $projectId
        ])->first();
        if (empty($v)) {
            $create = pageConfig::create([
                'pageId' => $pageId,
                'projectId' => $projectId,
                'pageContent' => rawurlencode('[]')
            ]);
            return $this->success($create);
        }else{
            $v->pageContent = $request->post('pageContent');
            $v->save();
            return $this->success($v);
        }
    }
}
