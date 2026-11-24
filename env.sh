#!/usr/bin/env bash
# Source this file to setup local environment in terminal:
#   source env.sh
export PATH="/home/aik/.gemini/antigravity/scratch/bin:$PATH"
export PHP_INI_SCAN_DIR=":/home/aik/.gemini/antigravity/scratch/php-ini"
export LD_LIBRARY_PATH="/home/aik/.gemini/antigravity/scratch/php-extensions:$LD_LIBRARY_PATH"
echo "✅ Environment configured with PHP extensions and Composer in PATH."
