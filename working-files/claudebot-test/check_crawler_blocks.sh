#!/bin/bash
# check_crawler_blocks.sh
# Tests whether pointonenav.com blocks AI crawler User-Agents.
# bash 3.2 compatible (macOS default) - uses parallel arrays, not associative arrays.
#
# Usage:
#   chmod +x check_crawler_blocks.sh
#   ./check_crawler_blocks.sh
#
# Optionally edit SITE and TEST_PAGES below.

SITE="https://pointonenav.com"
TEST_PAGES=("/" "/about/" "/contact/")   # <-- adjust to real paths on your site if these 404

# Parallel arrays: names and their User-Agent strings
UA_NAMES=("ClaudeBot" "GPTBot" "PerplexityBot" "Google-Extended" "CCBot" "Bingbot" "Browser-Chrome")
UA_STRINGS=(
  "ClaudeBot/1.0 (+https://www.anthropic.com/bot.html)"
  "Mozilla/5.0 (compatible; GPTBot/1.1; +https://openai.com/gptbot)"
  "Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)"
  "Mozilla/5.0 (compatible; Google-Extended)"
  "CCBot/2.0 (+https://commoncrawl.org/faq/)"
  "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)"
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
)

# --- helper: single request, print status ---
get_status() {
  local ua="$1"
  local path="$2"
  curl -s -o /dev/null -w "%{http_code}" -A "$ua" --max-time 15 "${SITE}${path}"
}

echo "==============================================="
echo " PASS 1: Homepage sweep across all User-Agents"
echo "==============================================="
BLOCKED_NAMES=()
i=0
while [ $i -lt ${#UA_NAMES[@]} ]; do
  name="${UA_NAMES[$i]}"
  ua="${UA_STRINGS[$i]}"
  code=$(get_status "$ua" "/")
  printf "%-18s -> %s\n" "$name" "$code"
  if [ "$code" != "200" ] && [ "$code" != "301" ] && [ "$code" != "302" ]; then
    BLOCKED_NAMES+=("$i")
  fi
  sleep 1
  i=$((i+1))
done

if [ ${#BLOCKED_NAMES[@]} -eq 0 ]; then
  echo ""
  echo "No blocks detected on homepage sweep. Done."
  exit 0
fi

echo ""
echo "==============================================="
echo " PASS 2: Verifying flagged User-Agents"
echo " (repeat x3, multiple pages, A/B vs browser UA)"
echo "==============================================="

BROWSER_UA="${UA_STRINGS[6]}"  # Browser-Chrome

for idx in "${BLOCKED_NAMES[@]}"; do
  name="${UA_NAMES[$idx]}"
  ua="${UA_STRINGS[$idx]}"
  echo ""
  echo "--- Verifying: $name ---"

  for page in "${TEST_PAGES[@]}"; do
    echo "  Page: $page"
    for run in 1 2 3; do
      before=$(get_status "$BROWSER_UA" "$page")
      test_code=$(get_status "$ua" "$page")
      after=$(get_status "$BROWSER_UA" "$page")
      printf "    run %d -> browser(before)=%s | %s=%s | browser(after)=%s\n" \
        "$run" "$before" "$name" "$test_code" "$after"
      sleep 1
    done
  done
done

echo ""
echo "==============================================="
echo " Interpretation guide:"
echo " - If browser before/after are consistently 200"
echo "   but the bot UA is consistently non-200 across"
echo "   pages and repeats, it's a real block (not rate"
echo "   limiting or a fluke)."
echo " - If browser requests also degrade, you may be"
echo "   hitting rate limiting rather than a UA block."
echo "==============================================="