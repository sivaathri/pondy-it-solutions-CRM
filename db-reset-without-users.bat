@echo off
setlocal
echo ========================================================
echo   PONDY IT SOLUTIONS - DB RESET WITHOUT USERS
echo ========================================================
echo.
echo This command will clear all clients, projects, tasks,
echo enquiries, invoices and payments, while KEEPING ALL
echo USER ACCOUNTS, PASSWORDS, AND ROLES INTACT.
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

"%PHP_BIN%" "%~dp0database\reset.php" --without-users

echo.
pause
