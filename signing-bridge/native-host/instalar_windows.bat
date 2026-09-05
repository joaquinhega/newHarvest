@echo off
REM ============================================================
REM  Instalador del puente de firma (DEMO) - Windows
REM  Corre este archivo UNA sola vez (doble click) despues de:
REM   1) Instalar Python (python.org) si no lo tenes
REM   2) Instalar las librerias: pip install pypdf reportlab
REM   3) Cargar la extension en Chrome y copiar su ID
REM      (ver instrucciones en README.md)
REM ============================================================

set CARPETA=%~dp0
set ARCHIVO_MANIFEST=%CARPETA%com.newharvest.signer.json

echo.
echo Registrando el host nativo en Chrome...
reg add "HKCU\Software\Google\Chrome\NativeMessagingHosts\com.newharvest.signer" /ve /t REG_SZ /d "%ARCHIVO_MANIFEST%" /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo LISTO. El host quedo registrado.
    echo Recorda haber completado en com.newharvest.signer.json:
    echo   - "path": la ruta a run_host_windows.bat de esta misma carpeta
    echo   - "allowed_origins": el ID de la extension ya cargada en Chrome
    echo.
) else (
    echo.
    echo Hubo un error al registrar. Proba ejecutando este .bat como administrador.
    echo.
)

pause
