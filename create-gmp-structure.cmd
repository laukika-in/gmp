@echo off
SETLOCAL ENABLEDELAYEDEXPANSION

REM Set base plugin folder
set "plugin_dir=gold-money-plan"

REM Create folder structure
mkdir "%plugin_dir%"
mkdir "%plugin_dir%\includes"
mkdir "%plugin_dir%\templates"
mkdir "%plugin_dir%\assets"
mkdir "%plugin_dir%\assets\css"
mkdir "%plugin_dir%\assets\js"
mkdir "%plugin_dir%\languages"
mkdir "%plugin_dir%\sql"

REM Create base files
type nul > "%plugin_dir%\gold-money-plan.php"
type nul > "%plugin_dir%\uninstall.php"

REM Includes
type nul > "%plugin_dir%\includes\class-gmp-init.php"
type nul > "%plugin_dir%\includes\class-gmp-subscriber.php"
type nul > "%plugin_dir%\includes\class-gmp-payment.php"
type nul > "%plugin_dir%\includes\class-gmp-admin.php"
type nul > "%plugin_dir%\includes\class-gmp-hooks.php"
type nul > "%plugin_dir%\includes\class-gmp-frontend.php"
type nul > "%plugin_dir%\includes\helper-functions.php"

REM Templates
type nul > "%plugin_dir%\templates\dashboard.php"
type nul > "%plugin_dir%\templates\calculator.php"

REM Assets
type nul > "%plugin_dir%\assets\css\style.css"
type nul > "%plugin_dir%\assets\js\script.js"

REM Languages and SQL
type nul > "%plugin_dir%\languages\gold-money-plan.pot"
type nul > "%plugin_dir%\sql\create-tables.sql"

echo Folder and file structure for Gold Money Plan plugin has been created successfully.
pause
