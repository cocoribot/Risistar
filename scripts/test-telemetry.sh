#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export TELEMETRY_TEST_COPY TELEMETRY_TEST_PASSWORD
TELEMETRY_TEST_PASSWORD="$(openssl rand -hex 24)"
mkdir -p logs
TELEMETRY_TEST_COPY="$(mktemp -d "$PWD/logs/telemetry-test.XXXXXX")"
printf 'Isolated test copy: %s\n' "$TELEMETRY_TEST_COPY"
tar --exclude=.git --exclude=.agents --exclude=.codex --exclude=vendor --exclude=logs --exclude='./cache/*' --exclude='./includes/config.php*' --exclude=includes/config.test.php --exclude=includes/error.log --exclude='./includes/backups/*' -cf - . | tar -xf - -C "$TELEMETRY_TEST_COPY"
compose=(docker compose -f "$PWD/docker/telemetry/compose.yml")
trap '"${compose[@]}" down --volumes' EXIT
"${compose[@]}" up -d --build --wait php
"${compose[@]}" exec -T php composer install --no-interaction --prefer-dist
"${compose[@]}" exec -T php php tests/telemetry/seed.php
"${compose[@]}" exec -T php php tests/telemetry/run.php
"${compose[@]}" exec -T php php tests/telemetry/database.php
"${compose[@]}" exec -T php php tests/telemetry/holdout.php
"${compose[@]}" exec -T php vendor/bin/phpunit --configuration phpunit.xml --fail-on-skipped
"${compose[@]}" exec -T php vendor/bin/phpunit --configuration phpunit.integration.xml --fail-on-skipped
"${compose[@]}" exec -T php php tests/telemetry/seed.php
"${compose[@]}" run --rm browser bash -c 'npm install --no-save --package-lock=false @playwright/test@1.58.2 && node tests/telemetry/routes.cjs && node tests/telemetry/browser.cjs'
"${compose[@]}" exec -T php php tests/telemetry/mutations.php
"${compose[@]}" exec -T php php tests/telemetry/load.php
printf 'Required checks passed. Reports: %s/tests/telemetry/results\n' "$TELEMETRY_TEST_COPY"
