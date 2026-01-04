@echo off
REM Regenerate Composer Autoload for Windows
REM Run this script to fix VSCode warnings about missing classes

echo Regenerating Composer autoload...

cd /d "%~dp0"

REM Dump autoload
composer dump-autoload

echo.
echo ✅ Autoload regenerated!
echo.
echo If VSCode still shows errors:
echo 1. Restart VSCode/Intelephense
echo 2. Run: Ctrl+Shift+P -^> 'PHP: Restart PHP Language Server'
echo 3. Clear cache: del /s /q storage\framework\cache\*
echo.
pause
