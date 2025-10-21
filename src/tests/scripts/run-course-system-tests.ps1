# Course System API Tests Runner
# This script runs the complete course system API tests using Newman (Postman CLI)

param(
    [string]$BaseUrl = "http://localhost:80/api",
    [string]$ReportFormat = "html",
    [string]$OutputDir = "test-results",
    [switch]$Verbose,
    [switch]$InstallNewman
)

# Colors for output
$Green = "Green"
$Red = "Red"
$Yellow = "Yellow"
$Cyan = "Cyan"

function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Show-Header {
    Write-ColorOutput "`n=====================================================" $Cyan
    Write-ColorOutput "    COURSE SYSTEM API TESTS RUNNER" $Cyan
    Write-ColorOutput "=====================================================" $Cyan
    Write-ColorOutput "Base URL: $BaseUrl" $Yellow
    Write-ColorOutput "Report Format: $ReportFormat" $Yellow
    Write-ColorOutput "Output Directory: $OutputDir" $Yellow
    Write-ColorOutput "=====================================================" $Cyan
}

function Test-Prerequisites {
    Write-ColorOutput "`n[1/6] Checking prerequisites..." $Yellow
    
    # Check if Node.js is installed
    try {
        $nodeVersion = node --version 2>$null
        Write-ColorOutput "✓ Node.js is installed: $nodeVersion" $Green
    }
    catch {
        Write-ColorOutput "✗ Node.js is not installed. Please install Node.js first." $Red
        Write-ColorOutput "  Download from: https://nodejs.org/" $Yellow
        exit 1
    }
    
    # Check if Newman is installed
    try {
        $newmanVersion = newman --version 2>$null
        Write-ColorOutput "✓ Newman is installed: $newmanVersion" $Green
    }
    catch {
        if ($InstallNewman) {
            Write-ColorOutput "! Newman not found. Installing Newman..." $Yellow
            npm install -g newman
            npm install -g newman-reporter-html
            Write-ColorOutput "✓ Newman installed successfully" $Green
        }
        else {
            Write-ColorOutput "✗ Newman is not installed. Use -InstallNewman flag to install automatically." $Red
            Write-ColorOutput "  Or install manually: npm install -g newman newman-reporter-html" $Yellow
            exit 1
        }
    }
    
    # Check if collection file exists
    $collectionPath = Join-Path $PSScriptRoot "Course_System_Complete_Tests.postman_collection.json"
    if (Test-Path $collectionPath) {
        Write-ColorOutput "✓ Collection file found: $collectionPath" $Green
    }
    else {
        Write-ColorOutput "✗ Collection file not found: $collectionPath" $Red
        exit 1
    }
    
    # Check if environment file exists
    $environmentPath = Join-Path $PSScriptRoot "Course_System_Test_Environment.postman_environment.json"
    if (Test-Path $environmentPath) {
        Write-ColorOutput "✓ Environment file found: $environmentPath" $Green
    }
    else {
        Write-ColorOutput "✗ Environment file not found: $environmentPath" $Red
        exit 1
    }
}

function Test-APIConnection {
    Write-ColorOutput "`n[2/6] Testing API connection..." $Yellow
    
    try {
        $response = Invoke-WebRequest -Uri "$BaseUrl/user" -Method GET -TimeoutSec 10 2>$null
        if ($response.StatusCode -eq 401) {
            Write-ColorOutput "✓ API is accessible (401 Unauthorized is expected without auth)" $Green
        }
        else {
            Write-ColorOutput "✓ API is accessible (Status: $($response.StatusCode))" $Green
        }
    }
    catch {
        Write-ColorOutput "✗ Cannot connect to API at $BaseUrl" $Red
        Write-ColorOutput "  Please ensure the API server is running" $Yellow
        Write-ColorOutput "  Error: $($_.Exception.Message)" $Red
        
        $continue = Read-Host "Continue anyway? (y/N)"
        if ($continue -ne "y" -and $continue -ne "Y") {
            exit 1
        }
    }
}

function Prepare-OutputDirectory {
    Write-ColorOutput "`n[3/6] Preparing output directory..." $Yellow
    
    if (Test-Path $OutputDir) {
        Write-ColorOutput "! Output directory exists. Cleaning up..." $Yellow
        Remove-Item "$OutputDir/*" -Recurse -Force -ErrorAction SilentlyContinue
    }
    else {
        New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
    }
    
    Write-ColorOutput "✓ Output directory prepared: $OutputDir" $Green
}

function Update-Environment {
    Write-ColorOutput "`n[4/6] Updating environment configuration..." $Yellow
    
    $environmentPath = Join-Path $PSScriptRoot "Course_System_Test_Environment.postman_environment.json"
    $env = Get-Content $environmentPath | ConvertFrom-Json
    
    # Update base URL
    $baseUrlVar = $env.values | Where-Object { $_.key -eq "baseUrl" }
    if ($baseUrlVar) {
        $baseUrlVar.value = $BaseUrl
        Write-ColorOutput "✓ Updated base URL to: $BaseUrl" $Green
    }
    
    # Save updated environment
    $tempEnvPath = Join-Path $OutputDir "temp_environment.json"
    $env | ConvertTo-Json -Depth 10 | Set-Content $tempEnvPath
    
    return $tempEnvPath
}

