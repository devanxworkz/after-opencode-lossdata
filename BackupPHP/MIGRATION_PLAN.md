# PHP API Migration Plan

> **Scope:** Analysis-only of the legacy PHP REST APIs under `BackupPHP/`.
> Target stack: Node.js + TypeScript + Fastify + PostgreSQL (pg) + Zod.
> No implementations, no `src/` changes, no DB changes — this is a planning document only.

---

## 1. API Inventory

| PHP File | Method | Purpose | Tables | Proposed Module | Proposed Route |
|----------|--------|---------|--------|-----------------|----------------|
| `tabledata.php` | GET | Vehicle detail + realtime telemetry with pagination and optional time range | `allvin`, `realtimedata` | `telemetry` / `vehicles` | `GET /vehicles/:vin` |
| `real_timedata1.php` | GET | Latest record or last-50 / time-range telemetry (chart data, ascending) | `realtimedata` | `telemetry` | `GET /telemetry` |
| `geolocationhistory.php` | GET | Paginated GPS/geo history (lat/lng parsed) for a VIN + time range | `realtimedata` | `telemetry` (geo) | `GET /telemetry/geolocation` |
| `backtimedatfetch.php` | GET | Historical telemetry (multiple fields + raw `data`) for a VIN + time range, numeric coercion | `realtimedata` | `telemetry` | `GET /telemetry/history` |
| `search_vehicle.php` | GET | VIN / owner / phone search (10 results) | `allvin` | `vehicles` | `GET /vehicles/search` |
| `all_vinfetch.php` | GET | All VIN numbers | `allvin` | `vehicles` | `GET /vehicles/vins` |

**Database discovered:**
- Database: `rivotcommandcenterdb`
- Table `allvin` — vehicle master data (col: `vinNumber`, `vinnumber`, `ownername`, `phonenumber`, …)
- Table `realtimedata` — telemetry (col: `vinnumber`/`vinNumber`, `time`, `ntc`, `lat_long`, `speed_kmph`, `soc`, `apusoc`, `tripkm`, `batvoltage`, `motortemp`, `controllermostemp`, `bmsmostemp`, `currentconsumption`, `inah`, `inah_by_charger`, `inah_by_regen`, `outah`, `remainingcapacity_ah`, `ev_power_state`, `currentrider`, `tirepressure`, `data`)

---

## 2. Detailed API Analysis

### 2.1 `tabledata.php`

- **Purpose:** Single vehicle overview: full `allvin` row + paginated realtime telemetry (optionally date-filtered).
- **Method:** GET
- **Query params:** `vin` (required), `start`, `end` (optional, used only when both given), `limit` (default 500), `offset` (default 0).
- **Request body:** none.
- **Response structure:**
  ```json
  {
    "status": "success",
    "allvin": { ...full allvin row... },
    "realtimedata": [ { ...full realtimedata row, with transforms... } ]
  }
  ```
- **Error responses:** `400` missing vin → `{"status":"error","message":"VIN number is required"}`; `500` db connect fail → `{"status":"error","message":"Database connection failed"}`; `200` with `{"status":"error","message":"VIN not found"}` when allvin row missing (note: no HTTP error code, returns 200). Empty/query-fail realtime not explicitly handled.
- **SQL:**
  - `SELECT * FROM allvin WHERE vinNumber = $1` (single row)
  - `SELECT * FROM realtimedata WHERE vinnumber = $1 [AND time BETWEEN $2 AND $3] ORDER BY time DESC LIMIT $limit OFFSET $offset`
- **Transforms:**
  - `ntc` column: trimmed CSV string → array via `explode(',')` + `trim`. **Note:** NOT numeric-cast here.
  - `lat_long` column: trimmed CSV → array of floats via `floatval`.
- **Unclear:** the case-sensitivity/inconsistency of VIN column name: `allvin.vinNumber` vs `realtimedata.vinnumber`. Also whether the app relies on the exact column casing from Postgres (PG lowercases unquoted identifiers, so `vinNumber` in an unquoted query actually reads `vinnumber`).

### 2.2 `real_timedata1.php`

- **Purpose:** Telemetry for charts — three modes: latest-only, last-50, or full time-range (ascending).
- **Method:** GET (+ handles OPTIONS preflight manually)
- **Query params:**
  - `vin` (optional)
  - `latest` (`"1"` literal → latest record mode)
  - `start`, `end` (optional; parsed via `strtotime`)
