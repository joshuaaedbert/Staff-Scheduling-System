# Staff Scheduler – README

A simple full‑stack staff scheduling system with a **PHP** backend (SQLite) and a **React (Vite)** frontend. Includes unit tests for backend validators and frontend components.

---

## 1) Prerequisites

- **Git**
- **Node.js (LTS)** and **npm**
- **PHP 8+ (CLI)**
- **Composer** (for backend tests)
- (Windows) If enabling PHP extensions fails, install **Microsoft Visual C++ Redistributable 2015–2022 (x64)**

> Verify tools:
>
> ```powershell
> node -v
> npm -v
> php -v
> composer -V
> ```

---

## 2) Project Structure

```
staff-scheduler/
├─ backend/
│  ├─ index.php          # API router (CORS + dispatch)
│  ├─ db.php             # PDO connection + schema bootstrap
│  ├─ staff.php          # /staff endpoints (GET, POST)
│  ├─ shifts.php         # /shifts endpoints (GET, POST + assign/unassign)
│  └─ lib/
│     └─ validators.php  # pure helpers: roles, date/time checks, overlap
│  └─ tests/             # PHPUnit tests
│  └─ composer.json      # dev dependencies for tests
└─ frontend/
   ├─ src/
   │  ├─ App.jsx         # main UI (staff + shifts)
   │  ├─ App.css         # responsive styling
   │  ├─ lib/api.js      # API client
   │  └─ components/
   │     ├─ StaffForm.jsx
   │     ├─ StaffList.jsx
   │     ├─ ShiftForm.jsx
   │     └─ ShiftsList.jsx
   ├─ .env               # VITE_API_BASE=http://localhost:8000
   ├─ vitest.config.js   # frontend tests config
   └─ vitest.setup.js
```

---

## 3) Backend Setup (PHP + SQLite)

### 3.1 Enable SQLite (Windows)

1. Find your `php.ini`:
   ```powershell
   php --ini
   ```
2. Edit `php.ini` and ensure:
   ```ini
   extension_dir = "ext"
   extension=pdo_sqlite
   extension=sqlite3
   ```
3. Verify:
   ```powershell
   php -m | findstr /I "sqlite"
   # expect: pdo_sqlite, sqlite3
   ```

### 3.2 Start the API

From `backend/`:
```powershell
php -S localhost:8000 -t .
```

Tables are auto‑created on first run by `db.php`.

### 3.3 API Endpoints

**Base:** `http://localhost:8000/index.php`

#### Staff
- **GET** `?path=staff` → list all staff
- **POST** `?path=staff` → create staff
  - Body: `{ "name": string, "role": "server|cook|manager", "phone"?: string }`

#### Shifts
- **GET** `?path=shifts[&day=YYYY-MM-DD]` → list shifts (optionally filtered by day)
- **POST** `?path=shifts` → create shift
  - Body: `{ "day": "YYYY-MM-DD", "start_time": "HH:MM", "end_time": "HH:MM", "role": "server|cook|manager", "staff_id"?: number }`
- **POST** `?path=shifts&action=assign` → assign a shift
  - Body: `{ "shift_id": number, "staff_id": number }`
- **POST** `?path=shifts&action=unassign` → unassign a shift
  - Body: `{ "shift_id": number }`

> Overlap rule applies **per staff, per day**: `[start, end)` overlaps if `NOT (existing.end <= start OR existing.start >= end)`.

### 3.4 Quick CLI Tests (PowerShell)

```powershell
# Create staff
$body = @{ name = "Alice"; role = "server"; phone = "306-555-1234" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/index.php?path=staff" -Method Post -Body $body -ContentType "application/json"

# List staff
Invoke-RestMethod -Uri "http://localhost:8000/index.php?path=staff" -Method Get

# Create a shift
$shift = @{ day = "2025-09-01"; start_time = "09:00"; end_time = "17:00"; role = "server" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/index.php?path=shifts" -Method Post -Body $shift -ContentType "application/json"

# Assign
$assign = @{ shift_id = 1; staff_id = 1 } | ConvertTo-Json -Compress
Invoke-RestMethod -Uri "http://localhost:8000/index.php?path=shifts&action=assign" -Method Post -Body $assign -ContentType "application/json"
```

To view server error bodies on non‑2xx in PowerShell:
```powershell
try { <your Invoke-RestMethod here> -ErrorAction Stop } catch {
  $resp=$_.Exception.Response; if($resp){$sr=New-Object System.IO.StreamReader($resp.GetResponseStream());$sr.ReadToEnd()} }
```

---

## 4) Frontend Setup (React + Vite)

From `frontend/`:

```powershell
npm install
```

Create `.env`:
```
VITE_API_BASE=http://localhost:8000
```

Run dev server:
```powershell
npm run dev
```
Open the printed URL (default `http://localhost:5173`).

### Features
- **Staff**: list & add
- **Shifts**: create, filter by day, assign/unassign (role‑aware dropdown)
- Responsive layout (mobile/desktop)

