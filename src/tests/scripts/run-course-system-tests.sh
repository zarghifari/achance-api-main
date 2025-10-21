#!/bin/bash
# Course System API Tests - Quick Start Script for Linux/Mac

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Default values
BASE_URL="http://localhost:80/api"
REPORT_FORMAT="html"
OUTPUT_DIR="test-results"
INSTALL_NEWMAN=false
VERBOSE=false

# Function to print colored output
print_color() {
    printf "${2}${1}${NC}\n"
}

show_header() {
    echo
    print_color "=====================================================" $CYAN
    print_color "    COURSE SYSTEM API TESTS RUNNER" $CYAN
    print_color "=====================================================" $CYAN
    print_color "Base URL: $BASE_URL" $YELLOW
    print_color "Report Format: $REPORT_FORMAT" $YELLOW
    print_color "Output Directory: $OUTPUT_DIR" $YELLOW
    print_color "=====================================================" $CYAN
}

check_prerequisites() {
    print_color "\n[1/6] Checking prerequisites..." $YELLOW
    
    # Check if Node.js is installed
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_color "✓ Node.js is installed: $NODE_VERSION" $GREEN
    else
        print_color "✗ Node.js is not installed. Please install Node.js first." $RED
        print_color "  Download from: https://nodejs.org/" $YELLOW
        exit 1
    fi
    
    # Check if Newman is installed
    if command -v newman &> /dev/null; then
        NEWMAN_VERSION=$(newman --version)
        print_color "✓ Newman is installed: $NEWMAN_VERSION" $GREEN
    else
        if [ "$INSTALL_NEWMAN" = true ]; then
            print_color "! Newman not found. Installing Newman..." $YELLOW
            npm install -g newman newman-reporter-html
            print_color "✓ Newman installed successfully" $GREEN
        else
            print_color "✗ Newman is not installed. Use --install-newman flag to install automatically." $RED
            print_color "  Or install manually: npm install -g newman newman-reporter-html" $YELLOW
            exit 1
        fi
    fi
    
    # Get script directory
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
    POSTMAN_DIR="$(dirname "$SCRIPT_DIR")/postman"
    
    # Check if collection file exists
    COLLECTION_PATH="$POSTMAN_DIR/Course_System_Complete_Tests.postman_collection.json"
    if [ -f "$COLLECTION_PATH" ]; then
        print_color "✓ Collection file found: $COLLECTION_PATH" $GREEN
    else
        print_color "✗ Collection file not found: $COLLECTION_PATH" $RED
        exit 1
    fi
    
    # Check if environment file exists
    ENVIRONMENT_PATH="$POSTMAN_DIR/Course_System_Test_Environment.postman_environment.json"
    if [ -f "$ENVIRONMENT_PATH" ]; then
        print_color "✓ Environment file found: $ENVIRONMENT_PATH" $GREEN
    else
        print_color "✗ Environment file not found: $ENVIRONMENT_PATH" $RED
        exit 1
    fi
}