function Run-Tests {
    param([string]$EnvironmentPath)
    
    Write-ColorOutput "`n[5/6] Running Course System API tests..." $Yellow
    
    $collectionPath = Join-Path $PSScriptRoot "Course_System_Complete_Tests.postman_collection.json"
    $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
    $reportFile = Join-Path $OutputDir "course-system-test-report-$timestamp"
    
    # Build Newman command
    $newmanArgs = @(
        "run", $collectionPath,
        "--environment", $EnvironmentPath,
        "--reporters", "cli,json,$ReportFormat",
        "--reporter-json-export", "$reportFile.json"
    )
    
    if ($ReportFormat -eq "html") {
        $newmanArgs += "--reporter-html-export", "$reportFile.html"
    }
    elseif ($ReportFormat -eq "junit") {
        $newmanArgs += "--reporter-junit-export", "$reportFile.xml"
    }
    
    if ($Verbose) {
        $newmanArgs += "--verbose"
    }
    
    # Add additional options for better output
    $newmanArgs += @(
        "--delay-request", "100",
        "--timeout-request", "30000",
        "--disable-unicode"
    )
    
    Write-ColorOutput "Running: newman $($newmanArgs -join ' ')" $Cyan
    
    # Run the tests
    $startTime = Get-Date
    & newman @newmanArgs
    $endTime = Get-Date
    $duration = $endTime - $startTime
    
    Write-ColorOutput "`n✓ Tests completed in $($duration.TotalSeconds.ToString('F2')) seconds" $Green
    Write-ColorOutput "📊 Reports generated in: $OutputDir" $Cyan
    
    return $reportFile
}

function Show-Results {
    param([string]$ReportFile)
    
    Write-ColorOutput "`n[6/6] Test Results Summary" $Yellow
    
    # Try to read JSON report for detailed statistics
    $jsonReportPath = "$ReportFile.json"
    if (Test-Path $jsonReportPath) {
        try {
            $report = Get-Content $jsonReportPath | ConvertFrom-Json
            $run = $report.run
            
            Write-ColorOutput "`n📈 DETAILED STATISTICS:" $Cyan
            Write-ColorOutput "   Total Requests: $($run.stats.requests.total)" $White
            Write-ColorOutput "   Failed Requests: $($run.stats.requests.failed)" $(if ($run.stats.requests.failed -eq 0) { $Green } else { $Red })
            Write-ColorOutput "   Total Assertions: $($run.stats.assertions.total)" $White
            Write-ColorOutput "   Failed Assertions: $($run.stats.assertions.failed)" $(if ($run.stats.assertions.failed -eq 0) { $Green } else { $Red })
            Write-ColorOutput "   Average Response Time: $($run.timings.responseAverage.ToString('F0'))ms" $White
            
            if ($run.stats.assertions.failed -eq 0 -and $run.stats.requests.failed -eq 0) {
                Write-ColorOutput "`n🎉 ALL TESTS PASSED! 🎉" $Green
            }
            else {
                Write-ColorOutput "`n⚠️  SOME TESTS FAILED" $Red
                
                # Show failed requests
                if ($run.executions) {
                    $failedExecutions = $run.executions | Where-Object { $_.response.code -ge 400 -or $_.assertions.failures.Count -gt 0 }
                    if ($failedExecutions) {
                        Write-ColorOutput "`n❌ FAILED REQUESTS:" $Red
                        foreach ($execution in $failedExecutions) {
                            Write-ColorOutput "   • $($execution.item.name)" $Red
                            if ($execution.response) {
                                Write-ColorOutput "     Status: $($execution.response.code) $($execution.response.status)" $Yellow
                            }
                        }
                    }
                }
            }
        }
        catch {
            Write-ColorOutput "! Could not parse detailed results from JSON report" $Yellow
        }
    }
    
    Write-ColorOutput "`n📁 Generated Reports:" $Cyan
    Get-ChildItem $OutputDir -File | ForEach-Object {
        Write-ColorOutput "   • $($_.Name)" $White
    }
    
    # Try to open HTML report if available
    $htmlReportPath = "$ReportFile.html"
    if ($ReportFormat -eq "html" -and (Test-Path $htmlReportPath)) {
        $openReport = Read-Host "`nOpen HTML report in browser? (Y/n)"
        if ($openReport -ne "n" -and $openReport -ne "N") {
            Start-Process $htmlReportPath
        }
    }
}

function Show-Footer {
    Write-ColorOutput "`n=====================================================" $Cyan
    Write-ColorOutput "              TESTS COMPLETED" $Cyan
    Write-ColorOutput "=====================================================" $Cyan
    Write-ColorOutput "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" $Yellow
    Write-ColorOutput "=====================================================" $Cyan
}

# Main execution
try {
    Show-Header
    Test-Prerequisites
    Test-APIConnection
    Prepare-OutputDirectory
    $envPath = Update-Environment
    $reportFile = Run-Tests -EnvironmentPath $envPath
    Show-Results -ReportFile $reportFile
    Show-Footer
}
catch {
    Write-ColorOutput "`n💥 Script execution failed!" $Red
    Write-ColorOutput "Error: $($_.Exception.Message)" $Red
    exit 1
}

Write-ColorOutput "`nScript completed successfully! 🚀" $Green
