@echo off
setlocal
echo ========================================================
echo   PONDY IT SOLUTIONS - FULL FACTORY RESET WITH USERS
echo ========================================================
echo.
echo WARNING: This will perform a complete factory wipe.
echo All users, projects, tasks, clients and data will be cleared.
echo Default Super Admin account will be restored: admin / admin123
echo.

set PHP_BIN=php
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_BIN=C:\xampp\php\php.exe
    ) else (
        echo [ERROR] PHP was not found in PATH or at C:\xampp\php\php.exe
        pause
        exit /b 1
    )
)

"%PHP_BIN%" "%~dp0database\reset.php" --with-users

echo.
pause