- **Request body:** none.
- **Response structure:**
  ```json
  { "status": "success", "count": <n>, "data": [ ...raw rows... ] }
  ```
- **Error responses:** db fail / query fail → `{"status":"error","message":"Server error"}` (no HTTP code change → 200). OPTIONS preflight → `200`.
- **SQL (3 cases):**
  1. latest + vin: `SELECT * FROM realtimedata WHERE vinnumber = $1 ORDER BY time DESC LIMIT 1`
  2. latest, no vin: `SELECT * FROM realtimedata ORDER BY time DESC LIMIT 1` (no WHERE — unordered global-latest; effectively scans/big)
  3. range (start & end present): `SELECT * FROM realtimedata WHERE ... ORDER BY time ASC` — **no LIMIT** → unbounded result set
  4. no range (vin and/or neither): `SELECT * FROM (SELECT * FROM realtimedata WHERE ... ORDER BY time DESC LIMIT 50) t ORDER BY time ASC`
- **Transforms:** none on output (raw rows). Time formatting via `strtotime`/`date`.
- **Performance red-flag factors:**
  - `SELECT *` (full row incl. large `data` column?).
  - Case 2 (latest, no vin) does a scan-ordered `ORDER BY time DESC LIMIT 1` over the whole 100M+ table with no filter — needs an index on `time` descending to be safe.
  - Case 3 (range with no vin) and Case 3 (range with vin) have **no LIMIT** — a wide time range returns unbounded rows.
  - Case 2b (no range) nested subquery `ORDER BY time DESC LIMIT 50` also scans.

### 2.3 `geolocationhistory.php`

- **Purpose:** GPS history — paginated time/`lat_long` + velocity/charge snapshot data.
- **Method:** GET
- **Query params:** `vin` (required), `start` (required), `end` (required), `page` (default 1), `limit` (default 5000).
- **Request body:** none.
- **Response structure:**
  ```json
  {
    "status": "success",
    "page": <int>, "limit": <int>, "count": <n>,
    "data": [
      { "time":..., "lat":<float|null>, "lng":<float|null>, "speed_kmph":..., "soc":..., "apusoc":..., "tripkm":..., "batvoltage":..., "motortemp":..., "controllermostemp":..., "bmsmostemp":..., "currentconsumption":..., "inah":..., "inah_by_charger":..., "inah_by_regen":..., "outah":..., "remainingcapacity_ah":..., "ntc":..., "ev_power_state":..., "currentrider":..., "tirepressure":... }
    ]
  }
  ```
- **Error responses:** missing params → `{"status":"error","message":"Missing params"}` (200); db fail → `{"status":"error","message":"DB failed"}` (200); query fail → `{"status":"error","message":<pg_last_error>}` (**exposes raw DB error string to client**).
- **SQL:** explicit column list (no `SELECT *`), `WHERE vinnumber = $1 AND time >= $2 AND time <= $3 ORDER BY time ASC LIMIT $limit OFFSET $offset`.
- **Transforms:**
  - `start`/`end`: replaces `T` with space and appends `:00` when 16 chars → `YYYY-MM-DD HH:MM:SS`.
  - `lat_long`: CSV → `{ lat, lng }` floating points.
  - `ntc`: left as-is (raw string) here (inconsistent with other files that convert to array).
- **Performance red-flag factors:** default limit 5000 with `OFFSET $offset` pagination → deep `OFFSET` on a 100M+ table gets expensive as `page` grows (OFFSET rewrites rows). Missing explicit `COUNT(*)` query → client cannot know total pages.

### 2.4 `backtimedatfetch.php`

- **Purpose:** Full historical telemetry (many numeric fields + raw `data` blob) — supports detailed graphs.
- **Method:** GET
- **Query params:** `vin` (required), `start` (required), `end` (required).
- **Request body:** none.
- **Response structure:**
  ```json
  {
    "status": "success", "count": <n>,
    "data": [ { "time":..., "currentconsumption":<float|null>, "speed_kmph":<float|null>, ..., "ntc":[<numbers>], "lat_long":[<float,float>|null], "data":... } ]
  }
  ```
