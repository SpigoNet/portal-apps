#!/usr/bin/env bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILENAME="relatorio-$(date '+%Y-%m-%d_%H-%M-%S').md"
OUTPUT="${SCRIPT_DIR}/${FILENAME}"

curl -s -X GET \
  -H "X-User-ID: 1" \
  -H "X-Token: 11995d5f94aa7be261b391f21ff66bad" \
  "https://apps.spigo.net/api/mithril/relatorio-markdown" \
  -o "${OUTPUT}"

echo "Relatório salvo em: ${OUTPUT}"
