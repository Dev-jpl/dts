# DTS — Document Tracking System
# Claude Code Master Context File
# Read this fully before every task.
# Last updated: Chapter 1 + Chapter 2 complete

---

## Project Structure

```
/
├── server-laravel/          ← Laravel 12 REST API (PHP 8.2+)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/          ← All controllers here
│   │   │   │   └── Settings/         ← Settings sub-controllers
│   │   │   └── Requests/             ← Form request validators
│   │   ├── Models/                   ← Eloquent models
│   │   ├── Services/                 ← Business logic
│   │   ├── Reports/                  ← Report classes (Chapter 2)
│   │   ├── Notifications/            ← Laravel notification classes
│   │   └── Events/                   ← DocumentActivityLoggedEvent etc.
│   ├── database/
│   │   ├── migrations/               ← Never modify existing, always add new
│   │   └── seeders/
│   └── routes/
│       └── api.php                   ← All API routes
└── client/                  ← Vue 3 + TypeScript SPA
    └── src/
        ├── views/                    ← Page-level components
        │   ├── reports/              ← Report views
        │   ├── settings/             ← Settings views
        │   └── templates/            ← Template views
        ├── components/               ← Reusable UI components
        │   ├── dashboard/            ← Dashboard widgets
        │   ├── reports/              ← Report components
        │   ├── search/               ← Search components
        │   ├── templates/            ← Template components
        │   ├── notifications/        ← Notification components
        │   └── signatories/          ← Signatory components
        ├── composables/              ← All composables
        ├── stores/                   ← Pinia stores
        ├── services/                 ← API service functions
        ├── router/                   ← Vue Router (index.ts)
        └── api/                      ← Axios instance + interceptors
```

---

## Tech Stack

### Backend
- Laravel 12, PHP 8.2+
- Laravel Passport (OAuth2 — `auth:api` guard on ALL protected routes)
- PostgreSQL 15+
- Laravel Queue (jobs for async processing)
- barryvdh/laravel-dompdf (PDF export)
- maatwebsite/excel (Excel export)
- simplesoftwareio/simple-qrcode (QR code generation)

### Frontend
- Vue 3 + TypeScript + Vite
- Pinia (stores: auth, document, libraries, dashboard, reports, search, notifications)
- Vue Router 4
- Tailwind CSS v4
- Axios (configured in `client/src/api/index.ts`)
- chart.js + vue-chartjs (reports charts)
- date-fns (date utilities)
- laravel-echo + pusher-js (WebSocket)

---

## Implementation Status

### Chapter 1 — Transaction Flow
```
Phase 1 — DB Updates                ⬜ Pending
Phase 2 — Core Services             ⬜ Pending
Phase 3 — Backend Actions           ⬜ Pending
Phase 4 — Frontend                  ⬜ Pending
```

### Chapter 2 — Extended Modules
```
Module 1 — Dashboard                ⬜ Pending
Module 2 — Reports                  ⬜ Pending
Module 3 — Advanced Search          ⬜ Pending
Module 4 — Templating               ⬜ Pending
Module 5 — Notifications Center     ⬜ Pending
Module 6 — User & Office Settings   ⬜ Pending
Module 7 — Signatory Management     ⬜ Pending
```

> Update this section after completing each task.
> Mark done as: ✅ Done | 🔄 In Progress | ⬜ Pending

---

## Current API Routes (existing)

```
POST   /api/login
POST   /api/logout
GET    /api/me

POST   /api/upload/temp
POST   /api/transactions/create
GET    /api/transactions/{trxNo}/show
GET    /api/transactions/{trxNo}/history
POST   /api/transactions/{trxNo}/log_transaction
POST   /api/transactions/{trxNo}/release
POST   /api/transactions/{trxNo}/upload/commit
GET    /api/transactions/{trxNo}/comments        ← deprecate → replaced by notes
POST   /api/transactions/{trxNo}/comments        ← deprecate → replaced by notes
POST   /api/transactions/{trxNo}/receive
POST   /api/transactions/{trxNo}/forward

GET    /api/documents
GET    /api/documents/filters
GET    /api/documents/received

GET    /api/library/offices
GET    /api/library/signatories

GET    /api/incoming
GET    /api/incoming/for-action
GET    /api/incoming/actioned
GET    /api/incoming/overdue
GET    /api/incoming/counts
GET    /api/incoming/filters
```