- **Error responses:** missing params → `400 {"status":"error","message":"Missing required parameters: vin, start, end"}`; invalid date → `400 {"status":"error","message":"Invalid date format. Expected: YYYY-MM-DDTHH:MM"}`; db/query fail → `500`.
- **SQL:** explicit column list, `WHERE vinnumber = $1 AND time BETWEEN $2 AND $3 ORDER BY time DESC` — **no LIMIT** → unbounded for wide ranges.
- **Transforms:**
  - `start`/`end`: ISO8601 `T`→space, pad seconds, `strtotime`+`date` validation.
  - Numeric columns → `(float)` (or null if not set).
  - `ntc` → array filtered: numeric values cast to float, non-numeric kept as string.
  - `lat_long` → `[float, float]` or null.
  - `data` → passed through raw.
- **Performance red-flag factors:** `ORDER BY time DESC` with no LIMIT over a 100M+ table; pulls the possibly-large `data` column; needs `(vinnumber, time)` composite index.

### 2.5 `search_vehicle.php`

- **Purpose:** Autocomplete search on VIN / owner / phone.
- **Method:** GET
- **Query params:** `q` (required; trimmed; empty → empty array).
- **Request body:** none.
- **Response structure:** bare JSON array (no `status` envelope):
  ```json
  [ { "vinnumber":..., "ownername":..., "phonenumber":... }, ... ]
  ```
- **Error responses:** db fail / query fail → `[]` (empty array, 200).
- **SQL:** `SELECT vinnumber, ownername, phonenumber FROM allvin WHERE vinnumber ILIKE $1 OR ownername ILIKE $1 OR phonenumber ILIKE $1 LIMIT 10` with `$searchParam = "%q%"`.
- **Transforms:** `trim`.
- **Performance:** `ILIKE '%...%'` on three columns → leading-wildcard, cannot use a b-tree index; full scans each call; will be slow on large `allvin`. Missing composite/trigram (`pg_trgm`) indexes.
- **Behavioral note:** unlike `tabledata.php`, here the `allvin` VIN column is treated as `vinnumber` (lowercase) consistently with `realtimedata`.

### 2.6 `all_vinfetch.php`

- **Purpose:** List all VIN numbers (dropdown).
- **Method:** GET
- **Query params:** none.
- **Request body:** none.
- **Response structure:**
  ```json
  { "status": "success", "count": <n>, "vins": [ "VIN1", "VIN2", ... ] }
  ```
- **Error responses:** db fail → `{"status":"error","message":"Database connection failed","pg_error":<raw>}` (200; leaks DB error); query fail → similar.
- **SQL:** `SELECT vinnumber FROM allvin ORDER BY vinnumber ASC` (table `vinnumber`).
- **Transforms:** none.
- **Performance:** full table scan of `allvin` returning all rows; `ORDER BY vinnumber` needs index to avoid sort of whole table. Returns everything (no pagination) — grows with fleet size.

---

## 3. Database Queries

### `allvin`-focused
| # | Query | Notes |
|---|-------|-------|
| Q1 | `SELECT * FROM allvin WHERE vinNumber = $1` | Vehicle detail; single-row lookup. |
| Q2 | `SELECT vinnumber, ownername, phonenumber FROM allvin WHERE vinnumber ILIKE $1 OR ownername ILIKE $1 OR phonenumber ILIKE $1 LIMIT 10` | Search; leading-wildcard ILIKE — no b-tree index use. |
| Q3 | `SELECT vinnumber FROM allvin ORDER BY vinnumber ASC` | All VINs; full table read + sort. |

