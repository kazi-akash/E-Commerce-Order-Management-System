@echo off
echo ========================================
echo E-Commerce Order Management System
echo Test Suite Runner
echo ========================================
echo.

echo Step 1: Refreshing database...
php artisan migrate:fresh --seed
if %errorlevel% neq 0 (
    echo ERROR: Database refresh failed!
    pause
    exit /b 1
)
echo Database refreshed successfully!
echo.

echo Step 2: Running all tests...
php artisan test
if %errorlevel% neq 0 (
    echo.
    echo Some tests failed. Check the output above.
    pause
    exit /b 1
)

echo.
echo ========================================
echo All tests passed successfully! ✓
echo ========================================
pause
