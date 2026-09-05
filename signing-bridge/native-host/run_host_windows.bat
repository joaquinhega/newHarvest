@echo off
REM Este archivo existe porque en Windows, Chrome solo puede invocar un
REM ejecutable o .bat directamente como Native Messaging Host — no puede
REM apuntar a un .py sin este intermediario. Simplemente reenvía todo a
REM Python sin tocar nada (stdin/stdout pasan tal cual).
python "%~dp0host.py"
