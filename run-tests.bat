@echo off
REM Run PHPUnit tests for YetAnotherPlugin
REM 
REM Usage:
REM   run-tests.bat                  - Run all tests
REM   run-tests.bat --filter test    - Run specific test
REM   run-tests.bat --coverage       - Generate coverage report

setlocal enabledelayedexpansion

set PLUGIN_DIR=%~dp0
set PLUGIN_DIR=%PLUGIN_DIR:~0,-1%

echo Running YAP Unit Tests...
echo ========================

REM Check if vendor/bin/phpunit exists
if exist "%PLUGIN_DIR%\vendor\bin\phpunit.bat" (
    call "%PLUGIN_DIR%\vendor\bin\phpunit.bat" --configuration "%PLUGIN_DIR%\phpunit.xml" %*
) else if exist "%PLUGIN_DIR%\vendor\bin\phpunit" (
    php "%PLUGIN_DIR%\vendor\bin\phpunit" --configuration "%PLUGIN_DIR%\phpunit.xml" %*
) else (
    echo PHPUnit not found. Install with: composer install
    exit /b 1
)

echo.
echo Test execution complete.
