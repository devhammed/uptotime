#!/bin/bash

set -e

php artisan migrate --force || true
php artisan storage:link || true
php artisan optimize:clear
php artisan optimize

echo "Starting queue worker ..."
php artisan queue:work --tries=3 --sleep=3 &
QUEUE_PID=$!

echo "Starting scheduler ..."
php artisan schedule:work &
SCHEDULER_PID=$!

echo "Starting FrankenPHP ..."
docker-php-entrypoint --config /Caddyfile --adapter caddyfile &
SERVER_PID=$!

cleanup() {
  echo "Stopping all processes ..."

  kill -TERM "$SERVER_PID" "$QUEUE_PID" "$SCHEDULER_PID" 2>/dev/null || true

  wait "$SERVER_PID" 2>/dev/null || true
  wait "$QUEUE_PID" 2>/dev/null || true
  wait "$SCHEDULER_PID" 2>/dev/null || true
}

trap cleanup SIGTERM SIGINT

wait -n "$SERVER_PID" "$QUEUE_PID" "$SCHEDULER_PID"

cleanup