@echo off
echo STARTING RELEASE BUILD...

:: Clean
if exist dist rmdir /s /q dist
mkdir dist

:: Build Frontend
call npm run build

:: Copy Source Code
xcopy src dist\src /E /I /Q
xcopy config dist\config /E /I /Q
xcopy templates dist\templates /E /I /Q
xcopy translations dist\translations /E /I /Q
xcopy public dist\public /E /I /Q
xcopy public\build\.vite dist\public\build\.vite /E /I /Y
copy .env.example dist\.env.example
copy composer.json dist\composer.json
copy composer.lock dist\composer.lock

:: Install Prod Dependencies
cd dist
call composer install --no-dev --optimize-autoloader --no-scripts
cd ..

echo DONE. Upload contents of 'dist' folder.
pause