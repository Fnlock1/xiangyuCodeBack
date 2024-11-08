<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function generateVueFile(Request $request)
    {
        // 获取传递的 JSON 数据
        $data = $request->input('components');

        // 生成 Vue 文件内容
        $vueContent = $this->generateVueContent($data);

        // 生成事件处理函数文件内容
        $eventHandlerContent = $this->generateEventHandlers($data);

        // 定义 Vue 文件名和事件处理函数文件名
        $vueFileName = 'GeneratedComponent.vue';
        $eventFileName = 'methods.js';

        // 将 Vue 文件和事件处理函数文件保存到 storage 目录
        Storage::disk('local')->put($vueFileName, $vueContent);
        Storage::disk('local')->put($eventFileName, $eventHandlerContent);

        // 返回下载链接或文件路径
        return response()->json([
            'message' => 'Vue and JS files generated successfully!',
            'vue_file_url' => url('/storage/' . $vueFileName),
            'event_file_url' => url('/storage/' . $eventFileName),
        ]);
    }

    // 生成 Vue 文件的核心逻辑
    private function generateVueContent($data)
    {
        $template = "<template>\n";
        $template .= $this->generateTemplate($data);
        $template .= "\n</template>\n\n";

        $script = "<script setup>\n";
        // 动态引入组件
        $imports = $this->collectImports($data, []);
        $script .= implode("\n", array_unique($imports)) . "\n"; // 确保引入不重复

        // 引入动态生成的事件处理函数
        $eventImports = $this->collectEventImports($data, []);
        $script .= implode("\n", array_unique($eventImports)) . "\n"; // 确保引入不重复

        $script .= "</script>\n\n";

        $style = "<style scoped>\n";
        $style .= "/* 添加你的样式 */\n";
        $style .= "</style>\n";

        return $template . $script . $style;
    }

    // 生成事件处理函数的导入语句
    private function collectEventImports($data, $imports)
    {
        foreach ($data as $component) {
            if (!empty($component['scriptSetup'])) {
                foreach ($component['scriptSetup'] as $event) {
                    $functionName = $event['name'];
                    $importStatement = "import { $functionName } from './methods.js';";
                    if (!in_array($importStatement, $imports)) { // 防止重复引入
                        $imports[] = $importStatement;
                    }
                }
            }

            // 递归处理子组件的事件引入
            if (!empty($component['children'])) {
                $imports = $this->collectEventImports($component['children'], $imports);
            }
        }
        return $imports;
    }

    // 生成事件处理函数
    private function generateEventHandlers($data)
    {
        $handlers = '';
        foreach ($data as $component) {
            if (!empty($component['scriptSetup'])) {
                foreach ($component['scriptSetup'] as $event) {
                    $functionName = $event['name'];
                    $eventType = $event['type'];
                    $handlers .= "export function $functionName() { console.log('fn:$functionName, 触发方式:$eventType'); }\n";
                }
            }

            // 递归处理子组件的事件
            if (!empty($component['children'])) {
                $handlers .= $this->generateEventHandlers($component['children']);
            }
        }
        return $handlers;
    }

    // 收集组件引入
    private function collectImports($data, $imports)
    {
        foreach ($data as $component) {
            // 检查当前组件是否有子组件
            if (!empty($component['children'])) {
                $imports = $this->collectImports($component['children'], $imports);
            }

            // 处理子组件引入
            if (!empty($component['name'])) {
                $importStatement = "import {$component['name']} from '../components/{$component['name']}/index.vue';";
                if (!in_array($importStatement, $imports)) { // 检查是否已存在
                    $imports[] = $importStatement;
                }
            }
        }
        return $imports;
    }

    // 生成模板部分（递归）
    private function generateTemplate($data)
    {
        $html = '';
        foreach ($data as $component) {
            // 处理组件类型
            $tag = $component['name'] ?? 'div';

            // 处理类名
            $class = implode(' ', $component['class'] ?? []);
            $id = $component['id'] ?? '';
            $vFor = isset($component['vFor']) ? "v-for=\"item in items\" :key=\"item.id\"" : '';

            // 生成当前节点的 HTML
            $html .= "<$tag class=\"$class\" id=\"$id\" $vFor";

            // 处理事件绑定
            if (!empty($component['scriptSetup'])) {
                foreach ($component['scriptSetup'] as $event) {
                    // 动态绑定事件
                    $html .= " @" . $event['type'] . "=\"$event[name]()\"";
                }
            }

            // 关闭标签
            $html .= ">\n";

            // 如果有子组件，递归生成
            if (!empty($component['children'])) {
                $html .= $this->generateTemplate($component['children']);
            }

            // 关闭标签
            $html .= "</$tag>\n";
        }
        return $html;
    }
}
