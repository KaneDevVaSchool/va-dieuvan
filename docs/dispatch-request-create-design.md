# Dispatch Request Create Module — System Design Document

---

## 1. Overview

**Purpose**
Allow staff to submit a vehicle dispatch request through a guided multi-step wizard. The form captures the type of trip, requester details, trip timing, urgency, supporting documents, trip detail rows, and a final confirmation before submission to the backend API.

**Who uses it (roles)**
- Staff / employees who need transport (requesters)
- Administrative staff entering on behalf of someone (using source channel: paper, Zalo)
- The currently authenticated user is auto-populated as the default requester on mount

**Main business goal**
Replace paper-based or manual dispatch request intake with a structured, validated digital form that produces a persisted `dispatch_request` record and optionally attaches a supporting document.

---

## 2. Functional Flow

1. **User opens the page**
   - `onMounted` fires in `useDispatchRequestWizard`
   - `auth.fetchMe()` is called if `auth.user` is null
   - Legacy single-draft migration (`migrateV1SingleDraftToMulti`) runs
   - Active draft is loaded from `localStorage` (`loadDraftFromStorage`)
   - Authenticated user's `name` and `email` are pre-filled into the form if fields are empty
   - Search query inputs are synced with pre-filled requester/coordinator names
   - Phone numbers are sanitized (VN format)

2. **Load required data**
   - No pre-fetch of vehicles, drivers, or trips — all selection is done at submission time by the backend
   - Staff search (requester / coordinator) is on-demand via `searchUsersForDispatchForm(q)` with 350ms debounce; minimum 2 characters
   - Target options are static constants (`TARGET_OPTIONS`, 31 items) — no API call

3. **User inputs form (4 steps)**
   - **Step 1 — Trip Type:** User selects one of 4 trip types (tile card UI)
   - **Step 2 — Requester Info & Time:** Fills requester details, dates, urgency, purpose, file upload, targets, coordinator, source channel
   - **Step 3 — Trip Detail:** Fills trip rows (passenger/business/cargo tables) based on trip type
   - **Step 4 — Confirm & Notes:** Reviews context note and enters free-form notes

4. **Validation**
   - Per-step `canGoNext` computed gate prevents progression if required fields are missing
   - Pre-submit `validateBeforeApi()` re-validates all steps and redirects to the failing step
   - Inline real-time validation for email format and date order

5. **Submit request**
   - `doSubmit()` builds API payload from `form`, `passengerRows`, `businessRows`, `cargoRows`
   - `createDispatchRequest(payload, { idempotencyKey })` is called
   - If a `basisFile` exists and the request is created, `uploadAttachment()` is called separately
   - On success: active draft is deleted from `localStorage`, lists are refreshed, `created` ref is set

6. **Handle success / error**
   - **Success:** A green success banner appears below the wizard card with the request ID and status; a link to `/requests` is shown
   - **Error (API):** `error` ref is set with a formatted message, displayed in Step 4 as a rose alert box
   - **Attachment error:** Handled separately — form error is set but the request was created; user must re-attach manually
   - **Validation error:** User is redirected to the failing step; error message shown at top of Step 4

---

## 3. UI Structure

### Header Bar

- Title + draft label (saved time or "New session")
- **Cancel** — goes back or to `/requests` if submitted
- **Save Draft** — saves current state to localStorage
- **Drafts Library** — opens drafts modal
- **Clear Draft** — only visible when `activeDraftId` is set; opens confirmation modal
- **Primary CTA** — context-aware: "Go to Confirm" on steps 0–2, "Submit" on step 3; disabled when `headerPrimaryDisabled`

### Stepper / Navigation

- 4 step tabs in a horizontal scrollable strip
- Steps: Type → Info → Detail → Confirm
- Completed steps show a green checkmark
- Steps beyond `maxReachedStep` are disabled
- Current step has active border highlight
- `goStep(i)` allows backward navigation to visited steps only

### Main Card Content

#### Step 1 — Trip Type Selection

- 4 tile cards with icon, label, hint, optional badge
- Types: `door_to_door`, `point_to_point`, `business`, `cargo`
- Single-select; selected tile shows colored border

#### Step 2 — Requester Info & Time

- **Requester section** (left column on lg)
  - Search-as-you-type input → popover dropdown (autocomplete from staff API)
  - `Full Name`\* (text), `Email`\* (email with inline error), `Phone` (numeric, max 11 chars, VN normalization), `Unit` (text)