---

## Routes to Add — Chapter 1

```
POST   /api/transactions/{trxNo}/subsequent-release
POST   /api/transactions/{trxNo}/done
POST   /api/transactions/{trxNo}/return              ← update existing
POST   /api/transactions/{trxNo}/reply
PATCH  /api/transactions/{trxNo}/recipients
POST   /api/documents/{docNo}/close
POST   /api/documents/close-bulk
PUT    /api/documents/{docNo}/re-release
POST   /api/documents/{docNo}/copy
GET    /api/documents/{docNo}/notes
POST   /api/documents/{docNo}/notes
```

---

## Routes to Add — Chapter 2

```
# Dashboard
GET    /api/dashboard
GET    /api/dashboard/for-action
GET    /api/dashboard/overdue
GET    /api/dashboard/outgoing
GET    /api/dashboard/drafts
GET    /api/dashboard/stats
GET    /api/dashboard/activity
GET    /api/dashboard/team              ← Superior role only
GET    /api/dashboard/system            ← Admin role only

# Reports
GET    /api/reports/office-performance
GET    /api/reports/pipeline
GET    /api/reports/compliance
GET    /api/reports/audit/{docNo}
GET    /api/reports/turnaround
GET    /api/reports/office-performance/export
GET    /api/reports/pipeline/export
GET    /api/reports/compliance/export
GET    /api/reports/audit/{docNo}/export    ← PDF only, never Excel
GET    /api/reports/turnaround/export

# Search
GET    /api/search
GET    /api/search/quick
GET    /api/search/saved
POST   /api/search/saved
DELETE /api/search/saved/{id}
GET    /api/search/filters

# Templates
GET    /api/templates
GET    /api/templates/personal
GET    /api/templates/office
GET    /api/templates/system
POST   /api/templates
GET    /api/templates/{id}
PUT    /api/templates/{id}
DELETE /api/templates/{id}
POST   /api/templates/{id}/duplicate
POST   /api/templates/{id}/use
POST   /api/documents/{docNo}/save-as-template

# Notifications
GET    /api/notifications
GET    /api/notifications/unread-count
PATCH  /api/notifications/{id}/read
PATCH  /api/notifications/read-all
DELETE /api/notifications/{id}
GET    /api/notifications/preferences
PUT    /api/notifications/preferences

# Settings
GET    /api/settings/profile
PUT    /api/settings/profile
PUT    /api/settings/password
GET    /api/settings/preferences
PUT    /api/settings/preferences
GET    /api/settings/sessions
DELETE /api/settings/sessions/{id}
GET    /api/settings/office
PUT    /api/settings/office
GET    /api/settings/office/members
GET    /api/settings/office/defaults
PUT    /api/settings/office/defaults

# Signatories
GET    /api/signatories/library
POST   /api/signatories/library
PUT    /api/signatories/library/{id}
DELETE /api/signatories/library/{id}
GET    /api/documents/{docNo}/signatories
POST   /api/documents/{docNo}/signatories
PUT    /api/documents/{docNo}/signatories/{id}
DELETE /api/documents/{docNo}/signatories/{id}
PATCH  /api/documents/{docNo}/signatories/reorder
```

---

## Database — Current State vs Target

### documents
```
CURRENT status enum:  Draft | Processing | Archived       ← WRONG
TARGET  status enum:  Draft | Active | Returned | Completed | Closed

ADD fields:
  allow_copy   boolean default false
  qr_code      varchar nullable
```

### document_transactions
```
CURRENT status enum:  Draft | Processing | Completed
TARGET  status enum:  Draft | Processing | Returned | Completed

ADD fields:
  parent_transaction_no  varchar nullable FK self-reference
  urgency_level          enum: Urgent|High|Normal|Routine default High
  due_date               date nullable
```

### document_transaction_logs
```
ADD to status enum:
  Done | Closed | Routing Halted | Document Revised |
  Recipient Added | Recipient Removed | Recipients Reordered

ADD field:
  reason   varchar nullable  ← REQUIRED on Return to Sender
```

### action_library
```
ADD fields:
  type                   enum: FA|FI
  default_urgency_level  enum: Urgent|High|Normal|Routine nullable
  reply_is_terminal      boolean default false
  requires_proof         boolean default false
  proof_description      varchar nullable
```

### document_type_library
```
ADD fields:
  default_urgency_level  enum: Urgent|High|Normal|Routine nullable
```

