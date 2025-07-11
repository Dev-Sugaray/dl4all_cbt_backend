@echo off
REM Start backend (PHP server)
start "Backend" cmd /k "cd /d %~dp0.. && php -S localhost:8000 index.php"

REM Start frontend (Vite dev server)
cd /d %~dp0frontend
start "Frontend" cmd /k "npm run dev"

REM Optional: Return to original directory
cd /d %~dp0
