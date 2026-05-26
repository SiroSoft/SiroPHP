# Replay Subsystem Hardening Plan

**Priority: S > A > B**  
**Status:** S-tier ✅ Done | A-tier ✅ Done | B-tier ✅ Done  

---

## S-1: Replay Stability

| Task | File | Status |
|------|------|--------|
| curl error handling: phân biệt timeout vs connection vs server error | `LogReplayCommand.php` `executeReplay()` | ✅ |
| Trace JSON validation trước khi access fields | `LogReplayCommand.php` `run()` | ✅ |
| Trace file guard: `is_file()` + `is_readable()` trước `file_get_contents` | `LogReplayCommand.php` `run()` | ✅ |
| Auto-auth max retry = 1 (tránh infinite loop) | `LogReplayCommand.php` auto-auth section | ✅ Built-in (1 attempt) |
| Replay GET 100x liên tiếp → no leak | Manual test | ✅ No leak |

## S-2: Auth Continuity

| Task | File | Status |
|------|------|--------|
| Logging strategy attempt (biết strategy nào thành công) | `LogReplayCommand.php` `autoReauthenticate()` | ✅ `['strategy' => 'xxx']` trong output |
| `extractToken()` fallback paths mở rộng | `LogReplayCommand.php` `extractToken()` | ✅ 12 paths, tự động fallback |
| Auth flow: verify full 6-step fallback chain | Manual test | ✅ Interactive prompt cuối cùng |
| `.siro_auth.json` write with `LOCK_EX` | `LogReplayCommand.php` `writeAuthFile()` | ✅ |
| All `@file_put_contents` → `writeAuthFile()` | `LogReplayCommand.php` | ✅ |
| PHPStan level max — 0 errors | siro-core + SiroPHP | ✅ |

## S-3: Deterministic Diff

| Task | File | Status |
|------|------|--------|
| Strip `debug` key before comparison | `LogReplayCommand.php` `--diff` section | ✅ |
| Strip `meta` key before comparison | `LogReplayCommand.php` `--diff` section | ✅ |
| Normalize JSON key order | `LogReplayCommand.php` `--diff` section | ✅ `ksort()` |
| `"✅ Fixed!"` check: status + body match + norm | `LogReplayCommand.php` `--diff` section | ✅ 3-level verdict |

## A-1: Regression Generation

| Task | File | Status |
|------|------|--------|
| Sanitize trace ID for PHP class name | `MakeTestCommand.php` `fromTrace()` | ✅ `preg_replace('/[^a-zA-Z0-9_]/', '_', $traceId)` |
| Handle trace without auth | `MakeTestCommand.php` `fromTrace()` | ✅ Already handled — `$hasAuth = $authHeader !== ''` |

## A-2: Replay Mutation

| Task | File | Status |
|------|------|--------|
| `--set` support dot-notation (`items.0.name`) | `LogReplayCommand.php` `run()` | ✅ `str_contains($key, '.')` → explode + nested ref |
| `--edit` handle nested objects | `LogReplayCommand.php` `editRecursive()` | ✅ Recursive flatten: `user.addr.city` |
| `--edit` handle non-JSON body | `LogReplayCommand.php` `--edit` section | ✅ Raw text edit instead of "cannot edit" |
