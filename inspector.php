<?php
// inspector.php
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $base = realpath(__DIR__);

    function listDir($dir)
    {
        $items = array_diff(scandir($dir), ['.', '..']);
        $result = [];
        foreach ($items as $item) {
            $path = "$dir/$item";
            $result[] = [
                'name' => $item,
                'path' => str_replace(realpath(__DIR__) . '/', '', realpath($path)),
                'type' => is_dir($path) ? 'dir' : 'file'
            ];
        }
        return $result;
    }

    function detectMVC($filePath)
    {
        $type = 'otro';
        if (strpos($filePath, 'views') !== false) $type = 'vista';
        elseif (strpos($filePath, 'controllers') !== false) $type = 'controlador';
        elseif (strpos($filePath, 'models') !== false) $type = 'modelo';

        $base = basename($filePath, '.php');
        $base = preg_replace('/(Controller|Model)$/', '', $base);

        return [
            'type' => $type,
            'suggested_model' => "models/{$base}Model.php",
            'suggested_controller' => "controllers/{$base}Controller.php"
        ];
    }

    function analyzeFile($filePath)
    {
        $absPath = realpath(__DIR__ . '/' . $filePath);
        if (!file_exists($absPath)) return ['error' => 'File not found'];

        $content = file_get_contents($absPath);

        preg_match_all('/\bclass\s+(\w+)/', $content, $classes);
        preg_match_all('/\bfunction\s+(\w+)/', $content, $functions);
        preg_match_all('/(?:include|require)(_once)?\s*\(?[\'"](.+?)[\'"]\)?\s*;/', $content, $matches);
        preg_match_all('/(SELECT|INSERT|UPDATE|DELETE)\s.+?;/is', $content, $queries);

        $includes = $matches[2];
        $sql = array_map('trim', $queries[0]);

        $refers = [];
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
        foreach ($allFiles as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $scan = file_get_contents($file);
                if (strpos($scan, $filePath) !== false && $filePath !== basename($file)) {
                    $refers[] = str_replace(__DIR__ . '/', '', $file);
                }
            }
        }

        $mvc = detectMVC($filePath);

        return [
            'file' => $filePath,
            'content' => htmlspecialchars($content),
            'classes' => $classes[1],
            'functions' => $functions[1],
            'includes' => $includes,
            'sql_queries' => $sql,
            'referenced_by' => $refers,
            'mvc' => $mvc
        ];
    }

    if ($_GET['action'] === 'list') {
        $dir = isset($_GET['dir']) ? realpath(__DIR__ . '/' . $_GET['dir']) : __DIR__;
        if (strpos($dir, __DIR__) !== 0) {
            echo json_encode(['error' => 'Invalid path']);
        } else {
            echo json_encode(listDir($dir));
        }
    } elseif ($_GET['action'] === 'analyze' && isset($_GET['file'])) {
        echo json_encode(analyzeFile($_GET['file']));
    }
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Inspector MVC</title>
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
            display: flex;
            height: 100vh;
        }

        #tree,
        #fileContent,
        #context {
            padding: 10px;
            overflow: auto;
        }

        #tree {
            width: 25%;
            background: #f5f5f5;
            border-right: 1px solid #ccc;
        }

        #fileContent {
            width: 45%;
            border-right: 1px solid #ccc;
            background: #fff;
            font-family: monospace;
            white-space: pre-wrap;
        }

        #context {
            width: 30%;
            background: #f9f9f9;
        }

        ul {
            list-style: none;
            padding-left: 20px;
        }

        li {
            cursor: pointer;
        }

        .folder {
            font-weight: bold;
        }

        h3 {
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .highlight {
            background: yellow;
        }

        .sql {
            background: #eef;
            padding: 4px;
            margin: 4px 0;
            border-left: 3px solid #88f;
            display: block;
        }

        #search {
            margin: 10px;
            width: 90%;
            padding: 5px;
        }
    </style>
</head>