### document_signatories
```
ADD fields (if not present):
  position   varchar(150) nullable
  office     varchar(150) nullable
  role       enum: Noted|Approved|Signed|Certified default Signed
  sequence   integer default 1
```

### CREATE — Chapter 1
```
document_versions
document_notes          ← replaces document_comments
```

### CREATE — Chapter 2
```
user_saved_searches
document_templates
document_template_recipients
notifications                     ← php artisan notifications:table
user_notification_preferences
user_preferences
signatory_library
```

### DEPRECATE (keep rows, stop inserting)
```
document_comments  ← replaced by document_notes
document_logs      ← replaced by document_transaction_logs
```

---

## Full Schema Reference — New Tables

### document_versions
```sql
id                   bigint PK auto-increment
document_no          varchar FK → documents
transaction_no       varchar FK → document_transactions
version_number       int
subject              varchar(255)
action_type          varchar(50)
document_type        varchar(50)
origin_type          varchar(50)
remarks              varchar(255) nullable
recipients_snapshot  json
changed_by_id        uuid FK → users
changed_by_name      varchar(150)
changed_at           timestamp
```

### document_notes
```sql
id               bigint PK auto-increment
document_no      varchar FK → documents
transaction_no   varchar FK → document_transactions
note             text NOT NULL
office_id        varchar FK → office_libraries
office_name      varchar(150)
created_by_id    uuid FK → users
created_by_name  varchar(150)
created_at       timestamp
updated_at       timestamp
```

### user_saved_searches
```sql
id           bigint PK auto-increment
user_id      uuid FK → users ON DELETE CASCADE
name         varchar(100)
filters_json json
sort_by      varchar(50) default 'relevance'
created_at   timestamp
updated_at   timestamp
```

### document_templates
```sql
id                bigint PK auto-increment
name              varchar(150)
description       text nullable
scope             enum: personal|office|system
document_type     varchar(100) nullable
action_type       varchar(100) nullable
routing_type      enum: Single|Multiple|Sequential nullable
urgency_level     enum: Urgent|High|Normal|Routine default High
origin_type       varchar(100) nullable
remarks_template  text nullable
created_by_id     uuid FK → users
created_by_name   varchar(150)
office_id         varchar FK → office_libraries
isActive          boolean default true
use_count         integer default 0
last_used_at      timestamp nullable
created_at        timestamp
updated_at        timestamp
```

### document_template_recipients
```sql
id              bigint PK auto-increment
template_id     bigint FK → document_templates ON DELETE CASCADE
office_id       varchar
office_name     varchar(150)
recipient_type  enum: default|cc|bcc default default
sequence        integer default 1
created_at      timestamp
updated_at      timestamp
```

### user_notification_preferences
```sql
id          bigint PK auto-increment
user_id     uuid FK → users ON DELETE CASCADE
event_type  varchar(100)
in_app      boolean default true
email       boolean default false
created_at  timestamp
updated_at  timestamp
UNIQUE(user_id, event_type)
```

### user_preferences
```sql
id                  bigint PK auto-increment
user_id             uuid UNIQUE FK → users ON DELETE CASCADE
date_format         varchar(20) default 'Y-m-d'
timezone            varchar(50) default 'Asia/Manila'
default_period      enum: week|month|quarter|year default month
dashboard_realtime  boolean default true
created_at          timestamp
updated_at          timestamp
```

### signatory_library
```sql
id           bigint PK auto-increment
name         varchar(150)
position     varchar(150) nullable
office_id    varchar FK → office_libraries
office_name  varchar(150)
isActive     boolean default true
created_at   timestamp
updated_at   timestamp
```

---

## Status Enums — Final Reference

```
documents.status:
  Draft | Active | Returned | Completed | Closed

document_transactions.status:
  Draft | Processing | Returned | Completed

document_transaction_logs.status:
  Profiled | Released | Received | Forwarded | Returned To Sender |
  Done | Closed | Routing Halted | Document Revised |
  Recipient Added | Recipient Removed | Recipients Reordered
```

---

## Action Types — FA vs FI

```
FA (For Action) — must take terminal action beyond Receive:
  Appropriate Action, Urgent Action, Comment/Reaction/Response,
  Compliance/Implementation, Endorsement/Recommendation,
  Coding/Deposit/Preparation, Follow Up, Investigation/Verification,
  Draft of Reply, Approval

FI (For Information) — Receive IS the terminal action:
  Dissemination of Information, Your Information
```

