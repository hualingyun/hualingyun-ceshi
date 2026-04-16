@echo off
chcp 65001 >nul
echo 正在停止 PHP 和 Nginx 服务...

taskkill /F /IM php-cgi.exe 2>nul
taskkill /F /IM nginx.exe 2>nul

timeout /t 2 /nobreak >nul

echo 正在启动 Nginx...
start "" "C:\phpStudy\nginx\nginx.exe"

echo 正在启动 PHP-CGI...
start "" "C:\phpStudy\php\php-cgi.exe" -b 127.0.0.1:9000

echo 服务重启完成！
pause