<body>
    <div id="tree">
        <input id="search" placeholder="Buscar archivo, clase o función...">
        <div id="treeContainer"><strong>Cargando...</strong></div>
    </div>
    <div id="fileContent"><em>Seleccione un archivo...</em></div>
    <div id="context"><em>Contexto del archivo...</em></div>

    <script>
        const treeContainer = document.getElementById('treeContainer');
        const fileContent = document.getElementById('fileContent');
        const context = document.getElementById('context');
        const searchBox = document.getElementById('search');
        let lastAnalysis = null;

        function buildTree(base = '', parentElement = null) {
            return fetch(`?action=list&dir=${encodeURIComponent(base)}`)
                .then(res => res.json())
                .then(data => {
                    const ul = document.createElement('ul');
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item.name;
                        li.dataset.path = item.path;
                        li.className = item.type === 'dir' ? 'folder' : '';
                        li.onclick = function(e) {
                            e.stopPropagation();
                            if (item.type === 'dir') {
                                if (li.querySelector('ul')) {
                                    li.removeChild(li.querySelector('ul'));
                                } else {
                                    buildTree(item.path, li);
                                }
                            } else {
                                viewFile(item.path);
                            }
                        };
                        ul.appendChild(li);
                    });
                    if (parentElement) parentElement.appendChild(ul);
                    else {
                        treeContainer.innerHTML = '';
                        treeContainer.appendChild(ul);
                    }
                });
        }

        function highlightSyntax(code) {
            return code
                .replace(/(&lt;\?php|&lt;\?)/g, '<span class="php">$1</span>')
                .replace(/\b(function|class|if|else|elseif|return|foreach|while|try|catch|throw|new|echo|public|private|protected)\b/g, '<span class="keyword">$1</span>')
                .replace(/\/\/.*/g, '<span class="comment">$&</span>')
                .replace(/(&quot;.*?&quot;|'.*?')/g, '<span class="string">$1</span>');
        }

        function viewFile(filePath, highlight = '') {
            fetch(`?action=analyze&file=${encodeURIComponent(filePath)}`)
                .then(res => res.json())
                .then(data => {
                    lastAnalysis = data;

                    let code = highlightSyntax(data.content);
                    if (highlight) {
                        const regex = new RegExp(`\\b(${highlight})\\b`, 'g');
                        code = code.replace(regex, `<span class="highlight">$1</span>`);
                    }

                    fileContent.innerHTML = `<h3>${data.file}</h3><pre>${code}</pre>`;

                    let ctx = `<h2>Contexto</h2>`;
                    ctx += `<p><strong>Tipo:</strong> ${data.mvc.type}</p>`;

                    ctx += `<h3>Clases</h3><ul>${(data.classes.length ? data.classes.map(c => `<li>${c}</li>`).join('') : '<li>Ninguna</li>')}</ul>`;
                    ctx += `<h3>Funciones</h3><ul>${(data.functions.length ? data.functions.map(f => `<li>${f}()</li>`).join('') : '<li>Ninguna</li>')}</ul>`;
                    ctx += `<h3>Includes</h3><ul>${(data.includes.length ? data.includes.map(i => `<li>${i}</li>`).join('') : '<li>Ninguno</li>')}</ul>`;
                    ctx += `<h3>SQL</h3>${(data.sql_queries.length ? data.sql_queries.map(q => `<code class="sql">${q}</code>`).join('') : '<p>No detectadas</p>')}`;
                    ctx += `<h3>Llamado por</h3><ul>${(data.referenced_by.length ? data.referenced_by.map(f => `<li><a href="#" onclick="viewFile('${f}'); return false;">${f}</a></li>`).join('') : '<li>Nadie</li>')}</ul>`;

                    if (data.mvc.type === 'vista') {
                        ctx += `<h3>Relacionados (MVC)</h3><ul>`;
                        ctx += `<li><a href="#" onclick="viewFile('${data.mvc.suggested_controller}'); return false;">${data.mvc.suggested_controller}</a></li>`;
                        ctx += `<li><a href="#" onclick="viewFile('${data.mvc.suggested_model}'); return false;">${data.mvc.suggested_model}</a></li>`;
                        ctx += `</ul>`;
                    }

                    ctx += `<button onclick="generateReport()">📝 Ver informe completo</button>`;
                    context.innerHTML = ctx;
                });
        }

        function generateReport() {
            if (!lastAnalysis) return;
            const data = lastAnalysis;
            const html = `
        <html><head><title>Informe de ${data.file}</title><meta charset="utf-8">
        <style>body { font-family: sans-serif; padding: 20px; } h2 { border-bottom: 1px solid #ccc; }</style>
        </head><body>
        <h1>Informe del archivo: ${data.file}</h1>
        <h2>Tipo: ${data.mvc.type}</h2>
        <h2>Clases</h2><ul>${(data.classes.length ? data.classes.map(c => `<li>${c}</li>`).join('') : '<li>Ninguna</li>')}</ul>
        <h2>Funciones</h2><ul>${(data.functions.length ? data.functions.map(f => `<li>${f}()</li>`).join('') : '<li>Ninguna</li>')}</ul>
        <h2>Includes</h2><ul>${(data.includes.length ? data.includes.map(i => `<li>${i}</li>`).join('') : '<li>Ninguno</li>')}</ul>
        <h2>SQL</h2>${(data.sql_queries.length ? data.sql_queries.map(q => `<pre>${q}</pre>`).join('') : '<p>No detectadas</p>')}
        <h2>Llamado por</h2><ul>${(data.referenced_by.length ? data.referenced_by.map(f => `<li>${f}</li>`).join('') : '<li>Nadie</li>')}</ul>
        ${data.mvc.type === 'vista' ? `<h2>Relacionado (MVC)</h2><ul>
            <li>${data.mvc.suggested_controller}</li>
            <li>${data.mvc.suggested_model}</li></ul>` : ''}
        <h2>Código fuente</h2><pre>${highlightSyntax(data.content)}</pre>
        </body></html>`;

            const blob = new Blob([html], {
                type: 'text/html'
            });
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
        }

        searchBox.addEventListener('input', () => {
            const query = searchBox.value.trim().toLowerCase();
            if (!query) return;

            const results = [];

            const allFiles = document.querySelectorAll('#treeContainer li');
            allFiles.forEach(li => {
                const name = li.textContent.toLowerCase();
                if (name.includes(query)) {
                    results.push({
                        name: li.textContent,
                        path: li.dataset.path
                    });
                }
            });

            if (results.length === 1) {
                viewFile(results[0].path, query);
            }
        });

        buildTree();
    </script>

</body>

</html>