### `realtimedata`-focused (100M+ rows — critical)
| # | Query | Notes |
|---|-------|-------|
| R1 | `SELECT * FROM realtimedata WHERE vinnumber = $1 ORDER BY time DESC LIMIT 1` | Latest per VIN. Needs `(vinnumber, time DESC)` index. |
| R2 | `SELECT * FROM realtimedata ORDER BY time DESC LIMIT 1` | Global latest; full scan. Needs index on `time` DESC. |
| R3 | `SELECT * FROM realtimedata WHERE vinnumber = $1 [AND time BETWEEN $2 AND $3] ORDER BY time DESC LIMIT $limit OFFSET $offset` | Paginated telemetry; deep OFFSET risk. |
| R4 | `SELECT * FROM realtimedata WHERE vinnumber = $1 [AND ...] ORDER BY time ASC` (range, no LIMIT) | Unbounded ascending; large result sets. |
| R5 | `SELECT * FROM (SELECT * FROM realtimedata WHERE ... ORDER BY time DESC LIMIT 50) t ORDER BY time ASC` | Last-50; windowed, but outer re-sort is fine. |
| R6 | `SELECT <20 cols> FROM realtimedata WHERE vinnumber = $1 AND time BETWEEN $2 AND $3 ORDER BY time ASC LIMIT $limit OFFSET $offset` | Geo history; explicit columns; OFFSET concern. |
| R7 | `SELECT <19 cols> FROM realtimedata WHERE vinnumber = $1 AND time BETWEEN $2 AND $3 ORDER BY time DESC` | History; no LIMIT; large result for wide ranges. |

### Cross-cutting index recommendations (to be validated against `EXPLAIN ANALYZE` later — NOT creating indexes now)
- Composite index `(vinnumber, time DESC)` — serves R1, R3, R4, R5, R6, R7.
- Index on `time DESC` — serves R2.
- `pg_trgm` GIN index on `allvin.vinnumber`, `allvin.ownername`, `allvin.phonenumber` — serves Q2 ILIKE.
- Index on `allvin.vinnumber` — serves Q1, Q3.

---

## 4. Shared Logic

- **DB connection:** every file duplicates the same hardcoded connection string + `pg_pconnect` + failure handling. → Centralize into the single shared `pg.Pool` in the Fastify foundation.
- **VIN lookup / validity:** `tabledata.php` is the only file that validates the VIN exists in `allvin` first. Others query `realtimedata` directly. → Consider a shared "get vehicle by VIN" service; decide whether each endpoint should validate VIN first.
- **Timestamp normalization:** duplicated across `geolocationhistory.php`, `backtimedatfetch.php`, `real_timedata1.php`:
  - Replace `T` with space (`str_replace`).
  - Pad seconds if `strlen == 16` (append `:00`).
  - `strtotime` + reformat → `YYYY-MM-DD HH:MM:SS`.
  → Extract a shared time-parsing utility (with Zod schema + validation to Fastify `querystring`).
- **`lat_long` parsing** (`explode`, `trim`, `float`): duplicated in `tabledata.php`, `geolocationhistory.php` (→ `{lat,lng}`), `backtimedatfetch.php` (→ `[float,float]`). Inconsistent output shapes → normalize in one shared transformer.
- **`ntc` parsing** (CSV → array): in `tabledata.php` (string array), `backtimedatfetch.php` (numeric-cast array); raw in `geolocationhistory.php`. → Shared transformer with a documented canonical shape.
- **Envelope inconsistency:** some return `{status, count, data}`, some `{status, allvin, realtimedata}`, `search_vehicle.php` returns a bare array. → Standardize a response envelope helper.
- **VIN key casing inconsistency** (`vinNumber` vs `vinnumber`): appears across files/queries → normalize on a single canonical column reference.

---

## 5. Performance Concerns

- **R4 / R7 unbounded result sets:** range queries return ALL matching rows (no LIMIT). On 100M+ rows with a wide date range this can return millions of rows → memory/network blowup and client-side freeze. **Highest priority.**
- **`SELECT *` on `realtimedata` (R1–R5):** pulls every column, including the potentially large `data` blob, even when the client only needs a few chart fields. → reduce to needed columns.
- **Deep OFFSET pagination (R3 / R6):** `OFFSET` skips/reads all prior rows each page → O(n²) as page grows. → cursor/keyset pagination on `(vinnumber, time)`.
- **Global `LIMIT 1` (R2):** no index on `time DESC` → full sort of 100M+ rows. → index + order that uses it.
- **Leading-wildcard ILIKE (Q2):** `%q%` can't use b-tree; full scan per keystroke. → `pg_trgm` GIN index, or tighten to prefix `q%` where UX allows.
- **Full-table `allvin` read (Q3):** returns all VINS, no pagination/filter.
- **Repeated per-query connections:** `pg_pconnect` mitigates per-request connects, but the pool isn't shared across files as one tuned pool. In Node, one shared pool with `max` sized appropriately (from `DATABASE_POOL_MAX`).
- **Missing `COUNT(*)` in paginated endpoints:** client can't know total pages; with OFFSET this worsens the deep-pagination issue.
- **No result-set size cap** beyond caller-supplied `limit` (defaults as high as 500 / 5000).

