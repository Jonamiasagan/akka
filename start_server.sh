#!/usr/bin/env bash
cd "$(dirname "$0")"
echo "Starting Hindi to Kruti Dev Web Server at http://localhost:8000 ..."
# Open default browser on macOS
open "http://localhost:8000" 2>/dev/null || true
# Run PHP built-in web server
php -S 127.0.0.1:8000