### Action Library Full Reference
| Action | type | reply_is_terminal | requires_proof | default_urgency |
|--------|------|-------------------|----------------|-----------------|
| Appropriate Action | FA | false | true | null |
| Urgent Action | FA | false | true | Urgent (locked) |
| Dissemination of Information | FI | false | false | null |
| Comment/Reaction/Response | FA | true | false | null |
| Compliance/Implementation | FA | false | true | null |
| Endorsement/Recommendation | FA | false | true | null |
| Coding/Deposit/Preparation | FA | false | true | null |
| Follow Up | FA | false | false | null |
| Investigation/Verification | FA | false | true | null |
| Your Information | FI | false | false | null |
| Draft of Reply | FA | true | false | null |
| Approval | FA | false | true | null |

---

## Urgency Levels

```
Urgent  → 1 day   (auto-locked when action_type = Urgent Action)
High    → 3 days  (system default)
Normal  → 5 days
Routine → 7 days

Overdue clock starts: on Receive log timestamp — NOT on Release
Applies to: FA default recipients ONLY
Never applies to: FI, CC, BCC

Priority order for due date calculation:
  1. due_date set explicitly on transaction → use that
  2. urgency_level on transaction → received_at + threshold
  3. document_type.default_urgency_level → received_at + period
  4. System default → High (3 days) from received_at
```

---

## Recipient Rules

### isActive Lifecycle
```
Created on release                    → true
Receives                              → stays true (no change)
Subsequent Release — releasing party  → false
Subsequent Release — target           → true (reactivated)
Forward — forwarder                   → false
Mark as Done                          → false
Reply (reply_is_terminal=true)        → false
Reply (reply_is_terminal=false)       → stays true
Manage Recipients — removed           → false (SOFT ONLY — NEVER hard delete)
Return to Sender — ALL pending        → false
Force Close — ALL pending             → false
Sequential FA — after terminal action → false
Sequential FI — after Receive         → false (auto-advances to next)
```

### CC/BCC Permissions
```
                           Default  CC     BCC
Can Receive                ✅       ✅     ✅
Can Reply                  ✅       ✅     ✅
Can Add Official Note      ✅       ✅     ✅
Can Forward                ✅       ❌     ❌
Can Return to Sender       ✅       ❌     ❌
Can Mark as Done           ✅       ❌     ❌
Can Subsequent Release     ✅       ❌     ❌
Counts toward Completion   ✅       ❌     ❌
Part of Sequential order   ✅       ❌     ❌
Visible to other recipients✅       ✅     ❌ hidden
Receive notifies origin    ✅       ❌     ❌
Notes visible to all       ✅       ✅     ✅
```

---

## TransactionStatusService

### NEVER manually set status = Completed
### ALWAYS call TransactionStatusService::evaluate($transactionNo)

```
FI Transaction:
  Completed when ALL default isActive=true recipients have Received log

FA Transaction:
  Completed when ALL default isActive=true recipients have a terminal action log
  Terminal: Done | Forwarded | Returned To Sender | Released (subsequent)
            | Reply log (if reply_is_terminal=true)
  Receive alone does NOT complete an FA transaction

Document Completed:
  Only when ALL document_transactions for document_no are Completed
  Document stays Active if ANY transaction is still Processing

Reply → new document_no → independent — does NOT affect original document
```

### Routing Completion
```
Single:
  FA → Completed on first terminal action
  FI → Completed on Receive

Multiple:
  FA → Completed when ALL default recipients have terminal actions
  FI → Completed when ALL default recipients have Received
  Any Return → ALL pending isActive=false, Transaction+Document → Returned

Sequential:
  FA → each step terminal action → next activates → last step → Completed
  FI → each step Receives → next activates → last Receives → Completed
  Any Return → sequence halts, ALL pending isActive=false, → Returned
  Guard: ONLY lowest-sequence office with no terminal action can act
```

---

## Guard Order — Every Controller Action