---

## 6. Security Concerns

- **Hardcoded production DB credentials** in every PHP file (`103.174.148.224 / rivotcommandcenter / "123"`), committed to the repo, and publicly exposed in source. → Env vars only (`.env`, no commit).
- **Raw DB error leakage:** `geolocationhistory.php`, `all_vinfetch.php` echo `pg_last_error()` / `pg_error` directly to the client → reveals schema/connection internals. → Never surface DB errors; log server-side, return generic message.
- **Wildcard CORS:** `Access-Control-Allow-Origin: *` on all files → any origin can call these APIs. → Restrict origins via `@fastify/cors` + Helmet (already configured in the foundation).
- **Optional auth / no authentication:** no API key, token, or user auth; any misconfiguration allows open data access. → Add auth layer (API keys / JWT) as part of migration.
- **SQL injection:** queries use `pg_query_params` / `$1` placeholders (good; no injection found). `$limit`/`$offset`/`$page` are `(int)`-cast (safe). **Residual risk:** `real_timedata1.php` interpolates `$where` from conditions built with `$i` counters, but every value is still bound via `$params` and column fragments are fixed strings — no user-controlled identifiers — so no injection. Keep this pattern (parameterized) in Fastify/pg.
- **Unsanitized output:** numeric coercion uses `(float)`/`floatval`; `data` and `ntc` non-numeric strings returned as-is.
- **`ini_set('display_errors', 1)` (real_timedata1.php):** if active in production, PHP errors could leak. → Ensure `display_errors` off in prod; Fastify error handler already masks internals except `message` in dev.
- **Empty-search input handling:** `search_vehicle.php` returns `[]` for empty `q` (OK) — but note console/log lines `error_log` include user input (`$q`), a minor log-injection vector. → sanitize/redact logging.

---

## 7. Proposed Fastify Architecture

Group by domain (not 1:1 file mapping). Given the analysis, the modules are:

```
src/
├── modules/
│   ├── telemetry/            # realtimedata queries (latest, range, history, geo, last-50)
│   │   ├── telemetry.routes.ts
│   │   ├── telemetry.service.ts
│   │   └── telemetry.schema.ts       # Zod schemas + time/lat/ntc transformers
│   ├── vehicles/             # allvin queries (detail, search, all VINs)
│   │   ├── vehicles.routes.ts
│   │   ├── vehicles.service.ts
│   │   └── vehicles.schema.ts
│   └── common/               # shared utils
│       ├── time.ts           # ISO8601 → timestamp normalization
│       ├── transformers.ts   # lat_long, ntc shared parsing
│       └── response.ts       # standard envelope helper
```

Mapping of PHP files → Fastify routes:

| Area | PHP file(s) | Route(s) | Notes |
|------|-------------|----------|-------|
| Telemetry | `real_timedata1.php` | `GET /telemetry` | latest / last-50 / range modes |
| Telemetry | `backtimedatfetch.php` | `GET /telemetry/history` | full history (limit + keyset) |
| Telemetry | `geolocationhistory.php` | `GET /telemetry/geolocation` | geo history (lat/lng) |
| Vehicle+Telemetry | `tabledata.php` | `GET /vehicles/:vin` | detail + telemetry page |
| Vehicles | `search_vehicle.php` | `GET /vehicles/search` | autocomplete |
| Vehicles | `all_vinfetch.php` | `GET /vehicles/vins` | all VINs |

Key Fastify design points:
- Reuse the existing shared `pg.Pool` (decorated `fastify.pg`) — never create a pool per request/route.
- All query params validated via Zod in route `schema` `querystring` → returns 400 automatically with a structured message (replaces manual validation + non-standard error codes).
- Time / `lat_long` / `ntc` parsing in shared `common` helpers for consistency.
- Standard response envelope helper; keep `search` as its own shape if frontend depends on bare array (flag for frontend contract check).
- Add:
  - Request logging + request id (Fastify built-in).
  - Central error handler (already in foundation) mapping `ZodError` → 400, DB errors → 500 generic.
  - Auth plugin (API key/JWT) before exposing publicly.
