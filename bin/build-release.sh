#!/usr/bin/env bash
set -euo pipefail

SLUG=wp-captcha-shield
VERSION="${1:-dev}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT"

echo "==> Cleaning previous build"
rm -rf build
mkdir -p "build/${SLUG}"

echo "==> Installing production Composer dependencies"
composer install \
  --no-dev \
  --optimize-autoloader \
  --classmap-authoritative \
  --no-interaction

echo "==> Staging files with .distignore"
rsync -av \
  --exclude-from=.distignore \
  --exclude='.git' \
  --exclude='build' \
  ./ "build/${SLUG}/"

echo "==> Creating zip"
cd build
ZIP_NAME="${SLUG}-${VERSION}.zip"
zip -r "../${ZIP_NAME}" "${SLUG}" -x "*.DS_Store" > /dev/null
cd ..

echo ""
echo "==> Done: ${ZIP_NAME}"
ls -lh "${ZIP_NAME}"
echo ""
echo "Top-level entries in zip:"
unzip -l "${ZIP_NAME}" | awk 'NR>3 {print $NF}' | awk -F/ '{print $1"/"$2}' | sort -u | head -n 20

echo ""
echo "==> Restoring dev dependencies"
composer install --no-interaction > /dev/null

echo "==> Reminder: install ${ZIP_NAME} in a clean WP to smoke-test"