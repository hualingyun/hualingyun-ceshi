let htmlEditor, cssEditor, jsEditor;
let monacoLoaded = false;

const STORAGE_KEY = 'code-editor-data';

function formatHtml(code) {
    let result = '';
    let indent = 0;
    const indentStr = '    ';
    const lines = code.replace(/></g, '>\n<').split('\n');
    
    lines.forEach(line => {
        line = line.trim();
        if (!line) return;
        
        const isClosingTag = line.startsWith('</');
        const isSelfClosing = line.endsWith('/>') || 
            /<(meta|link|br|img|input|hr|area|base|col|embed|source|track|wbr)(\s|>|\/>)/i.test(line);
        
        if (isClosingTag) indent = Math.max(0, indent - 1);
        
        result += indentStr.repeat(indent) + line + '\n';
        
        if (!isClosingTag && !isSelfClosing && line.startsWith('<') && !line.includes('</')) {
            indent++;
        }
    });
    
    return result.trim();
}

function formatCss(code) {
    let result = '';
    let indent = 0;
    const indentStr = '    ';
    
    code = code.replace(/\s+/g, ' ').trim();
    code = code.replace(/\{/g, ' {\n');
    code = code.replace(/\}/g, '\n}\n');
    code = code.replace(/;/g, ';\n');
    
    const lines = code.split('\n');
    
    lines.forEach(line => {
        line = line.trim();
        if (!line) return;
        
        if (line === '}') indent = Math.max(0, indent - 1);
        
        result += indentStr.repeat(indent) + line + '\n';
        
        if (line.endsWith('{')) indent++;
    });
    
    return result.trim();
}

function formatJs(code) {
    let result = '';
    let indent = 0;
    const indentStr = '    ';
    
    code = code.replace(/\{/g, ' {\n');
    code = code.replace(/\}/g, '\n}\n');
    code = code.replace(/;/g, ';\n');
    code = code.replace(/,([^\s])/g, ', $1');
    
    const lines = code.split('\n');
    
    lines.forEach(line => {
        line = line.trim();
        if (!line) return;
        
        const startsWithClosing = line.startsWith('}') || line.startsWith(']');
        const endsWithOpening = line.endsWith('{') || line.endsWith('[');
        const closesSameLine = line.includes('{') && line.includes('}') && line.indexOf('{') < line.indexOf('}');
        
        if (startsWithClosing) indent = Math.max(0, indent - 1);
        
        result += indentStr.repeat(indent) + line + '\n';
        
        if (endsWithOpening && !closesSameLine) indent++;
    });
    
    return result.trim();
}

function formatCode(editor, language) {
    const code = editor.getValue();
    let formatted = code;
    
    try {
        if (language === 'html') {
            formatted = formatHtml(code);
        } else if (language === 'css') {
            formatted = formatCss(code);
        } else if (language === 'javascript') {
            formatted = formatJs(code);
        }
    } catch (e) {
        console.error('格式化失败:', e);
        return;
    }
    
    editor.setValue(formatted);
}

function saveToLocalStorage() {
    const data = {
        html: htmlEditor.getValue(),
        css: cssEditor.getValue(),
        js: jsEditor.getValue()
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function loadFromLocalStorage() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const data = JSON.parse(saved);
            return data;
        } catch (e) {
            return null;
        }
    }
    return null;
}

function addConsoleLog(type, ...args) {
    const consoleOutput = document.getElementById('console-output');
    const div = document.createElement('div');
    div.className = `console-${type}`;
    
    const message = args.map(arg => {
        if (typeof arg === 'object') {
            try {
                return JSON.stringify(arg, null, 2);
            } catch (e) {
                return String(arg);
            }
        }
        return String(arg);
    }).join(' ');
    
    const time = new Date().toLocaleTimeString();
    div.textContent = `[${time}] ${message}`;
    consoleOutput.appendChild(div);
    consoleOutput.scrollTop = consoleOutput.scrollHeight;
}

function clearConsole() {
    document.getElementById('console-output').innerHTML = '';
}

function runCode() {
    clearConsole();
    
    const html = htmlEditor.getValue();
    const css = cssEditor.getValue();
    const js = jsEditor.getValue();
    
    try {
        new Function(js);
    } catch (e) {
        if (e instanceof SyntaxError) {
            addConsoleLog('error', '语法错误:', e.message);
            return;
        }
    }
    
    const consoleInterceptor = `
        (function() {
            const originalLog = console.log;
            const originalWarn = console.warn;
            const originalError = console.error;
            const originalInfo = console.info;
            
            window.onerror = function(msg, url, line, col, error) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'error',
                    args: ['运行时错误:', msg, '行:', line]
                }, '*');
                return true;
            };
            
            window.addEventListener('error', function(e) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'error',
                    args: ['错误:', e.message]
                }, '*');
            });
            
            console.log = function(...args) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'log',
                    args: args
                }, '*');
                originalLog.apply(console, args);
            };
            
            console.warn = function(...args) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'warn',
                    args: args
                }, '*');
                originalWarn.apply(console, args);
            };
            
            console.error = function(...args) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'error',
                    args: args
                }, '*');
                originalError.apply(console, args);
            };
            
            console.info = function(...args) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'info',
                    args: args
                }, '*');
                originalInfo.apply(console, args);
            };
        })();
    `;
    
    let finalHtml = html || '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body></body></html>';
    
    const styleTag = `<style>\n${css}\n</style>`;
    const wrappedJs = `
        window.addEventListener('DOMContentLoaded', function() {
            try {
                ${js}
            } catch (e) {
                window.parent.postMessage({
                    type: 'console',
                    level: 'error',
                    args: ['运行时错误:', e.name + ':', e.message]
                }, '*');
            }
        });
    `;
    const scriptTag = `<script>\n${consoleInterceptor}\n${wrappedJs}\n</script>`;
    
    if (finalHtml.includes('</head>')) {
        finalHtml = finalHtml.replace('</head>', styleTag + '\n</head>');
    } else if (finalHtml.includes('<body>')) {
        finalHtml = finalHtml.replace('<body>', styleTag + '\n<body>');
    } else {
        finalHtml = styleTag + '\n' + finalHtml;
    }
    
    if (finalHtml.includes('</body>')) {
        finalHtml = finalHtml.replace('</body>', scriptTag + '\n</body>');
    } else if (finalHtml.includes('</html>')) {
        finalHtml = finalHtml.replace('</html>', scriptTag + '\n</html>');
    } else {
        finalHtml = finalHtml + '\n' + scriptTag;
    }
    
    const iframe = document.getElementById('preview-frame');
    iframe.srcdoc = finalHtml;
    
    addConsoleLog('info', '代码已运行');
}

