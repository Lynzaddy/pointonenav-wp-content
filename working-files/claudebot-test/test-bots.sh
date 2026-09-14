#!/usr/bin/env bash
# Usage: ./test-bots.sh [url]
# Defaults to production if no URL is given.
URL="${1:-https://pointonenav.com/}"

NAMES=(
  "ClaudeBot"
  "Claude-SearchBot"
  "Claude-User"
  "GPTBot (control)"
  "Browser (control)"
)

UAS=(
  "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)"
  "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-SearchBot/1.0; +Claude-SearchBot@anthropic.com)"
  "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)"
  "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; Chrome/125.0.0.0; compatible; GPTBot/1.1; +https://openai.com/gptbot)"
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36"
)

echo "Testing: $URL"
echo "----------------------------------------"
for i in "${!NAMES[@]}"; do
  code=$(curl -A "${UAS[$i]}" -o /dev/null -s -w "%{http_code}" "$URL")
  printf "%-20s %s\n" "${NAMES[$i]}" "$code"
done