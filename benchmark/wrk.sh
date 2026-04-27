#!/usr/bin/env bash
set -euo pipefail

URL="${1:-http://localhost:8080/users}"
THREADS="${THREADS:-4}"
CONNECTIONS="${CONNECTIONS:-100}"
DURATION="${DURATION:-10s}"

TMP_LUA="$(mktemp)"
trap 'rm -f "$TMP_LUA"' EXIT

cat > "$TMP_LUA" <<'LUA'
done = function(summary, latency, requests)
  local total = summary.requests or 0
  local non2xx = summary.non2xx or 0
  local socket_errors = 0
  if summary.errors then
    socket_errors = (summary.errors.connect or 0) + (summary.errors.read or 0) + (summary.errors.write or 0) + (summary.errors.timeout or 0)
  end
  local errors = non2xx + socket_errors
  local error_rate = 0
  if total > 0 then
    error_rate = (errors / total) * 100
  end
  io.write(string.format("Error rate: %.2f%%\n", error_rate))
end
LUA

OUTPUT="$(wrk --latency -t"$THREADS" -c"$CONNECTIONS" -d"$DURATION" -s "$TMP_LUA" "$URL")"

echo "=== Siro v0.7 wrk Benchmark ==="
echo "$OUTPUT" | grep -E "Requests/sec|Latency|Error rate" || true