```php
// 1. Find transaction
$transaction = DocumentTransaction::with(['document','recipients','logs'])
    ->where('transaction_no', $trxNo)->first();
if (!$transaction) {
    return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
}

// 2. Status check
if ($transaction->status !== 'Processing') {
    return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
}

// 3. Actor check — isActive=true recipient row for this office
$recipient = DocumentRecipient::where('transaction_no', $trxNo)
    ->where('office_id', $user->office_id)
    ->where('isActive', true)->first();
if (!$recipient) {
    return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
}

// 4. Duplicate action check
// 5. Sequential turn check (if applicable)
// 6. Additional guards (proof attachment, etc.)

// 7. Execute inside DB::transaction()
DB::transaction(function() use (...) {
    // ... logic
    TransactionStatusService::evaluate($transaction->transaction_no);
});
```

---

## API Response Format

```php
// Success
return response()->json([
    'success' => true,
    'message' => 'Human readable success message.',
    'data'    => $transaction->refresh()->load([
        'document', 'recipients', 'signatories', 'attachments', 'logs'
    ])
], 200);

// Validation error  → 422
// Not found         → 404
// Not authorized    → 403
// Already actioned  → 409
```

---

## All Actions — Quick Reference

| Action | Route | Key Guard | New TRX |
|--------|-------|-----------|---------|
| Initial Release | POST /{trxNo}/release | status=Draft, origin only | No |
| Subsequent Release | POST /{trxNo}/subsequent-release | isActive=true, Received, target registered | No |
| Receive | POST /{trxNo}/receive | isActive=true, no dup, seq turn | No |
| Mark as Done | POST /{trxNo}/done | FA+default only, proof if required | No |
| Forward | POST /{trxNo}/forward | isActive=true, Received, target NOT registered | Yes (Forward) |
| Return to Sender | POST /{trxNo}/return | isActive=true, Received, default, reason+remarks | No |
| Reply | POST /{trxNo}/reply | isActive=true, Received, any type | Yes + new doc |
| Close Single | POST /documents/{docNo}/close | origin only, remarks required | No |
| Close Bulk | POST /documents/close-bulk | origin, Completed docs only | No |
| Edit & Re-release | PUT /documents/{docNo}/re-release | origin, doc=Returned | Yes (Default) |
| Copy to New | POST /documents/{docNo}/copy | origin only | Yes + new doc |
| Manage Recipients | PATCH /{trxNo}/recipients | origin, Processing, atomic | No |
| Official Notes | POST /documents/{docNo}/notes | active participant, not Closed | No |

---

## Notification Triggers

```
Initial Release        → All recipients (including CC/BCC)
Subsequent Release     → Target office only
Receive (default)      → Origin office
Receive (CC/BCC)       → Nobody
Sequential next step   → Next recipient in sequence
Mark as Done           → Origin office
Forward                → New recipient(s)
Return to Sender       → Origin + all halted pending (reason+remarks included)
Routing Halted         → Each halted office (reason+remarks included)
Reply                  → Origin office
Official Note added    → All active participants
Overdue threshold      → Recipient + Origin office
Close (forced)         → Pending recipients if routing halted
```

---

## QR Code

```php
use SimpleSoftwareIO\QRCode\Facades\QrCode;

// Generate on document CREATE (not on Release)
$qrCode = base64_encode(
    QrCode::format('svg')->size(200)->generate(
        config('app.url') . '/view/' . $documentNo
    )
);
// Save to documents.qr_code
```

---

## Dashboard — Complete Spec

```
Default route:  /dashboard — landing page after login for ALL users
Layout:         Fixed. Role-based content.
Refresh:        Real-time via WebSocket. Fallback: 60s polling.
Roles:          Regular User | Superior Office | Admin

Widget order (top to bottom):
  1. OVERDUE          Critical — red — FA past urgency threshold
  2. FOR ACTION       High — isActive=true, not yet Received, my turn
  3. PENDING RELEASE  My drafts not yet released
  4. MY OUTGOING      Active | Returned | Completed sub-tabs
  5. QUICK STATS      Period selector + delta vs previous period
  6. ACTIVITY FEED    Last 20 actions that directly affect me
  7. TEAM PERFORMANCE Superior + Admin only
  8. SYSTEM HEALTH    Admin only

Quick actions (inline confirm modal — no navigation):
  Receive    → confirm modal
  Release    → confirm modal (prerequisites must be met)
  Close      → confirm modal (status=Completed only, remarks required)

Force navigation to ViewDocument (never inline):
  Forward | Return to Sender | Mark as Done | Reply
  Manage Recipients | Edit & Re-release | Subsequent Release

No new DB tables — all derived from Chapter 1 schema.
```

