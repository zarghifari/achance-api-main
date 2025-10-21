@echo off
REM Course System API Tests Runner (Batch Version)
REM This batch file runs the PowerShell test runner script

setlocal enabledelayedexpansion

echo.
echo =====================================================
echo     COURSE SYSTEM API TESTS RUNNER
echo =====================================================
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if errorlevel 1 (
    echo ERROR: PowerShell is not available or not in PATH
    echo Please ensure PowerShell is installed and accessible
    pause
    exit /b 1
)

REM Get the directory where this batch file is located
set "SCRIPT_DIR=%~dp0"

REM Define the PowerShell script path
set "PS_SCRIPT=%SCRIPT_DIR%run-course-system-tests.ps1"

REM Check if PowerShell script exists
if not exist "%PS_SCRIPT%" (
    echo ERROR: PowerShell script not found at:
    echo %PS_SCRIPT%
    pause
    exit /b 1
)

REM Parse command line arguments
set "BASE_URL=http://localhost:80/api"
set "REPORT_FORMAT=html"
set "INSTALL_NEWMAN="
set "VERBOSE="

:parse_args
if "%~1"=="" goto :run_script
if /i "%~1"=="--base-url" (
    set "BASE_URL=%~2"
    shift
    shift
    goto :parse_args
)
if /i "%~1"=="--report-format" (
    set "REPORT_FORMAT=%~2"
    shift
    shift
    goto :parse_args
)
if /i "%~1"=="--install-newman" (
    set "INSTALL_NEWMAN=-InstallNewman"
    shift
    goto :parse_args
)
if /i "%~1"=="--verbose" (
    set "VERBOSE=-Verbose"
    shift
    goto :parse_args
)
if /i "%~1"=="--help" (
    goto :show_help
)
shift
goto :parse_args

:run_script
echo Running PowerShell script with parameters:
echo - Base URL: %BASE_URL%
echo - Report Format: %REPORT_FORMAT%
if not "%INSTALL_NEWMAN%"=="" echo - Install Newman: Yes
if not "%VERBOSE%"=="" echo - Verbose: Yes
echo.

REM Execute the PowerShell script
powershell -ExecutionPolicy Bypass -File "%PS_SCRIPT%" -BaseUrl "%BASE_URL%" -ReportFormat "%REPORT_FORMAT%" %INSTALL_NEWMAN% %VERBOSE%

if errorlevel 1 (
    echo.
    echo ERROR: Test execution failed
    pause
    exit /b 1
)

echo.
echo SUCCESS: Tests completed successfully!
pause
exit /b 0

:show_help
echo.
echo USAGE: %~nx0 [OPTIONS]
echo.
echo OPTIONS:
echo   --base-url URL        Set the API base URL (default: http://localhost:80/api)
echo   --report-format FMT   Set report format: html, json, junit (default: html)
echo   --install-newman      Install Newman automatically if not found
echo   --verbose             Enable verbose output
echo   --help                Show this help message
echo.
echo EXAMPLES:
echo   %~nx0
echo   %~nx0 --base-url http://localhost:8080/api
echo   %~nx0 --report-format junit --verbose
echo   %~nx0 --install-newman
echo.
pause
exit /b 0
