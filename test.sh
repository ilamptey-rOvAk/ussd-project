#!/usr/bin/env bash
# Helper script to run test suite with correct extensions
export PATH="/home/aik/.gemini/antigravity/scratch/bin:$PATH"
export PHP_INI_SCAN_DIR=":/home/aik/.gemini/antigravity/scratch/php-ini"
export LD_LIBRARY_PATH="/home/aik/.gemini/antigravity/scratch/php-extensions:$LD_LIBRARY_PATH"
cd "$(dirname "$0")"
php artisan test "$@"