---

## Reports — Complete Spec

```
5 report types:

1. Office Performance
   GET /api/reports/office-performance
   Export: PDF + Excel
   Metrics: received, on-time rate, avg time to receive,
            avg time to complete, return rate, return reasons

2. Document Pipeline
   GET /api/reports/pipeline
   Export: PDF + Excel
   Metrics: by status, overdue list, aged documents

3. ISO Compliance
   GET /api/reports/compliance
   Export: PDF + Excel
   ISO: 9001 (9.1.1, 9.1.3, 8.7, 10.2) + 15489 (8.3, 8.4, 9.8)

4. Transaction Audit
   GET /api/reports/audit/{docNo}
   Export: PDF ONLY — never Excel (integrity)
   Content: full log, versions, parties, attachments, notes

5. Individual Turnaround
   GET /api/reports/turnaround
   Export: PDF + Excel
   Metrics: breakdown by action type, doc type, trend over time

Access control (enforced by ReportAccessService):
  Regular user → own office data only
  Superior     → own + all subordinate offices
  Admin        → all offices, all data

Performance metrics definitions:
  Time to Receive:  Released log → Received log
  Time to Complete: Received log → terminal action log
  On Time:          Time to Complete <= urgency threshold (or due_date)
  Return Rate:      Count(Returned To Sender) / Count(Received) * 100
```

---

## Search — Complete Spec

```
Two entry points:
  GlobalSearchBar.vue  → nav header, quick dropdown (top 5)
  SearchView.vue       → /search, full filter panel + results

Filters:
  document_no, subject (full-text), document_type, action_type,
  origin_office, recipient_office, status, transaction_type,
  routing_type, urgency_level, overdue_only,
  has_attachments, has_notes, has_returned,
  return_reason, date ranges (released/received/completed/closed),
  days_since_released, days_overdue

Sort: relevance | date | status | urgency | overdue

Full-text indexes (PostgreSQL GIN):
  documents.subject
  document_notes.note

Saved searches: personal only — user_saved_searches table
AI Assistant: planned — NOT in Chapter 2 scope
BCC: never exposed to non-origin callers in search results
```

---

## Templates — Complete Spec

```
Three scope levels:
  personal → any user creates, own use only
  office   → office head or admin creates, all office members use
  system   → admin creates, all users read

On use:
  Form pre-filled from template data
  Subject: always empty and focused — never stored
  Attachments: always fresh — never stored
  Recipients: pre-populated, fully editable (add/remove/reorder freely)
  use_count++ and last_used_at = now() on every use

Save as Template:
  [Save as Template] button on ViewDocument
  Origin office only
  Prompts for name + scope selection
  Pre-fills from document's current profiling

Duplicate:
  Any user can duplicate any visible template
  Creates personal copy — "Copy of [name]"
  Original unaffected
```

---

## Notifications — Complete Spec

```
Notification classes:
  DocumentReleasedNotification
  DocumentReceivedNotification
  DocumentReturnedNotification
  DocumentDoneNotification
  DocumentForwardedNotification
  RoutingHaltedNotification
  OverdueNotification
  OfficialNoteAddedNotification

Storage: notifications table (Laravel standard)
Persistence: permanent — not ephemeral
Unread count: badge on NotificationBell in nav header
Email: opt-in per user per event type (user_notification_preferences)

Each notification class:
  via(): ['database', ...($emailPref ? ['mail'] : [])]
  toDatabase(): { type, document_no, transaction_no,
                  subject, message, action_url, from_office }
```

---

## Key Business Rules — Never Violate

### Chapter 1
```
1.  NEVER hard delete DocumentRecipient — isActive=false only
2.  NEVER manually set Transaction=Completed — use TransactionStatusService
3.  FA: Receive alone NEVER completes a transaction
4.  FI: Receive IS the terminal action
5.  CC/BCC NEVER count toward completion
6.  Sequential: ONLY active step (lowest seq, no terminal action) can act
7.  Return to Sender: halts ALL routing — not just the returning office
8.  Forward: creates NEW transaction_no. Forwarder steps out permanently.
9.  Reply: creates NEW document_no entirely
10. Close: origin only, remarks required, any status except Draft/Closed
11. Manage Recipients remove: only if NO Received log on this transaction
12. QR code: generated on document CREATE, stored in documents.qr_code
13. Dissemination of Information: Multiple locked, auto-completes on Release
14. Urgent Action: urgency_level auto-locked to Urgent, cannot override
15. Bulk close: Completed documents ONLY
16. document_comments → deprecated, use document_notes
17. document_logs → deprecated, use document_transaction_logs
18. All multi-step DB ops MUST be inside DB::transaction()
19. Always return full refreshed transaction after every action
20. BCC recipients hidden from API response for non-origin callers
```

