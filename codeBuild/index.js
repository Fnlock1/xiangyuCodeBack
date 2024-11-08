const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
const cors = require('cors');
app.use(express.json());
app.use(express.static(path.join(__dirname, 'image')));
app.use(cors());

const tagMapping = {
    vue: {
        'view': 'div',
        'text': 'span',
        'button': 'button',
        'image':'img'
        // 其他需要映射的标签
    },
    uniapp: {
        'div': 'view',
        'span': 'text',
        'button': 'button',
        'img' : 'image'
        // 其他需要映射的标签
    }
};

app.post('/api/generateCode', (req, res) => {
    const data = JSON.parse(req.body.components); // 从请求体中获取组件数据
    const langMode = req.body.langMode || 'vue'; // 默认平台是 Vue

    // 检查数据是否有效
    if (!Array.isArray(data)) {
        return res.status(400).json({ message: 'Invalid data format. Expected an array.' });
    }

    // 根据 langMode 选择生成内容和文件名
    let codeContent, fileName, eventHandlerContent, eventFileName;
    if (langMode === 'uniapp') {
        codeContent = generateUniappContent(data);
        eventHandlerContent = generateEventHandlers(data, langMode);
        fileName = 'GeneratedComponent.vue';
        eventFileName = 'methods.js';
    } else { // 默认 vue
        codeContent = generateVueContent(data);
        eventHandlerContent = generateEventHandlers(data, langMode);
        fileName = 'GeneratedComponent.vue';
        eventFileName = 'methods.js';
    }

    // 将生成的文件保存到当前目录
    fs.writeFileSync(path.join(__dirname, fileName), codeContent);
    fs.writeFileSync(path.join(__dirname, eventFileName), eventHandlerContent);

    // 返回成功消息和文件路径
    res.json({
        message: `${langMode} files generated successfully!`,
        code_file_url: `/${fileName}`,
        event_file_url: `/${eventFileName}`
    });
});

// 生成 Vue 文件的内容
function generateVueContent(data) {
    let template = "<template>\n";
    template += generateTemplate(data, 'vue');
    template += "\n</template>\n\n";

    let script = "<script setup>\n";
    const imports = collectImports(data, []);
    script += Array.from(new Set(imports)).join('\n') + "\n";
    const eventImports = collectEventImports(data, 'vue');
    script += Array.from(new Set(eventImports)).join('\n') + "\n";
    script += "</script>\n\n";

    let style = "<style scoped>\n";
    style += "/* 添加你的样式 */\n";
    style += "</style>\n";

    return template + script + style;
}

// 生成 Uniapp 文件的内容
function generateUniappContent(data) {
    let template = "<template>\n";
    template += generateTemplate(data, 'uniapp');
    template += "\n</template>\n\n";

    let script = "<script setup>\n";
    script += `import { ref, reactive } from 'vue';\n`;
    const imports = collectImports(data, []);
    script += Array.from(new Set(imports)).join('\n') + "\n";
    const eventImports = collectEventImports(data, 'uniapp');
    script += Array.from(new Set(eventImports)).join('\n') + "\n";
    script += "</script>\n\n";

    let style = "<style scoped>\n";
    style += "/* Uniapp 样式配置 */\n";
    style += "</style>\n";

    return template + script + style;
}

// 生成事件处理函数
function generateEventHandlers(data, langMode) {
    let handlers = '';
    data.forEach(component => {
        if (component.scriptSetup) {
            component.scriptSetup.forEach(event => {
                const functionName = event.name;
                const eventType = event.type;
                handlers += `export function ${functionName}() { console.log('fn:${functionName}, 触发方式:${eventType}'); }\n`;
            });
        }

        if (component.children) {
            handlers += generateEventHandlers(component.children, langMode);
        }
    });
    return handlers;
}

// 收集组件引入
function collectImports(data, imports) {
    data.forEach(component => {
        if (component.children) {
            collectImports(component.children, imports);
        }
        if (component.name && component.isComponent === true) {
            const importStatement = `import ${component.name} from '../components/${component.name}/index.vue';`;
            if (!imports.includes(importStatement)) {
                imports.push(importStatement);
            }
        }
    });
    return imports;
}

// 生成模板部分（递归）
function generateTemplate(data, langMode) {
    let html = '';
    data.forEach(component => {
        const tag = mapTag(component.name, langMode); // 使用映射函数处理标签
        let classNames = (component.class || []).join(' ');
        // let id = component.id || '';
        // const vFor = component.vFor ? `v-for="item in items" :key="item.id"` : '';

        let props = '';
        let content = '';
        if (component.isComponent === false) {
            content = component.props?.content?.default || '';
        }

        if (component.props) {
            for (const key in component.props) {
                if (key === 'class') {
                    classNames += ` ${component.props[key].default}`;
                } else if (key === 'id') {
                    // id = component.props[key].default;
                } else {
                    if (key !== 'content'){
                        props += `${key}='${component.props[key].default}' `;

                    }
                }
            }
        }

        html += `<${tag} class="${classNames}"   ${props}`;

        if (component.scriptSetup) {
            component.scriptSetup.forEach(event => {
                html += ` @${event.type}="${event.name}()"`;
            });
        }

        html += `>${content}`;

        if (component.children) {
            html += generateTemplate(component.children, langMode);
        }

        html += `</${tag}>\n`;
    });
    return html;
}

// 标签映射函数
function mapTag(tag, langMode) {
    if (tagMapping[langMode] && tagMapping[langMode][tag]) {
        return tagMapping[langMode][tag];
    }
    return tag; // 如果没有映射，则返回原标签
}

// 收集事件处理函数的引入
function collectEventImports(data, langMode) {
    let imports = [];
    data.forEach(component => {
        if (component.scriptSetup) {
            component.scriptSetup.forEach(event => {
                // 生成对应的事件处理函数引入
                const eventFunctionImport = `import { ${event.name} } from './events/${event.name}.js';`;
                if (!imports.includes(eventFunctionImport)) {
                    imports.push(eventFunctionImport);
                }
            });
        }

        if (component.children) {
            imports = imports.concat(collectEventImports(component.children, langMode));
        }
    });
    return imports;
}

// 启动服务
const PORT = process.env.PORT || 8000;
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});