test_api_connection() {
    print_color "\n[2/6] Testing API connection..." $YELLOW
    
    if curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/user" | grep -q "401"; then
        print_color "✓ API is accessible (401 Unauthorized is expected without auth)" $GREEN
    else
        print_color "✗ Cannot connect to API at $BASE_URL" $RED
        print_color "  Please ensure the API server is running" $YELLOW
        
        read -p "Continue anyway? (y/N): " continue_choice
        if [[ ! "$continue_choice" =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

prepare_output_directory() {
    print_color "\n[3/6] Preparing output directory..." $YELLOW
    
    if [ -d "$OUTPUT_DIR" ]; then
        print_color "! Output directory exists. Cleaning up..." $YELLOW
        rm -rf "$OUTPUT_DIR"/*
    else
        mkdir -p "$OUTPUT_DIR"
    fi
    
    print_color "✓ Output directory prepared: $OUTPUT_DIR" $GREEN
}

update_environment() {
    print_color "\n[4/6] Updating environment configuration..." $YELLOW
    
    # Create temporary environment file with updated base URL
    TEMP_ENV_PATH="$OUTPUT_DIR/temp_environment.json"
    sed "s|\"http://localhost:80/api\"|\"$BASE_URL\"|g" "$ENVIRONMENT_PATH" > "$TEMP_ENV_PATH"
    
    print_color "✓ Updated base URL to: $BASE_URL" $GREEN
    echo "$TEMP_ENV_PATH"
}

run_tests() {
    local env_path=$1
    
    print_color "\n[5/6] Running Course System API tests..." $YELLOW
    
    TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
    REPORT_FILE="$OUTPUT_DIR/course-system-test-report-$TIMESTAMP"
    
    # Build Newman command
    NEWMAN_CMD="newman run \"$COLLECTION_PATH\" --environment \"$env_path\" --reporters cli,json,$REPORT_FORMAT --reporter-json-export \"$REPORT_FILE.json\""
    
    if [ "$REPORT_FORMAT" = "html" ]; then
        NEWMAN_CMD="$NEWMAN_CMD --reporter-html-export \"$REPORT_FILE.html\""
    elif [ "$REPORT_FORMAT" = "junit" ]; then
        NEWMAN_CMD="$NEWMAN_CMD --reporter-junit-export \"$REPORT_FILE.xml\""
    fi
    
    if [ "$VERBOSE" = true ]; then
        NEWMAN_CMD="$NEWMAN_CMD --verbose"
    fi
    
    # Add additional options
    NEWMAN_CMD="$NEWMAN_CMD --delay-request 100 --timeout-request 30000 --disable-unicode"
    
    print_color "Running: $NEWMAN_CMD" $CYAN
    
    # Run the tests
    START_TIME=$(date +%s)
    eval $NEWMAN_CMD
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    print_color "\n✓ Tests completed in ${DURATION} seconds" $GREEN
    print_color "📊 Reports generated in: $OUTPUT_DIR" $CYAN
    
    echo "$REPORT_FILE"
}

show_results() {
    local report_file=$1
    
    print_color "\n[6/6] Test Results Summary" $YELLOW
    
    # Try to read JSON report for detailed statistics
    JSON_REPORT_PATH="$report_file.json"
    if [ -f "$JSON_REPORT_PATH" ]; then
        print_color "\n📈 DETAILED STATISTICS:" $CYAN
        
        # Extract statistics using jq if available, otherwise use basic parsing
        if command -v jq &> /dev/null; then
            TOTAL_REQUESTS=$(jq '.run.stats.requests.total' "$JSON_REPORT_PATH")
            FAILED_REQUESTS=$(jq '.run.stats.requests.failed' "$JSON_REPORT_PATH")
            TOTAL_ASSERTIONS=$(jq '.run.stats.assertions.total' "$JSON_REPORT_PATH")
            FAILED_ASSERTIONS=$(jq '.run.stats.assertions.failed' "$JSON_REPORT_PATH")
            AVG_RESPONSE_TIME=$(jq '.run.timings.responseAverage' "$JSON_REPORT_PATH")
            
            print_color "   Total Requests: $TOTAL_REQUESTS" $NC
            if [ "$FAILED_REQUESTS" -eq 0 ]; then
                print_color "   Failed Requests: $FAILED_REQUESTS" $GREEN
            else
                print_color "   Failed Requests: $FAILED_REQUESTS" $RED
            fi
            print_color "   Total Assertions: $TOTAL_ASSERTIONS" $NC
            if [ "$FAILED_ASSERTIONS" -eq 0 ]; then
                print_color "   Failed Assertions: $FAILED_ASSERTIONS" $GREEN
            else
                print_color "   Failed Assertions: $FAILED_ASSERTIONS" $RED
            fi
            print_color "   Average Response Time: ${AVG_RESPONSE_TIME}ms" $NC
            
            if [ "$FAILED_ASSERTIONS" -eq 0 ] && [ "$FAILED_REQUESTS" -eq 0 ]; then
                print_color "\n🎉 ALL TESTS PASSED! 🎉" $GREEN
            else
                print_color "\n⚠️  SOME TESTS FAILED" $RED
            fi
        else
            print_color "   (Install 'jq' for detailed statistics)" $YELLOW
        fi
    fi
    
    print_color "\n📁 Generated Reports:" $CYAN
    ls -la "$OUTPUT_DIR"
    
    # Try to open HTML report if available
    HTML_REPORT_PATH="$report_file.html"
    if [ "$REPORT_FORMAT" = "html" ] && [ -f "$HTML_REPORT_PATH" ]; then
        read -p $'\nOpen HTML report in browser? (Y/n): ' open_choice
        if [[ ! "$open_choice" =~ ^[Nn]$ ]]; then
            if command -v xdg-open &> /dev/null; then
                xdg-open "$HTML_REPORT_PATH"
            elif command -v open &> /dev/null; then
                open "$HTML_REPORT_PATH"
            else
                print_color "HTML report available at: $HTML_REPORT_PATH" $CYAN
            fi
        fi
    fi
}

show_footer() {
    print_color "\n=====================================================" $CYAN
    print_color "              TESTS COMPLETED" $CYAN
    print_color "=====================================================" $CYAN
    print_color "Timestamp: $(date)" $YELLOW
    print_color "=====================================================" $CYAN
}

show_help() {
    cat << EOF

USAGE: $0 [OPTIONS]

OPTIONS:
  --base-url URL        Set the API base URL (default: http://localhost:80/api)
  --report-format FMT   Set report format: html, json, junit (default: html)
  --install-newman      Install Newman automatically if not found
  --verbose             Enable verbose output
  --help                Show this help message

EXAMPLES:
  $0
  $0 --base-url http://localhost:8080/api
  $0 --report-format junit --verbose
  $0 --install-newman

EOF
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --base-url)
            BASE_URL="$2"
            shift 2
            ;;
        --report-format)
            REPORT_FORMAT="$2"
            shift 2
            ;;
        --install-newman)
            INSTALL_NEWMAN=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            print_color "Unknown option: $1" $RED
            show_help
            exit 1
            ;;
    esac
done

# Main execution
main() {
    show_header
    check_prerequisites
    test_api_connection
    prepare_output_directory
    env_path=$(update_environment)
    report_file=$(run_tests "$env_path")
    show_results "$report_file"
    show_footer
    
    print_color "\nScript completed successfully! 🚀" $GREEN
}

# Run main function
main
