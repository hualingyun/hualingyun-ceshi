$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add('http://localhost:8000/')
$listener.Start()
Write-Host 'Server started at http://localhost:8000/'

$mimeTypes = @{
    '.html' = 'text/html';
    '.htm' = 'text/html';
    '.css' = 'text/css';
    '.js' = 'text/javascript';
    '.json' = 'application/json';
    '.png' = 'image/png';
    '.jpg' = 'image/jpeg';
    '.jpeg' = 'image/jpeg';
    '.gif' = 'image/gif';
    '.svg' = 'image/svg+xml';
    '.ico' = 'image/x-icon';
    '.woff' = 'font/woff';
    '.woff2' = 'font/woff2';
    '.ttf' = 'font/ttf';
    '.eot' = 'application/vnd.ms-fontobject';
    '.map' = 'application/json';
}

while ($listener.IsListening) {
    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response
    
    $localPath = $request.Url.LocalPath.TrimStart('/')
    if ([string]::IsNullOrWhiteSpace($localPath)) {
        $localPath = 'index.html'
    }
    
    $fullPath = Join-Path (Get-Location) $localPath
    
    if (Test-Path $fullPath -PathType Leaf) {
        $extension = [System.IO.Path]::GetExtension($fullPath).ToLowerInvariant()
        if ($mimeTypes.ContainsKey($extension)) {
            $response.ContentType = $mimeTypes[$extension]
        } else {
            $response.ContentType = 'application/octet-stream'
        }
        
        $content = [System.IO.File]::ReadAllBytes($fullPath)
        $response.ContentLength64 = $content.Length
        $response.OutputStream.Write($content, 0, $content.Length)
        Write-Host "200 - $localPath"
    } else {
        $response.StatusCode = 404
        $notFound = [System.Text.Encoding]::UTF8.GetBytes('404 - Not Found')
        $response.OutputStream.Write($notFound, 0, $notFound.Length)
        Write-Host "404 - $localPath"
    }
    
    $response.Close()
}

$listener.Stop()