### Chapter 2
```
21. /dashboard is the default landing page after login — always
22. Dashboard quick actions: Receive, Release, Close ONLY
23. All other actions force navigation to ViewDocument
24. Close inline requires remarks even from dashboard
25. Dashboard real-time via WebSocket — fallback 60s polling
26. Team Performance: Superior + Admin only
27. System Health: Admin only
28. Reports access enforced by ReportAccessService on every endpoint
29. Transaction Audit export: PDF ONLY — never Excel
30. All other reports: PDF + Excel
31. Search access scope same as reports — enforced server-side
32. BCC never exposed in search results to non-origin callers
33. Saved searches: personal only — never shared
34. AI Assistant: planned — NOT implemented in Chapter 2
35. Template recipients: suggestions only — fully editable on use
36. Template subject: NEVER stored — always filled fresh
37. Template attachments: NEVER stored — always fresh
38. Save as Template: origin office only
39. Template create scope:
      personal → any user
      office   → office head or admin only
      system   → admin only
40. Notifications stored permanently — not ephemeral
41. Email notifications: opt-in per user per event type
42. All Chapter 2 endpoints: auth:api guard — no exceptions
43. Role checks: from auth token — never trust client-sent role
44. Dashboard stats show delta vs previous period
45. Reports: ReportAccessService::canAccessOffice() before every query
```

---

## Frontend Conventions

### Composables — All Files
```typescript
// Chapter 1
useTransaction.ts        → transaction action methods
useActionVisibility.ts   → button computed visibility
useToast.ts              → toast notifications
useDocumentNotes.ts      → Official Notes (replaces useComments)

// Chapter 2
useDashboard.ts          → dashboard data + WebSocket refresh
useQuickAction.ts        → inline Receive/Release/Close from dashboard
useReports.ts            → fetch + export reports
useSearch.ts             → search + saved searches
useTemplates.ts          → template CRUD + use + saveAsTemplate
useNotifications.ts      → notifications list + preferences
useSettings.ts           → profile + preferences + office
useSignatories.ts        → signatory library + document signatories
```

### useActionVisibility — All Computeds
```typescript
canReceive            // isActive=true, not received, isActiveSequentialStep
canRelease            // isOriginator, status=Draft
canSubsequentRelease  // isActive=true, has Received, default only
canMarkAsDone         // FA, isActive=true, has Received, default only
canForward            // isActive=true, has Received, default only
canReturn             // isActive=true, has Received, default only
canReply              // isActive=true, has Received (any recipient type)
canClose              // isOriginator only (not Draft, not Closed)
canManageRecipients   // isOriginator, status=Processing
```

### Pinia Stores
```typescript
// Existing
useAuthStore()            → auth state, user, office_id, role
useDocumentStore()        → current document + transaction
useLibraryStore()         → offices, action types, doc types

// Chapter 2
useDashboardStore()       → all widget data
useReportsStore()         → current report + filters
useSearchStore()          → results + filters + saved searches
useNotificationsStore()   → notifications list + unread count
```

### Modal Convention
```
Name:    [Verb]Modal.vue
Emits:   past tense — 'received', 'forwarded', 'returned', 'done', 'replied', 'closed'
```

### Error Handling
```typescript
try {
    const result = await someAction(trxNo, payload)
    toast.success(result.message)
    emit('action-completed')
} catch (e: any) {
    toast.error(e.response?.data?.message || e.message || 'Action failed')
}
```

---

## Vue Router — All Routes

