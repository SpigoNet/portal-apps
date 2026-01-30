call php artisan view:clear
call php artisan cache:clear
call php artisan config:cache
call composer install --optimize-autoloader --no-dev
call npm run build
del "C:\laragon\www\deploy-portal.zip"

"C:\Program Files\7-Zip\7z.exe" a -tzip "C:\laragon\www\deploy-portal.zip" "C:\laragon\www\portal-apps\*" -xr!"C:\laragon\www\portal-apps\.git\" -xr!"C:\laragon\www\portal-apps\.idea\" -x!"C:\laragon\www\portal-apps\.env" -xr!"C:\laragon\www\portal-apps\public\hot\" -xr!"C:\laragon\www\portal-apps\node_modules\" -xr!"C:\laragon\www\portal-apps\storage\" -xr!"C:\laragon\www\portal-apps\tests\" -x!"C:\laragon\www\portal-apps\.editorconfig" -x!"C:\laragon\www\portal-apps\.gitignore" -x!"C:\laragon\www\portal-apps\.env.example" -x!"C:\laragon\www\portal-apps\phpunit.xml" -x!"C:\laragon\www\portal-apps\postcss.config.js" -x!"C:\laragon\www\portal-apps\README.md" -x!"C:\laragon\www\portal-apps\package.json" -x!"C:\laragon\www\portal-apps\package-lock.json" -x!"C:\laragon\www\portal-apps\vite.config.js" -x!"C:\laragon\www\portal-apps\tailwind.config.js" -x!"C:\laragon\www\portal-apps\build.bat" -x!"C:\laragon\www\portal-apps\public\hot"

echo Deployment package created at C:\laragon\www\deploy-portal.zip

