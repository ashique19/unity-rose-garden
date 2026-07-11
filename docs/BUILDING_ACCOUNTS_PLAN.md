# Unity Rose Garden App — Building Accounts Architecture Plan

**Status:** Decisions locked. Ready for Phase A implementation.  
**Repo:** `ashique19/unity-rose-garden`  
**Branch for this plan:** `cursor/building-accounts-plan-25f9`  
**Last updated:** 2026-07-11

This document is the source of truth for rebuilding the app from gas-only billing into full **billing + building accounts management**. Continue from Cursor Desktop using this file.

---

## 1. Product vision

**App name:** Unity Rose Garden App  
**Building:** Unity Rose Garden — G+9 (10 storied), **2 flats per floor**, **18 flats total**.

| Audience | Access |
|----------|--------|
| **Public / members** | View flats and monthly bills only |
| **Association admins** | Enter data, generate bills, collect payments, manage cashbook |

### Flat naming (locked)

G+9 building; residential flats start at floor 2 (no `1A`/`1B`):

```
2A, 2B, 3A, 3B, 4A, 4B, 5A, 5B, 6A, 6B, 7A, 7B, 8A, 8B, 9A, 9B, 10A, 10B
```

### Flat contact (locked)

Each flat stores:

| Field | Rule |
|-------|------|
| **Contact person name** | Required string |
| **Phone** | **11-digit** phone number (Bangladesh-style, e.g. `017XXXXXXXX`) |

### Initial bill types (locked)

| Key | Label | Nature |
|-----|-------|--------|
| `gas` | Gas | Per-flat meter |
| `water` | Water | Common meter → equal split among **water-enabled** flats |
| `cleaner` | Cleaner | Other / template charge |
| `common_electricity` | Common electricity | Other / template charge |

More bill types will be added later via admin (no hardcoding beyond these seeds).

---

## 2. Locked decisions

| # | Topic | Decision |
|---|--------|----------|
| 1 | Bill unit | **One `MonthlyStatement` per flat per month** |
| 2 | Public history | Default **current month** + **dropdown** for previous months/years (all history) |
| 3 | Payments | **Multiple collections** per statement |
| 4 | Cash in | Allowed **with or without** a flat (donation / misc) |
| 5 | Meter OCR | **Gemini** vision — suggest reading, admin must confirm |
| 6 | Water | Common bill ÷ **N water-enabled flats** (e.g. 1 disabled → ÷17) |
| 7 | Auth | **Role-based**: `admin`, `secretary`, `treasurer` (+ public) |
| 8 | Enable/disable | **Per flat × bill type** (not only per generated line) |
| 9 | Legacy data | Build new app first; **migrate old readings/bills once** at the end |
| 10 | Flat names | `2A`…`10B` as above (18 flats) |
| 11 | Flat contact | **Name** + **11-digit phone** per flat |
| 12 | Seed bill types | Gas, Water, Cleaner, Common electricity |

### Participation rules (critical)

- If a flat is **gas disabled** → do not collect gas reading; do not create gas line.
- If a flat is **water disabled** → exclude from water divisor and water line.
- Same pattern for Cleaner / Common electricity (and future types): disabled flat is skipped when that type is applied.
- If `N = 0` water-enabled flats → block water generation with a clear error.
- Default for all flat × bill-type settings: **enabled = true**.

---

## 3. Domain model

```text
Building 1──* Flat
Building 1──* BillType
Flat *──* BillType          via FlatBillTypeSetting (enabled)
Flat 1──* GasMeterReading   (only when gas enabled)
Building 1──* CommonMeterReading   (water, etc.)
Flat 1──* MonthlyStatement  (unique: flat_id + bill_month)
MonthlyStatement 1──* StatementLine
MonthlyStatement 1──* Collection
Building 1──* AccountLedgerEntry
Collection 0..1── AccountLedgerEntry   (optional cash-in link)
User *──* Role
```

### Entities