```typescript
// Auth
{ path: '/login', component: LoginView }

// After login → always redirect to /dashboard
// router.beforeEach: authenticated + path=='/login' → push('/dashboard')

// Chapter 1
{ path: '/dashboard',              component: DashboardView }
{ path: '/incoming',               component: IncomingView }
{ path: '/documents',              component: MyDocumentsView }
{ path: '/documents/new',          component: ProfilingView }
{ path: '/documents/:docNo',       component: ViewDocumentView }
{ path: '/documents/:docNo/edit',  component: ReReleaseView }
{ path: '/new-document',           component: CopyToNewView }  // ?from={docNo}

// Chapter 2
{ path: '/reports',                      component: ReportsHomeView }
{ path: '/reports/office-performance',   component: OfficePerformanceView }
{ path: '/reports/pipeline',             component: PipelineView }
{ path: '/reports/compliance',           component: ComplianceView }
{ path: '/reports/audit/:docNo',         component: AuditView }
{ path: '/reports/turnaround',           component: TurnaroundView }
{ path: '/search',                       component: SearchView }
{ path: '/templates',                    component: TemplatesHomeView }
{ path: '/templates/create',             component: TemplateCreateView }
{ path: '/templates/:id/edit',           component: TemplateEditView }
{ path: '/notifications',                component: NotificationsView }
{ path: '/settings', component: SettingsLayout, children: [
    { path: 'profile',         component: ProfileView },
    { path: 'password',        component: PasswordView },
    { path: 'preferences',     component: PreferencesView },
    { path: 'notifications',   component: NotificationPrefsView },
    { path: 'sessions',        component: SessionsView },
    { path: 'office',          component: OfficeProfileView },
    { path: 'office/members',  component: OfficeMembersView },
    { path: 'office/defaults', component: OfficeDefaultsView },
]}
```

---

## App Navigation

```
Sidebar:
  Dashboard          /dashboard        all roles
  Incoming           /incoming         all roles
  My Documents       /documents        all roles
  Search             /search           all roles
  Reports            /reports          Superior + Admin only
  Templates          /templates        all roles
  Settings           /settings         all roles

Nav Header (left to right):
  [Sidebar toggle]  [GlobalSearchBar]  [NotificationBell {count}]  [UserAvatar]
```

---

## Migration Rules

```
NEVER modify an existing migration file.
ALWAYS create a new migration for schema changes.
Use Schema::table() to add columns to existing tables.
Always implement down() — migrations must be reversible.
Test rollback: php artisan migrate:rollback

PostgreSQL enum changes:
  Add new string column → migrate data → drop old → rename new
```

---

## Git Workflow

```
Branches:
  main          → production, protected, PR only
  develop       → integration, all features merge here
  feature/{x}   → one feature per branch, PR to develop
  fix/{x}       → bug fixes, PR to develop
  migration/{x} → DB only, reviewed separately

Commit format (Conventional Commits):
  feat(scope): description
  fix(scope): description
  migrate(scope): description
  refactor(scope): description
  test(scope): description

Examples:
  feat(dashboard): implement OverdueWidget with urgency sort
  feat(reports): add OfficePerformanceReport PDF export
  migrate(chapter2): create user_saved_searches table
  fix(sequential): enforce active step guard on Receive
  feat(frontend): add NotificationBell with unread count badge
```

---

## How to Give Claude Tasks

Be specific. Reference the spec. State the chapter and module.

### Good Examples
```
"Implement DashboardController forAction() method.
 Route: GET /api/dashboard/for-action.
 Returns document_recipients where office_id = auth office,
 isActive=true, no Received log since last activation.
 Sort: urgency_level (Urgent first) then released_at ASC.
 Chapter 2, Module 1 — Dashboard."

"Write the migration for create_document_templates_table.
 Full schema in CLAUDE.md. Include down() method. PostgreSQL."

"Implement ForActionWidget.vue.
 Shows documents awaiting this office's action.
 Sorted by urgency (Urgent top) then oldest first.
 Receive button → QuickActionModal inline confirm.
 All other actions → navigate to ViewDocument.
 Uses useDashboard composable. Chapter 2, Module 1."
```

### Bad Examples
```
"Add the dashboard"        ← too vague
"Fix the action buttons"   ← no context
"Update the database"      ← which table? which field?
```

---

## Session Tips

```
Start every session:
  "Read CLAUDE.md and tell me the current implementation status."

Lost context mid-session:
  "Re-read CLAUDE.md and the current state of [filename]."

Break complex tasks down:
  "Do only the migration first."
  "Now do the controller."
  "Now do the Vue component."

Always state the chapter and module:
  "We are in Chapter 1, Phase 3 — Return to Sender."
  "We are in Chapter 2, Module 1 — Dashboard."

After completing each task:
  "Update CLAUDE.md Implementation Status to mark [task] as done."
```