---

## 5) Testing

### Backend (PHPUnit)
```powershell
cd backend
composer install
vendor/bin/phpunit
```
Covers `allowed_roles`, `valid_day`, `valid_time`, `time_minutes`, and `has_overlap` (with in‑memory SQLite).

### Frontend (Vitest + React Testing Library)
```powershell
cd frontend
npm run test            # run once
npm run test:watch      # watch mode
```
Covers API helper behavior (mocked `fetch`) and UI components (`StaffForm`, `ShiftsList`).

---

## 6) Our Approach

- **Separation of concerns**: clean split between a minimal REST‑like PHP API and a React SPA.
- **SQLite** for simplicity: file‑based DB with auto‑created schema to avoid manual migrations during onboarding.
- **Strict role matching**: a shift’s `role` must match the assigned staff’s `role`.
- **Overlap prevention**: server enforces non‑overlapping shifts per staff per day using a simple interval check.
- **Developer UX**: CORS enabled, `.env` for base URL, tidy API client, clear error surfacing, and stepwise Git commits.
- **Testability**: extracted pure validators into `backend/lib/validators.php`; frontend uses Vitest + RTL.

---

## 7) Suggested Commit History (reference)

- `chore: initialize empty project structure`
- `feat(backend): setup PHP API skeleton with SQLite database`
- `feat(frontend): initialize React app with Vite`
- `feat(api): implement /staff GET and POST endpoints with validation and SQLite persistence`
- `chore(api): add clear error when SQLite PDO driver is missing`
- `feat(api): implement /shifts GET and POST (create, assign, unassign) with validation and overlap checks`
- `chore(frontend): add VITE_API_BASE env var for PHP API`
- `feat(frontend): add API client (getStaff, createStaff)`
- `feat(frontend): add StaffList and StaffForm components`
- `feat(frontend): wire staff list and add form into App`
- `style(frontend): add simple responsive styling for forms and list`
- `feat(frontend): add shifts API helpers (list, create, assign, unassign)`
- `feat(frontend): add ShiftForm to create new shifts`
- `feat(frontend): add ShiftsList with assign/unassign controls`
- `feat(frontend): integrate shifts (create/list/assign/unassign) into App`
- `style(frontend): table layout and controls for shifts and filters`
- `refactor(api): extract validation and overlap helpers for unit testing`
- `chore(test): add phpunit as dev dependency`
- `test(backend): add unit tests for validators and overlap using in-memory SQLite`
- `chore(frontend): configure Vitest with jsdom and RTL`
- `test(frontend): add API helper tests with mocked fetch`
- `test(frontend): StaffForm validation and submit behavior`
- `test(frontend): ShiftsList filters options by role and calls onAssign`

---

## 8) Known Limitations & Trade‑offs

- **No authentication/authorization**: anyone can hit the API; CORS is wide open for dev.
- **No edit/delete endpoints**: staff and shifts can’t be updated or removed yet.
- **SQLite** is perfect for demo/dev, but not ideal for multi‑user prod; replace with MySQL/PostgreSQL for scale/concurrency.
- **Timezones**: times are stored as strings without TZ; cross‑timezone scheduling isn’t handled.
- **Validation**: basic only (e.g., phone format isn’t enforced; only 3 roles).
- **Error handling**: minimal; no structured error codes beyond HTTP+message.
- **N+1/joins**: simple queries; fine for small datasets but not optimized.
- **No pagination/filtering** beyond `day` on shifts.
- **No data migrations** framework.
- **Security**: no rate limits, CSRF, input sanitization beyond type/format checks.
- **Tests**: backend tests cover helpers (not full controllers); frontend tests cover key flows but not all states.
- **Deployment**: local dev servers only; no Docker/CI/CD here by default.

---

## 9) Troubleshooting

- **`npm ERR! enoent ... package.json`**: ensure you ran `npm create vite@latest frontend` (or `npm create vite@latest` inside `frontend/`); then run `npm install` in `frontend/`.
- **`PDOException: could not find driver`**: enable `pdo_sqlite` and `sqlite3` in `php.ini` and restart; verify with `php -m`.
- **400/409 from assign**: check role matches between staff and shift; check for overlapping shifts; inspect server body with the PowerShell try/catch shown above.
- **CORS issues**: backend sets permissive CORS; if modifying methods/headers, ensure they’re included in `index.php`.
- **Port conflicts**: change dev ports (e.g., `php -S localhost:8001`, update `VITE_API_BASE`).

---

## 10) Next Steps (nice to have)

- Staff/shift **edit & delete** (with cascade rules)
- **Auth** (JWT/session) + roles (manager vs staff)
- **Timezone-aware** scheduling & weekly views
- Input **validation** (phone/email), better UX messages
- **Pagination** & richer filters (by role, by staff)
- **Migrations** and seed data
- **Docker** for consistent local setup
- **CI** (GitHub Actions) to run PHPUnit/Vitest on PRs
- Swap SQLite for **MySQL/PostgreSQL** in production

