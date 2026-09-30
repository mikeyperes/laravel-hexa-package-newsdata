# Bug Log — laravel-hexa-package-newsdata

Timestamps are EST (UTC−05:00). Never delete an entry.

## NEWSDATA-BUG-001 — A query over 100 characters failed the search with HTTP 422

- **Severity:** High
- **Status:** Fixed in 2.0.8, 2026-09-30 14:32:09 EST.
- **Symptom:** High Net Worth (campaign 56, operation 7481) reported
  "NewsData returned HTTP 422." Its compiled query was 113 characters.
  The same query cut to 100 characters returned HTTP 200.
- **Impact:** Every search whose query exceeded the plan limit lost NewsData
  as a provider, silently shrinking the source pool.
- **Root cause:** `NewsDataService::searchArticles()` sent `q` unchanged;
  NewsData rejects a longer `q` instead of truncating it.
- **Patch:** `clampQuery()` keeps whole words up to
  `newsdata.max_query_length` (default 100) before the request.
- **Guard:** `NewsDataService::clampQuery()`.