- **Time section** (right column on lg)
  - `Proposed Date`\* (date picker), `Date Needed`\* (date picker with date-order error)
  - `Is Urgent` toggle + `Urgent Reason`\* (conditionally required when urgent is on)
- **Purpose section** (full width)
  - Conditional radio group for `point_to_point` trip: "Point-to-Point" or "Extracurricular"
  - `Purpose`\* (textarea)
  - File upload zone (drag-and-drop + click): accepts PDF/JPG/PNG, max 10MB; shows attached file with remove button
- **Targets section** (left column on lg)
  - Scrollable checkbox list of 31 org unit options; shows count or warning if none selected for P2P
- **Coordinator section** (right column on lg)
  - Same search pattern as requester; `Name`, `Email`, `Phone`
- **Source Channel** (full width, bottom bar)
  - Select: Portal / Zalo / Paper

#### Step 3 — Trip Detail (`DispatchWizardStep3`, lazy-loaded via `defineAsyncComponent`)

Only mounted when `step === 2`.

**Passenger/Business mode** (when `trip_type !== 'cargo'`):

- **E.1 table** (hidden for `business` trip type):
  - Columns: departure time\*, pickup place, return time, drop-off, guests, person in charge, unit price, extra fee, row total, notes
  - Add/remove rows; multi-day checkbox
- **E.1.1 panel** (shown for non-P2P-standard, non-business):
  - 3+ days checkbox, from/to date, days total, extra cost, weekday chip selector (Mon–Sun)
- **E.2 table** (hidden for `point_to_point`):
  - Same columns as E.1 plus waypoint column; add/remove rows
- **E.2.1 panel** (shown when `showPassengerTripExtras`):
  - Door pickup checkbox + cost, driver self checkbox + cost, after 21h checkbox + cost
  - Costs show extracurricular amounts if applicable

**Cargo mode** (when `trip_type === 'cargo'`):

- **Cargo table**:
  - Columns: name\*, qty, dimensions, weight, item notes, pickup time\*, pickup place, shipper contact, delivery time\*, delivery place, receiver contact, transport note, cost
  - Add/remove rows
- **Cargo extras panel**:
  - Cargo extra notes (textarea), porter checkbox + qty + cost, interprovincial checkbox + cost

#### Step 4 — Confirm

- Context note (different text for P2P vs other types)
- Free notes textarea (optional)
- API error display (rose alert)

### Modals (Teleported to `<body>`)

**Clear Draft Modal**
- Amber warning icon, confirm/cancel
- Triggers `confirmClearDraft()`
- `Escape` key closes; body scroll locked while open

**Drafts Library Modal**
- Lists all saved drafts sorted newest-first (max 25)
- Each draft: trip label, purpose preview (72 chars), saved time
- Actions per draft: Load, Delete
- "New Form" button starts a clean session
- `Escape` key closes; body scroll locked while open

### Navigation Footer (inside card)

- **Back** button (disabled on step 0)
- **Next** button (disabled when `!canGoNext`; hidden on step 3)

---

## 4. Data Model

### Main Entity: `DispatchRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `trip_type` | enum: `door_to_door` \| `point_to_point` \| `business` \| `cargo` | Yes | Type of transport |
| `source_channel` | enum: `portal` \| `zalo` \| `paper` | Yes | How the request was received |
| `origin` | string | No | Derived from first passenger/cargo pickup place |
| `destination` | string | No | Derived from first passenger/cargo drop-off place |
| `depart_at` | ISO 8601 datetime | Yes | Earliest departure from all trip rows |
| `arrive_by` | ISO 8601 datetime | No | Always null at creation |
| `passenger_count` | integer | No | Sum of guests across all passenger/business rows; null for cargo |
| `notes` | string | No | Free-form notes from Step 4 |
| `is_urgent` | boolean | Yes | Urgency flag |

### Supporting Row Models (client-side only)

**PassengerRow**

| Field | Type | Description |
|---|---|---|
| `depart_at` | datetime-local string | Departure datetime |
| `pickup` | string | Pickup location |
| `return_at` | datetime-local string | Return datetime |
| `dropoff` | string | Drop-off location |
| `guests` | string (number) | Passenger count |
| `unit_price` | string (VND formatted) | Base price |
| `extra_fee` | string (VND formatted) | Extra charge |
| `person_in_charge` | string | Responsible person |
| `notes` | string | Row-level notes |

**BusinessRow**

Same as PassengerRow plus:

