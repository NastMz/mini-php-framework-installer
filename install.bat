@echo off
setlocal EnableDelayedExpansion

REM MiniFramework PHP Global Installer Setup Script for Windows
REM This script installs the MiniFramework PHP global installer

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║         MiniFramework PHP Global Installer Setup            ║
echo ║              Quick installation script                       ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP is not installed or not in PATH
    echo.
    echo Please install PHP and add it to your PATH:
    echo   https://www.php.net/downloads.php
    echo.
    pause
    exit /b 1
)

echo ✅ PHP is installed

REM Check PHP version
for /f "tokens=2 delims= " %%a in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%a
echo ℹ️  PHP version: %PHP_VERSION%

REM Check if Composer is installed
composer --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Composer is not installed or not in PATH
    echo.
    echo Please install Composer:
    echo   https://getcomposer.org/download/
    echo.
    pause
    exit /b 1
)

echo ✅ Composer is installed

REM Install the global installer
echo.
echo ℹ️  Installing MiniFramework PHP global installer...
composer global require miniframework/installer --no-interaction

if errorlevel 1 (
    echo ❌ Failed to install global installer
    echo.
    echo You can try manual installation:
    echo   git clone https://github.com/miniframework/installer.git
    echo   cd installer
    echo   composer install
    echo.
    pause
    exit /b 1
)

echo ✅ Global installer installed successfully!

REM Get Composer global bin directory
for /f "tokens=*" %%i in ('composer global config bin-dir --absolute 2^>nul') do set COMPOSER_BIN_DIR=%%i
if "%COMPOSER_BIN_DIR%"=="" set COMPOSER_BIN_DIR=%APPDATA%\Composer\vendor\bin

REM Check if the bin directory is in PATH
echo %PATH% | findstr /C:"%COMPOSER_BIN_DIR%" >nul
if errorlevel 1 (
    echo.
    echo ⚠️  Composer global bin directory is not in PATH
    echo.
    echo Please add the following directory to your PATH environment variable:
    echo   %COMPOSER_BIN_DIR%
    echo.
    echo To add to PATH:
    echo   1. Open System Properties ^(Windows + R, type sysdm.cpl^)
    echo   2. Go to Advanced tab
    echo   3. Click Environment Variables
    echo   4. Edit the PATH variable
    echo   5. Add: %COMPOSER_BIN_DIR%
    echo.
    echo Alternative: You can run the installer directly:
    echo   "%COMPOSER_BIN_DIR%\miniframework.bat" new my-project
    echo.
) else (
    echo ✅ Composer global bin directory is in PATH
)

REM Test the installation
echo.
echo ℹ️  Testing installation...
miniframework version >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Command 'miniframework' not found in PATH
    echo.
    echo The installer was installed but is not available globally.
    echo Please check your PATH configuration above.
    echo.
) else (
    echo ✅ Installation successful!
    echo.
    miniframework version
    echo.
    echo You can now create new projects with:
    echo   miniframework new my-project
    echo.
)

REM Show usage examples
echo.
echo Usage examples:
echo.
echo   REM Create a basic project
echo   miniframework new my-project
echo.
echo   REM Create with custom options
echo   miniframework new my-api --namespace=MyApi --path=C:\www\api
echo.
echo   REM Create a blog
echo   miniframework new my-blog --description="My personal blog"
echo.
echo   REM Show help
echo   miniframework help
echo.

echo ✅ Setup complete! 🎉
echo.
echo Documentation: https://github.com/miniframework/installer
echo Framework docs: https://github.com/nastmz/mini-php-framework
echo.

pause
