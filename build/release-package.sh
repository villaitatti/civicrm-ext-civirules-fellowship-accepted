#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_DIR="${ROOT_DIR}/dist"
INFO_XML="${ROOT_DIR}/info.xml"

if ! command -v php >/dev/null 2>&1; then
  echo "php is required to read info.xml" >&2
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "rsync is required to build the package" >&2
  exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
  echo "zip is required to build the package" >&2
  exit 1
fi

EXT_KEY="$(php -r '$xml = simplexml_load_file($argv[1]); if (!$xml) { fwrite(STDERR, "Could not read info.xml\n"); exit(1);} echo trim((string) $xml["key"]);' "$INFO_XML")"
XML_VERSION="$(php -r '$xml = simplexml_load_file($argv[1]); if (!$xml) { fwrite(STDERR, "Could not read info.xml\n"); exit(1);} echo trim((string) $xml->version);' "$INFO_XML")"

if [[ -z "$EXT_KEY" ]]; then
  echo "Extension key in info.xml is empty" >&2
  exit 1
fi

if [[ -z "$XML_VERSION" ]]; then
  echo "Version in info.xml is empty" >&2
  exit 1
fi

VERSION="${1:-$XML_VERSION}"
STAGE_DIR="${OUTPUT_DIR}/${EXT_KEY}"
ZIP_FILE="${OUTPUT_DIR}/${EXT_KEY}-${VERSION}.zip"

rm -rf "$STAGE_DIR"
mkdir -p "$OUTPUT_DIR" "$STAGE_DIR"

rsync -a \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='dist/' \
  --exclude='vendor/' \
  --exclude='*.zip' \
  --exclude='.DS_Store' \
  "${ROOT_DIR}/" "${STAGE_DIR}/"

(
  cd "$OUTPUT_DIR"
  rm -f "$(basename "$ZIP_FILE")"
  zip -r -q "$(basename "$ZIP_FILE")" "$(basename "$STAGE_DIR")"
)

echo "$ZIP_FILE"