| Entity | Purpose |
|--------|---------|
| **Building** | Singleton config: name, m³→kg conversion rate, opening balance |
| **Flat** | 18 units (`2A`…`10B`); `contact_name`, `phone` (11 digits) |
| **BillType** | Catalog of charge heads (`gas`, `water`, `cleaner`, `common_electricity`, …) |
| **FlatBillTypeSetting** | `flat_id` + `bill_type_id` + `enabled` |
| **GasMeterReading** | Per flat/month: date, prev/curr m³, photo path, Gemini suggestion, confirmed value |
| **CommonMeterReading** | Building-level common meter bill for a month (water): total amount, optional readings/photo |
| **MonthlyStatement** | Public monthly bill shell for one flat |
| **StatementLine** | Lines on a statement: type/key, label, qty, rate, amount, note, enabled snapshot |
| **ChargeTemplate** | Optional defaults for non-meter heads (amount, label, bill_type) |
| **Collection** | Payment against a statement (many allowed) |
| **AccountLedgerEntry** | Building cashbook: `cash_in` / `cash_out`; optional `flat_id`; optional `collection_id` |
| **User** | Phone + password login |
| **Role** | `admin`, `secretary`, `treasurer` |

### Derived amounts

- Statement **total** = sum of enabled statement lines  
- Statement **collected** = sum of collections  
- Statement **pending** = max(0, total − collected)  
- Building **balance** = opening_balance + Σ cash_in − Σ cash_out  
- Dashboard **pending collections** = Σ statement pendings  

### Gas line snapshot fields

Bill month, reading date, current m³, previous m³, consumed m³, consumed kg, rate/kg, total.

### Water line

Label e.g. `Common water – Jul 2026`, amount = `common_total / N`, note optional.

---

## 4. UX

### Public (no login)

```text
Home → list of 18 flats
  → Flat page
      Month control: [current month] + dropdown (month/year search)
      Summary: Gas | Water | Cleaner | Common electricity | … | Total
      Buttons: [Gas bill details] [Other bill details]
```

- Gas details: reading math + total  
- Other details: heading, amount, note, subtotal  
- No ledger, OCR, or admin actions  

### Admin by role

| Role | Capabilities |
|------|----------------|
| **Secretary** | Gas readings (+ photo/Gemini later), common water entry, other charges, generate month, flat bill-type toggles |
| **Treasurer** | Collections (multi), cash in (flat optional), cash out, balance & pending dashboard |
| **Admin** | Everything + users/roles + building settings |

---

## 5. Gemini meter reading (Phase D — not Phase A)

1. Capture/upload analogue meter photo.  
2. Store file; call Gemini with a strict numeric-read prompt.  
3. Show suggestion; **admin confirms/edits** before save.  
4. Never auto-commit OCR to the ledger.  
5. Config: `GEMINI_API_KEY` in `.env`.

---

## 6. Legacy data strategy

**Do not migrate during rebuild.**

Current app has previous months’ meter readings and gas bills (`unity_rose_garden.sql`, existing tables). Keep that dump as the historical source.

When the new app is feature-complete:

1. Artisan import command maps flats by name (`2A`, …).  
2. Import readings → `GasMeterReading`.  
3. Import bills/details → `MonthlyStatement` + gas `StatementLine`.  
4. Import custom charges if any → other lines.  
5. Default all `FlatBillTypeSetting` enabled.  
6. Best-effort: old paid → one `Collection`.  
7. Validate a few months’ totals, then cut over.

Until then, develop against seed data only.

---

## 7. What exists today (context for implementers)

Legacy Laravel app (gas-focused):

- Models: `Flat`, `MeterReading`, `Bill`, `BillDetail`, `CustomCharge`, `ChargeTemplate`, `BillItem`, `User`
- Phone login; many public read routes; write routes behind `auth`
- Schema drift: code uses `flats.status`, `bill_details.amount_due` / `payment_status` without matching migrations
- Custom charges UI exists but is not fully wired into bill totals
- Broken bits: `Flat::meterReeadings()` wrong namespace; missing `showByMonth`; example tests only

**Phase A should introduce new tables/models** rather than stretching the old `Bill` / `BillDetail` design. Old code can remain until cutover; prefer parallel new routes/controllers under a clear structure.

---

## 8. Implementation phases

### Phase A — Foundation (implement next)

**Goal:** New schema + roles + public flat → month → statement views. No OCR, no ledger, no full generate yet.

Deliverables:

1. **Migrations**
   - `buildings`
   - `flats` (ensure `name`, `contact_name`, `phone` 11 digits; seed 18 flats `2A`…`10B` — may alter existing `flats` or recreate carefully)
   - `bill_types`
   - `flat_bill_type_settings`
   - `monthly_statements` (`flat_id`, `bill_month`, unique pair)
   - `statement_lines`
   - `roles` / `role_user` (or equivalent)
   - Keep users phone-auth compatible