- Performance-aware queries (migration stage 2):
  - Parameterized LIMIT with a sane default + maximum cap.
  - **Keyset/cursor pagination** (`WHERE (vinnumber, time) > ($vin, $cursor) ORDER BY time ASC LIMIT n`) instead of OFFSET, or at minimum bounded `LIMIT` + `COUNT`.
  - Column projection (no `SELECT *`), omit heavy `data` column unless requested via `?fields=`.
  - Reuse prepared statements (`fastify.pg.query` with parameterized text).
  - Indexes: `(vinnumber, time DESC)`, `time DESC`, `pg_trgm` on `allvin` searchable columns — added in Stage 2 only, after confirming via `EXPLAIN ANALYZE`.

---

## 8. Migration Order

Recommended sequencing (each step: implement module → unit/integration test → verify against schema):

1. **`GET /vehicles/vins` (all_vinfetch.php)** — simplest (single table, no params). Establishes module + envelope + pool patterns quickly.
2. **`GET /vehicles/search` (search_vehicle.php)** — introduces query params + Zod validation + the ILIKE performance topic early.
3. **`GET /vehicles/:vin` (tabledata.php)** — first compound query (allvin + realtimedata join in app logic), establishes per-VIN indexing baseline, VIN validation concern.
4. **`GET /telemetry` (real_timedata1.php)** — the 3-mode branching; introduces time normalization + bounded/keyset last-50; highest usage for dashboards.
5. **`GET /telemetry/history` (backtimedatfetch.php)** — full history; introduces unbounded-query refactor (LIMIT + keyset + projection) — critical against 100M+ rows.
6. **`GET /telemetry/geolocation` (geolocationhistory.php)** — pagination + `lat/lng` transform; last because it overlaps with history queries but adds map-specific shaping.

Reasoning: start with zero-parameter endpoints to validate the foundation wiring, then introduce params/validation, then the high-value telemetry ranges where performance/design effort is concentrated. Consider migrating `real_timedata1` before `/:vin` if the dashboard loads telemetry first — reorder based on actual frontend usage if provided.

---

## 9. Unknowns / Questions

- **VIN column casing:** `tabledata.php` uses `vinNumber`; all others use `vinnumber`. Need the real `allvin`/`realtimedata` schema to confirm canonical column names/casing (Postgres should return lowercase for unquoted, but quoting matters).
- **Full schema missing:** exact columns, types, and whether `realtimedata.data` is a large JSON/text blob that inflates `SELECT *`.
- **`real_timedata1.php` "latest, no vin" semantics:** returns the globally newest row across ALL vehicles — is that intentional/used? Likely a dashboard "latest whatever" widget, but confirm.
- **Response contract dependency:** the React frontend may rely on exact response shapes (bare array in `search_vehicle.php`; specific envelope keys). Need the frontend network calls to confirm before standardizing envelopes.
- **Only 6 files in `BackupPHP/`:** the task mentioned ~10 PHP APIs. A sibling folder `command/backupforthehostingerphp/` (~22 PHP files incl. `login.php`, `telemery.php`, `timedataset.php`, `get_vin_details.php`, `latest_ntc.php`, `get_last_alert.php`, etc.) exists. Confirm whether its files are also in scope for this migration, or whether `BackupPHP/` (these 6) is the authoritative/current set.
- **Auth model:** no auth exists in PHP. Need the intended auth mechanism (API key shared, per-user JWT, none/internal-only) before/while adding the auth layer.
- **Existing WebSocket / React:** there is an existing WebSocket in the project; confirm it doesn't also write to `realtimedata` or share endpoints that could conflict with the new backend routes.
- **Date timezone handling:** PHP `strtotime`/`date` use server-local timezone; need to confirm what timezone `time` is stored in and how the frontend sends `start`/`end` (UTC vs local, with/without offset) to avoid off-by-hours filtering in the migrated timestamps.
- **Deep pagination needs:** confirm whether any UI page actually goes beyond a few OFFSET pages for `geolocationhistory` / `tabledata`; if not, simple bounded LIMIT may suffice over keyset.
- **Production DB:** the connection targets `103.174.148.224` — confirm this is the target for the new backend and whether credentials will be moved to `.env` (required) and secrets rotated (the current password `123` is exposed and should not be reused).
