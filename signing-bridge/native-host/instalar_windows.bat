@echo off
setlocal enabledelayedexpansion
REM ============================================================
REM  Instalador - Puente de firma newHarvest (Windows)
REM  Doble click. No hay que editar ningun archivo a mano.
REM ============================================================

set "CARPETA=%~dp0"
set "RUTA_BAT=%CARPETA%run_host_windows.bat"
set "ARCHIVO_MANIFEST=%CARPETA%com.newharvest.signer.json"
set "EXTENSION_ID=ninkgloiebjnhpimjbeniepmfolpgclb"

REM Reemplazar barras simples por dobles para que el JSON sea valido
set "RUTA_JSON=%RUTA_BAT:\=\\%"

echo Generando configuracion...
(
echo {
echo   "name": "com.newharvest.signer",
echo   "description": "newHarvest - Puente de firma con token criptografico",
echo   "path": "%RUTA_JSON%",
echo   "type": "stdio",
echo   "allowed_origins": [
echo     "chrome-extension://%EXTENSION_ID%/"
echo   ]
echo }
) > "%ARCHIVO_MANIFEST%"

echo Registrando el host nativo en Chrome...
reg add "HKCU\Software\Google\Chrome\NativeMessagingHosts\com.newharvest.signer" /ve /t REG_SZ /d "%ARCHIVO_MANIFEST%" /f >nul

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ================================================
    echo  LISTO. Instalacion completa, sin pasos manuales.
    echo ================================================
    echo.
    echo Solo falta, una vez:
    echo  1. Ir a chrome://extensions
    echo  2. Activar "Modo de desarrollador"
    echo  3. "Cargar descomprimida" y elegir la carpeta:
    echo     %CARPETA%..\extension
    echo.
    echo Despues de eso, cerrar y volver a abrir Chrome.
    echo.
) else (
    echo.
    echo Hubo un error. Proba ejecutar este archivo como administrador.
    echo.
)

pause