| Field | Type | Description |
|---|---|---|
| `waypoint` | string | Intermediate stop |

**CargoRow**

| Field | Type | Description |
|---|---|---|
| `name` | string | Item name (required for row validation) |
| `qty` | string | Quantity |
| `dimensions` | string | Size (L×W×H) |
| `weight` | string | Weight |
| `item_notes` | string | Item-level notes |
| `pickup_at` | datetime-local string | Pickup datetime |
| `pickup_place` | string | Pickup address |
| `pickup_contact` | string | Shipper contact |
| `delivery_at` | datetime-local string | Delivery datetime |
| `delivery_place` | string | Delivery address |
| `delivery_contact` | string | Receiver contact |
| `transport_note` | string | Transport instructions |
| `cost` | string (VND formatted) | Transport cost |

### Draft Meta (localStorage)

| Field | Type | Description |
|---|---|---|
| `id` | string | e.g. `d-1714800000000-abc1234` |
| `savedAt` | number (ms timestamp) | When it was saved |
| `tripLabel` | string | Localized trip type short label |
| `purposeLine` | string | First 72 chars of purpose |

### Attachment

| Field | Value |
|---|---|
| `attachable_type` | `"dispatch_request"` |
| `attachable_id` | `created.id` (number) |
| `kind` | `"proposal_basis"` |
| `file` | File object |

---

## 5. Validation Rules

### Step 1 (Trip Type)

- `trip_type` must be selected (one of the 4 options)

### Step 2 (Info & Time)