function loadTemplate(templateName) {
    if (typeof templates === 'undefined') {
        addConsoleLog('error', '模板数据未加载，请检查 templates.js 文件');
        return;
    }
    
    const template = templates[templateName];
    if (template) {
        htmlEditor.setValue(template.html);
        cssEditor.setValue(template.css);
        jsEditor.setValue(template.js);
        saveToLocalStorage();
        setTimeout(runCode, 100);
    } else {
        addConsoleLog('error', '未找到模板:', templateName);
    }
}

function initMonaco() {
    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
    
    require(['vs/editor/editor.main'], function() {
        monacoLoaded = true;
        
        const editorOptions = {
            theme: 'vs-dark',
            automaticLayout: true,
            minimap: { enabled: false },
            fontSize: 14,
            fontFamily: 'Consolas, Courier New, monospace',
            lineNumbers: 'on',
            folding: true,
            foldingStrategy: 'auto',
            showFoldingControls: 'always',
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            tabSize: 4,
            detectIndentation: true,
            renderWhitespace: 'selection',
            cursorBlinking: 'smooth',
            smoothScrolling: true,
            mouseWheelScrollSensitivity: 1,
            padding: { top: 10, bottom: 10 }
        };
        
        const savedData = loadFromLocalStorage();
        
        htmlEditor = monaco.editor.create(
            document.getElementById('editor-html'),
            {
                ...editorOptions,
                language: 'html',
                value: savedData?.html || '<!DOCTYPE html>\n<html lang="zh-CN">\n<head>\n    <meta charset="UTF-8">\n    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n    <title>示例页面</title>\n</head>\n<body>\n    <h1>Hello, World!</h1>\n    <p>这是一个在线代码编辑器的示例页面。</p>\n    <button id="btn">点击我</button>\n</body>\n</html>'
            }
        );
        
        cssEditor = monaco.editor.create(
            document.getElementById('editor-css'),
            {
                ...editorOptions,
                language: 'css',
                value: savedData?.css || 'body {\n    font-family: Arial, sans-serif;\n    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);\n    min-height: 100vh;\n    display: flex;\n    flex-direction: column;\n    justify-content: center;\n    align-items: center;\n    margin: 0;\n}\n\nh1 {\n    color: white;\n    font-size: 48px;\n    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);\n}\n\np {\n    color: rgba(255, 255, 255, 0.9);\n    font-size: 18px;\n}\n\n#btn {\n    padding: 12px 30px;\n    background: white;\n    color: #667eea;\n    border: none;\n    border-radius: 25px;\n    font-size: 16px;\n    font-weight: bold;\n    cursor: pointer;\n    margin-top: 20px;\n    transition: transform 0.2s;\n}\n\n#btn:hover {\n    transform: scale(1.05);\n}'
            }
        );
        
        jsEditor = monaco.editor.create(
            document.getElementById('editor-js'),
            {
                ...editorOptions,
                language: 'javascript',
                value: savedData?.js || 'const btn = document.getElementById("btn");\n\nbtn.addEventListener("click", function() {\n    alert("按钮被点击了！");\n    console.log("用户点击了按钮");\n    console.log("当前时间:", new Date().toLocaleString());\n});\n\nconsole.log("JavaScript 已加载");'
            }
        );
        
        htmlEditor.onDidChangeModelContent(saveToLocalStorage);
        cssEditor.onDidChangeModelContent(saveToLocalStorage);
        jsEditor.onDidChangeModelContent(saveToLocalStorage);
        
        setTimeout(runCode, 500);
    });
}

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'console') {
        addConsoleLog(event.data.level, ...event.data.args);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    initMonaco();
    
    document.getElementById('btn-run').addEventListener('click', runCode);
    
    document.getElementById('btn-format').addEventListener('click', function() {
        formatCode(htmlEditor, 'html');
        formatCode(cssEditor, 'css');
        formatCode(jsEditor, 'javascript');
        addConsoleLog('info', '代码已格式化');
    });
    
    document.getElementById('btn-clear-console').addEventListener('click', clearConsole);
    
    document.getElementById('template-responsive').addEventListener('click', function() {
        loadTemplate('responsive');
        addConsoleLog('info', '已加载响应式布局模板');
    });
    
    document.getElementById('template-todo').addEventListener('click', function() {
        loadTemplate('todo');
        addConsoleLog('info', '已加载 TodoList 模板');
    });
    
    document.getElementById('template-carousel').addEventListener('click', function() {
        loadTemplate('carousel');
        addConsoleLog('info', '已加载轮播图模板');
    });
});
