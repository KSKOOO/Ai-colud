@echo off
setlocal

set "BASE=%~dp0unpackage"

echo ========================================
echo Clean uni-app build artifacts
echo ========================================
echo.

if not exist "%BASE%" (
    echo Unpackage directory not found: %BASE%
    exit /b 0
)

for %%D in (dist cache release) do (
    if exist "%BASE%\%%D" (
        echo Removing %BASE%\%%D
        rmdir /s /q "%BASE%\%%D"
    ) else (
        echo Skip %BASE%\%%D ^(not found^)
    )
)

echo.
if exist "%BASE%\res" (
    echo Keep %BASE%\res
)
echo.
echo Clean complete.
exit /b 0