2. **Seeders**
   - Building: Unity Rose Garden + default m³→kg rate (e.g. 2.04)
   - Flats: `2A`…`10B` with placeholder `contact_name` + 11-digit `phone` (replace with real contacts later)
   - Bill types: gas, water, cleaner, common_electricity
   - FlatBillTypeSetting: all enabled for all flats
   - Roles: admin, secretary, treasurer
   - Demo admin user (phone-based)
   - Optional: 1–2 demo statements with sample lines for UI

3. **Auth**
   - Keep phone login
   - Attach roles; middleware/policies stubs: `role:admin|secretary|treasurer`

4. **Public UI**
   - Home: flat list
   - Flat show: month dropdown (default current month), statement summary by bill type, links to gas details / other details
   - Empty state when no statement for selected month

5. **Admin stubs (minimal)**
   - Screen or section to toggle flat × bill-type enable/disable (secretary/admin)
   - Do **not** require full billing generate yet

6. **Tests (smoke)**
   - Seed runs
   - Public flat list / month view returns 200
   - Water divisor helper unit test: 18 enabled → ÷18; 1 disabled → ÷17

**Out of scope for Phase A:** Gemini, cashbook, collections UI, common water entry, month generate, data import.

### Phase B — Admin billing

- Gas reading CRUD (manual), respect gas toggle  
- Other charges from templates / ad-hoc, respect toggles  
- Generate/refresh month → upsert statements + lines; preserve future collections  
- Enable/disable already on settings; regenerate respects them  

### Phase C — Water + accounts

- Common water entry; split by N water-enabled flats  
- Collections (multiple per statement)  
- Ledger cash in/out (flat optional); dashboard balance + pending  

### Phase D — Gemini assist

- Photo capture/upload + Gemini suggest + confirm  

### Phase E — Import + polish

- Artisan migrator from old SQL/DB  
- Print/PDF, audit log, permission hardening  

---

## 9. Phase A suggested file layout

```text
app/Models/Building.php
app/Models/Flat.php                    (extend / align)
app/Models/BillType.php
app/Models/FlatBillTypeSetting.php
app/Models/MonthlyStatement.php
app/Models/StatementLine.php
app/Models/Role.php

app/Http/Controllers/Public/FlatController.php
app/Http/Controllers/Public/StatementController.php
app/Http/Controllers/Admin/FlatBillTypeSettingController.php

database/migrations/xxxx_create_buildings_table.php
database/migrations/xxxx_create_bill_types_table.php
...
database/seeders/BuildingSeeder.php
database/seeders/FlatSeeder.php
database/seeders/BillTypeSeeder.php
database/seeders/RoleSeeder.php

resources/views/public/home.blade.php
resources/views/public/flats/show.blade.php
resources/views/public/statements/gas.blade.php
resources/views/public/statements/others.blade.php
resources/views/admin/flats/bill-type-settings.blade.php

tests/Unit/WaterShareCalculatorTest.php
tests/Feature/PublicFlatStatementTest.php
```

Helper (Phase A or B):

```php
// WaterShareCalculator::share(total, enabledFlatCount): Decimal
// throws if enabledFlatCount === 0
```

---

## 10. How to continue from Cursor Desktop

1. Pull / check out branch `cursor/building-accounts-plan-25f9` (or merge this plan into `main`).  
2. Open this file: `docs/BUILDING_ACCOUNTS_PLAN.md`.  
3. Prompt example:

   > Implement **Phase A** from `docs/BUILDING_ACCOUNTS_PLAN.md`. Follow locked decisions. Create branch `cursor/building-accounts-phase-a-25f9`. Do not migrate legacy data yet.

4. After Phase A PR merges, proceed Phase B → C → D → E in order.  
5. Do not delete old billing controllers until Phase E cutover unless they conflict; prefer parallel new public routes first.

---

## 11. Open items (non-blocking)

- Exact UI chrome / design system (reuse current Bootstrap layout is fine for Phase A).  
- Gemini model id (choose at Phase D).  
- Whether offline/non-participating flats still appear on public home with ৳0 (recommended: **yes, show flat**).  
- Whether public pages show contact name/phone (recommended Phase A: **admin-only**; public shows flat code only unless you decide otherwise).  
- Cash-out categories list (define in Phase C).

---

## 12. One-line summary

Rebuild around **per-flat monthly statements**, **per–bill-type participation**, **role-based admin**, and a **building cashbook**; flats are `2A`…`10B` with contact name + 11-digit phone; ship Phase A foundation first; **import old gas history only after** the new app works.
