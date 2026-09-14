#!/usr/bin/env bash
# Usage: ./test-origin.sh <origin-ip>
IP="$1"
if [ -z "$IP" ]; then
  echo "Usage: ./test-origin.sh <origin-ip>"
  exit 1
fi
curl -A "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)" \
  --resolve pointonenav.com:443:"$IP" \
  -o /dev/null -s -w "ClaudeBot via origin IP ($IP): %{http_code}\n" \
  https://pointonenav.com/