- `requester_name`: required, must not be blank
- `requester_email`: required, must match `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- `purpose`: required, must not be blank
- `proposed_date`: required (YYYY-MM-DD)
- `date_needed`: required (YYYY-MM-DD); must be ≥ `proposed_date`
- `coordinator_email`: optional, but if provided must be a valid email
- `is_urgent = true` → `urgent_reason` required

### Step 3 (Trip Detail)

- `computedDepartAt` must not be empty (at least one row has a departure/pickup datetime)
- **Cargo:** at least one `cargoRow` must have a non-blank `name`
- **point_to_point:** at least one `passengerRow` must be filled (any significant field non-empty)
- **door_to_door / business:** at least one `passengerRow` or one `businessRow` must be filled

### File Upload

- Accepted types: `.pdf`, `.jpg`, `.jpeg`, `.png`, `image/*`, `application/pdf`
- Max size: 10 MB

### Phone Input

- Digits only; non-digits stripped on input
- If starts with `84` and length ≥ 10, normalize to `0{rest}`
- Max 11 characters

### Pre-submit Re-validation (`validateBeforeApi`)

- Re-checks all step constraints in order
- Redirects user to the first failing step with an error message
- Guards against `submitInFlight` duplicate submission

---

## 6. API Design

### POST `/dispatch-requests`

**Request payload:**

```json
{
  "trip_type": "point_to_point",
  "source_channel": "portal",
  "origin": "123 Nguyen Van Troi",
  "destination": "456 Le Van Sy",
  "depart_at": "2026-05-05T08:00:00.000Z",
  "arrive_by": null,
  "passenger_count": 3,
  "notes": "Free notes from step 4",
  "is_urgent": false
}
```

- Empty string values are stripped from payload before sending
- `idempotencyKey` is a generated UUID sent as a request header via `newIdempotencyKey()`

**Response (success):**

```json
{
  "id": 42,
  "status": "pending"
}
```

- `created.value` is set to the response object
- `id` and `status` are displayed in the success banner

### POST `/attachments`

Called after request creation if a `basisFile` is selected.

**Multipart form data:**

| Field | Value |
|---|---|
| `attachable_type` | `dispatch_request` |
| `attachable_id` | `{created.id}` |
| `kind` | `proposal_basis` |
| `file` | binary file |

- Failure is non-blocking; sets `error` but does not undo the dispatch request

### GET `/operational/users/search?q={query}`

Used by `searchUsersForDispatchForm(q)` for requester and coordinator search.

**Response:**

```json
[
  {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "a@example.com",
    "phone": "0901234567",
    "unit_name": "P. Kế Toán",
    "department_name": "Tài chính",
    "employee_code": "EMP001"
  }
]
```

- Called with minimum 2 characters, debounced 350ms
- Shared endpoint for both requester and coordinator search

---

## 7. State Management (Vue)

All state lives inside the `useDispatchRequestWizard()` composable. The view `DispatchRequestCreateView.vue` and child `DispatchWizardStep3` consume state via `provide/inject` using `DISPATCH_WIZARD_KEY`.

### Reactive State (`ref`)

| Name | Type | Source |
|---|---|---|
| `step` | `number` | local, 0–3 |
| `maxReachedStep` | `number` | local, tracks furthest visited step |
| `loading` | `boolean` | set during API call |
| `error` | `string` | API/validation error message |
| `created` | `object \| null` | API response on success |
| `form` | `FormObject` | all Step 2 & 4 fields |
| `passengerRows` | `PassengerRow[]` | Step 3 E.1 rows |
| `businessRows` | `BusinessRow[]` | Step 3 E.2 rows |
| `cargoRows` | `CargoRow[]` | Step 3 cargo rows |
| `basisFile` | `File \| null` | selected file object |
| `basisFileInput` | `HTMLInputElement` | DOM ref for file input |
| `basisDragOver` | `boolean` | drag-over visual state |
| `basisFileError` | `string` | file validation error |
| `requesterSearchQ` | `string` | search text input |
| `requesterSearchResults` | `User[]` | results from API |
| `requesterSearchLoading` | `boolean` | spinner state |
| `requesterDropdownOpen` | `boolean` | dropdown visibility |
| `requesterSearchError` | `string` | search API error |
| `coordinatorSearch*` | same pattern | same as requester |
| `activeDraftId` | `string \| null` | currently open draft ID |
| `savedDraftsList` | `DraftMeta[]` | list loaded from localStorage |
| `draftSavedAt` | `number \| null` | timestamp of last save |
| `hasDraftSnapshot` | `boolean` | whether any draft exists |
| `clearDraftModalOpen` | `boolean` | modal visibility |
| `draftsModalOpen` | `boolean` | modal visibility |

### Computed State

| Name | Depends On | Purpose |
|---|---|---|
| `steps` | i18n locale | Step labels array |
| `tripTypeOptions` | i18n locale | Trip card options |
| `isCargo` | `form.trip_type` | Branch cargo vs passenger logic |
| `isPointToPointTrip` | `form.trip_type` | Branch P2P logic |
| `canGoNext` | `step`, `form`, rows | Enable/disable Next button |
| `canSubmitApi` | `computedDepartAt` | Enable/disable Submit |
| `computedDepartAt` | rows, trip type | Derives earliest depart datetime |
| `passengerE1Total` | `passengerRows` | Sum unit_price + extra_fee |
| `passengerE2Total` | `businessRows` | Sum unit_price + extra_fee |
| `cargoTotal` | `cargoRows` | Sum cost |
| `extraCosts` | `form` porter/interprovincial | Additional cost totals |
| `step2DateOrderInvalid` | `form.proposed_date`, `form.date_needed` | Date order error flag |
| `step2RequesterEmailInvalid` | `form.requester_email` | Email format error flag |
| `step2CoordinatorEmailInvalid` | `form.coordinator_email` | Email format error flag |
| `draftLabel` | `activeDraftId`, `savedDraftsList`, `draftSavedAt`, `created` | Header status text |
| `headerPrimaryLabel` | `step`, `loading` | CTA button label |
| `headerPrimaryDisabled` | `loading`, `step`, `canGoNext`, `canSubmitApi` | CTA disabled state |

### Watchers

| Watcher | Trigger | Action |
|---|---|---|
| `watch(step)` | step advances | Updates `maxReachedStep` |
| `watch(form.proposed_date)` | date changes | Auto-syncs `date_needed` if not diverged |
| `watchEffect` ×2 | modal open state | Locks body scroll; attaches/removes `Escape` handler |

---

## 8. Error Handling

### Validation Errors

- **Inline real-time:** email format (rose ring + error text below input), date order (rose ring + error text)
- **Step-gate:** `canGoNext` is false → Next button disabled; no error message shown (implicit)
- **Pre-submit full pass:** `validateBeforeApi()` returns a string message → sets `error`, redirects to failing step

### API Errors

- `createDispatchRequest` failure → `formatApiError(e, fallback)` → sets `error.value` → shown in Step 4 rose alert box

### Attachment Errors

- `uploadAttachment` failure → sets `error.value` but does not roll back the created request
- User must manually retry the attachment

### File Errors

- File > 10 MB → sets `basisFileError`, clears file selection, resets the input element value

### Search Errors

- `searchUsersForDispatchForm` failure → sets `requesterSearchError` or `coordinatorSearchError` with formatted message; shown inline below the search input

### Draft Errors

- All localStorage operations wrapped in `try/catch` with silent fallback
- No crash on private browsing or storage quota exceeded

---

## 9. Edge Cases

### Missing / Stale Auth

- If `auth.user` is null on mount, `auth.fetchMe()` is attempted; failure is silently caught (router guard/401 handles redirect)
- Form still initializes; auto-fill step is skipped if `auth.user` is null after fetch

### Deleted or Invalid Draft

- If `draftActiveStorageKey` points to a missing draft item, the system falls back to the most recent item in the draft list
- If the list is empty, `hasDraftSnapshot` is false and a clean form starts

### Draft Version Migration (v1 → v2)

- On mount, `migrateV1SingleDraftToMulti` converts the legacy single-draft key to the multi-draft structure
- If v2 list already exists, the v1 key is silently removed
- Migration is idempotent

### Duplicate Submission

- `submitInFlight` flag (plain boolean, not reactive) prevents double-fire while `doSubmit` is in flight
- `loading = true` also disables the primary button via `headerPrimaryDisabled`
- Idempotency key is generated per submit attempt; backend should honor it to prevent duplicate records

### Trip Type Switching After Step 3

- Row data is preserved in all three arrays regardless of trip type
- `trim*RowsInPlace()` is called before save to remove empty rows
- After type switch, the correct table is rendered based on `isCargo` / `isPointToPointTrip`

### Max Draft Limit Exceeded

- `MAX_SAVED_DRAFTS = 25`; when exceeded, oldest drafts are pruned from the list and their localStorage items removed
- If the pruned draft was the active one, `activeDraftId` is reset to null

### Coordinator Email Invalid but Not Required

- Coordinator email is optional but validated if filled
- `canGoNext` in step 1 checks `!step2CoordinatorEmailInvalid` even though the field is not required
- Invalid coordinator email blocks step progression

### P2P Extracurricular Sub-flow

- When `trip_type = 'point_to_point'` AND `point_purpose_kind = 'extracurricular'`, sections E.1.1 and E.2.1 become visible with extracurricular cost copy
- This is a display-only branch; the underlying row data structure is identical

---

## 10. Performance Considerations

### API Call Minimization

- Staff search debounced 350ms, requires ≥ 2 characters; no call on mount
- No pre-loading of vehicles, drivers, or trips
- `DispatchWizardStep3` loaded via `defineAsyncComponent` — chunked separately, only mounted when `step === 2`

### Re-render Control

- Steps 0–1 use `v-show` (DOM kept, no remount cost on step switch)
- Step 3 uses `v-if` (mounted/unmounted to control async chunk loading)
- Step 4 uses `v-if`
- Row arrays use stable `:key` patterns (`'e1-' + idx`, `'e2-' + idx`)

### LocalStorage Access

- All reads/writes are synchronous; confined to mount and explicit user actions (save, load, delete)
- No watchers that write to localStorage on every keystroke

### Currency Formatting

- `formatVndWhileTyping` called on `input` events only, not on every render
- `formatCurrency` (Intl.NumberFormat) called only in computed totals and display cells, not inside watchers

### Search Timer Optimization

- `requesterSearchTimer` and `coordinatorSearchTimer` are plain module-level variables, not reactive refs — avoids reactive tracking overhead on every keystroke

---

## 11. Security

### Authorization

- Route is protected by router guard (`auth.fetchMe()` failure leads to 401 redirect)
- Backend must independently enforce authorization on `POST /dispatch-requests`

### Input Validation

- Phone inputs sanitized to digits-only before storage and submission
- Email fields validated client-side before API call; backend must also validate
- File uploads restricted to PDF/image MIME types and 10 MB client-side; backend must re-validate independently

### Idempotency

- Each submission generates a unique idempotency key via `newIdempotencyKey()`
- Prevents duplicate record creation on network retry or double-click

### Draft Storage Isolation

- Drafts stored per-user via `userId`-namespaced localStorage keys
- Prevents cross-user data contamination on shared browsers

### Prevent Invalid References

- Origin/destination derived from user-typed strings only — no stale foreign key references
- `depart_at` converted to ISO 8601 via `toIsoMaybe()` before sending; malformed values produce `null` which the backend must reject

### Attachment Upload

- Uploaded only after the dispatch request is confirmed created (valid `created.id`)
- Upload failure is surfaced to the user; no silent data loss
