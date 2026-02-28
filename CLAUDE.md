# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# DTS — Document Tracking System

---

## Development Commands

### Run everything (recommended for development)
```bash
# From server-laravel/ — starts Laravel server, queue listener, Pail log viewer, and Vite dev server concurrently
cd server-laravel && composer run dev
```

### Individual processes
```bash
# Backend API server only
cd server-laravel && php artisan serve

# Frontend dev server only (Vite)
cd client && npm run dev

# Queue worker
cd server-laravel && php artisan queue:listen --tries=1 --timeout=0
```

### Build
```bash
# Frontend production build (includes TypeScript check)
cd client && npm run build

# TypeScript check only
cd client && npm run type-check
```

### Tests & Linting
```bash
# Run all backend tests (Pest)
cd server-laravel && composer run test

# Run a single test file or filter
cd server-laravel && php artisan test --filter=TestClassName

# PHP code style (Laravel Pint)
cd server-laravel && ./vendor/bin/pint
```

### Database
```bash
cd server-laravel && php artisan migrate
cd server-laravel && php artisan db:seed
```

---

## Project Structure

```
/
├── server-laravel/          ← Laravel 12 REST API (PHP 8.2+)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/     ← All controllers here
│   │   │   └── Requests/        ← Form request validators
│   │   ├── Models/              ← Eloquent models
│   │   ├── Services/            ← Business logic (TransactionStatusService etc.)
│   │   └── Events/              ← DocumentActivityLoggedEvent etc.
│   ├── database/
│   │   ├── migrations/          ← Never modify existing, always add new
│   │   └── seeders/
│   └── routes/
│       └── api.php              ← All API routes
└── client/                  ← Vue 3 + TypeScript SPA
    └── src/
        ├── views/               ← Page-level components
        ├── components/          ← Reusable UI components
        ├── composables/         ← useTransaction.ts, useActionVisibility.ts etc.
        ├── stores/              ← Pinia stores (auth, document, libraries)
        ├── services/            ← API service functions
        ├── router/              ← Vue Router (index.ts)
        └── api/                 ← Axios instance + interceptors
```

---

## Tech Stack

### Backend
- Laravel 12, PHP 8.2+
- Laravel Passport (OAuth2 — `auth:api` guard on all protected routes)
- PostgreSQL
- Laravel Queue (jobs for async processing)
- Events: `DocumentActivityLoggedEvent`

### Frontend
- Vue 3 + TypeScript + Vite
- Pinia (stores: auth, document, libraries)
- Vue Router 4
- Tailwind CSS v4
- Axios (configured in `client/src/api/index.ts`)

---

## Current API Routes

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
GET    /api/transactions/{trxNo}/comments        ← to be replaced by notes
POST   /api/transactions/{trxNo}/comments        ← to be replaced by notes
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

### Routes to Add
```
POST   /api/transactions/{trxNo}/subsequent-release
POST   /api/transactions/{trxNo}/done
POST   /api/transactions/{trxNo}/return             ← update existing
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

### CREATE these tables (do not exist yet)
```
document_versions
document_notes          ← replaces document_comments
user_saved_searches     ← Chapter 2
document_templates      ← Chapter 2
document_template_recipients ← Chapter 2
```

### DEPRECATE (keep rows, stop inserting)
```
document_comments  ← replaced by document_notes
document_logs      ← replaced by document_transaction_logs
```

---

## Status Enums — FINAL REFERENCE

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

## Action Types — FA vs FI Classification

```
FA (For Action) — recipient must take terminal action:
  Appropriate Action, Urgent Action, Comment/Reaction/Response,
  Compliance/Implementation, Endorsement/Recommendation,
  Coding/Deposit/Preparation, Follow Up, Investigation/Verification,
  Draft of Reply, Approval

FI (For Information) — Receive is the terminal action:
  Dissemination of Information, Your Information
```

### Action Library Full Reference
| Action | type | reply_is_terminal | requires_proof | default_urgency |
|--------|------|-------------------|----------------|-----------------|
| Appropriate Action | FA | false | true | null |
| Urgent Action | FA | false | true | Urgent locked |
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

Overdue clock starts: on Receive log timestamp (NOT on Release)
Applies to: FA recipients only (never FI, never CC/BCC)

Priority order:
  1. due_date set explicitly → use that date
  2. urgency_level on transaction → received_at + threshold
  3. document_type.default_urgency_level → received_at + period
  4. System default → High (3 days)
```

---

## Recipient Rules

### isActive Lifecycle
```
Created on release                    → true
Receives                              → stays true
Subsequent Release — releasing party  → false
Subsequent Release — target           → true (reactivated)
Forward — forwarder                   → false
Mark as Done                          → false
Reply (reply_is_terminal=true)        → false
Reply (reply_is_terminal=false)       → stays true
Manage Recipients — removed           → false (SOFT ONLY — NEVER hard delete)
Return to Sender — all pending        → false
Force Close — all pending             → false
Sequential FA — after terminal action → false
Sequential FI — after Receive         → false (auto-advances)
```

### CC/BCC Permissions
```
                         Default  CC    BCC
Can Receive              ✅       ✅    ✅
Can Reply                ✅       ✅    ✅
Can Add Official Note    ✅       ✅    ✅
Can Forward              ✅       ❌    ❌
Can Return to Sender     ✅       ❌    ❌
Can Mark as Done         ✅       ❌    ❌
Can Subsequent Release   ✅       ❌    ❌
Counts toward Completion ✅       ❌    ❌
In Sequential order      ✅       ❌    ❌
Visible to others        ✅       ✅    ❌ hidden
Receive notifies origin  ✅       ❌    ❌
```

---

## TransactionStatusService — Rules

### NEVER manually set status = Completed
### ALWAYS call TransactionStatusService::evaluate($transactionNo)

```
FI Transaction:
  Completed when ALL default isActive=true recipients have Received log

FA Transaction:
  Completed when ALL default isActive=true recipients have terminal action log
  Terminal actions: Done | Forwarded | Returned To Sender | Released (subsequent)
                    | Reply log (if reply_is_terminal=true)
  Receive alone does NOT complete an FA transaction

Document Completed:
  Only when ALL document_transactions linked to document_no are Completed
  Document stays Active if ANY transaction is Processing

Reply transactions → new document_no → independent, do NOT affect original
```

### Routing Completion Rules
```
Single:
  FA → Completed on first terminal action
  FI → Completed on Receive

Multiple:
  FA → Completed when ALL default recipients have terminal actions
  FI → Completed when ALL default recipients have Received
  Any Return → ALL pending isActive→false, Transaction→Returned, Document→Returned

Sequential:
  FA → each step terminal action → next activates → last step terminal → Completed
  FI → each step Receives → next activates → last step Receives → Completed
  Any Return → sequence halts, all pending isActive→false, Transaction+Document→Returned
  Guard: only lowest-sequence office with no terminal action can act
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

// 3. Actor check — is this user's office a valid actor?
// (varies per action — check isActive=true on recipient row)

// 4. Duplicate action check — no duplicate log
// (varies per action — check logs for existing entry)

// 5. Sequential turn check (if applicable)
// Only for sequential routing — verify this is the active step

// 6. Additional guards (e.g. proof attachment for Mark as Done)

// 7. Execute inside DB::transaction()
DB::transaction(function() use (...) { ... });
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

// Validation error
return response()->json([
    'success' => false,
    'message' => 'Human readable error message.',
], 422);

// Not found
return response()->json([
    'success' => false,
    'message' => 'Resource not found.',
], 404);

// Conflict (duplicate action)
return response()->json([
    'success' => false,
    'message' => 'Action already performed.',
], 409);
```

---

## All Actions — Quick Reference

| Action | Route | Key Guard | Creates New TRX |
|--------|-------|-----------|-----------------|
| Initial Release | POST /{trxNo}/release | status=Draft, origin only | No |
| Subsequent Release | POST /{trxNo}/subsequent-release | isActive=true, has Received, target is registered | No |
| Receive | POST /{trxNo}/receive | isActive=true, no dup Received, seq turn | No |
| Mark as Done | POST /{trxNo}/done | FA+default only, proof if required | No |
| Forward | POST /{trxNo}/forward | isActive=true, target NOT registered, default only | Yes (Forward) |
| Return to Sender | POST /{trxNo}/return | isActive=true, has Received, default only, reason required | No |
| Reply | POST /{trxNo}/reply | isActive=true, has Received, any type | Yes (Reply) + new doc |
| Close Single | POST /documents/{docNo}/close | origin only, remarks required | No |
| Close Bulk | POST /documents/close-bulk | origin only, Completed docs only | No |
| Edit & Re-release | PUT /documents/{docNo}/re-release | origin, doc=Returned | Yes (Default) |
| Copy to New | POST /documents/{docNo}/copy | origin only | Yes + new doc |
| Manage Recipients | PATCH /{trxNo}/recipients | origin, Processing, atomic | No |
| Official Notes | POST /documents/{docNo}/notes | active participant, doc not Closed | No |

---

## Notification Triggers

```
Initial Release        → All recipients including CC/BCC
Subsequent Release     → Target office only
Receive (default)      → Origin office
Receive (CC/BCC)       → Nobody
Sequential step active → Next recipient in sequence
Mark as Done           → Origin office
Forward                → New recipient(s)
Return to Sender       → Origin + all halted pending (with reason+remarks)
Routing Halted         → Each halted office (with reason+remarks)
Reply                  → Origin office
Official Note added    → All active participants
Overdue threshold      → Recipient + Origin office
Close (forced)         → Pending recipients if routing halted
```

---

## Frontend Conventions

### Composables Pattern
```typescript
// All API calls through composables — never in components directly
// useTransaction.ts → all action methods
// useActionVisibility.ts → all button computed visibility
// useToast.ts → toast notifications
// useDocumentNotes.ts → Official Notes (replaces useComments)
```

### useActionVisibility — All Required Computeds
```typescript
canReceive            // isActive=true, not received, isActiveSequentialStep
canRelease            // isOriginator, status=Draft
canSubsequentRelease  // isActive=true, has Received, default only
canMarkAsDone         // FA action type, isActive=true, has Received, default only
canForward            // isActive=true, has Received, default only
canReturn             // isActive=true, has Received, default only, not returned
canReply              // isActive=true, has Received (all recipient types)
canClose              // isOriginator only (any status except Draft/Closed)
canManageRecipients   // isOriginator, status=Processing
```

### Modal Convention
```
Name:    [Verb]Modal.vue
Emits:   past tense — 'received', 'forwarded', 'returned', 'done', 'replied', 'closed'
Example: ReceiveModal.vue → emits 'received'
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

## QR Code

```php
// Install: composer require simplesoftwareio/simple-qrcode
// Generate on document create (store method):

use SimpleSoftwareIO\QRCode\Facades\QrCode;

$qrCode = base64_encode(
    QrCode::format('svg')->size(200)->generate(
        config('app.url') . '/view/' . $documentNo
    )
);

// Save to documents.qr_code
```

---

## Official Notes Schema

```sql
document_notes:
  id               bigint auto-increment PK
  document_no      varchar FK → documents (scope: entire document)
  transaction_no   varchar FK → document_transactions (context only)
  note             text NOT NULL
  office_id        varchar FK → office_libraries
  office_name      varchar(150)
  created_by_id    uuid FK → users
  created_by_name  varchar(150)
  created_at       timestamp
  updated_at       timestamp
```

---

## Document Versions Schema

```sql
document_versions:
  id                   bigint auto-increment PK
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

---

## Migration Rules

```
NEVER modify an existing migration file.
ALWAYS create a new migration for each schema change.
Use Schema::table() for adding columns to existing tables.

Naming: YYYY_MM_DD_HHMMSS_descriptive_name.php

For PostgreSQL enum changes — add new column, migrate, drop old:
  $table->string('status_new')->default('Draft')->after('status');
  // migrate data in a separate seeder or data migration
  // then rename/drop
```

---

## Implementation Phase Order

```
Phase 1 — DB (parallel with all phases, do first)
  documents status enum update
  document_transactions: Returned status, parent_transaction_no, urgency_level, due_date
  document_transaction_logs: new statuses, reason field
  action_library: type, default_urgency_level, reply_is_terminal, requires_proof, proof_description
  document_type_library: default_urgency_level
  documents: allow_copy, qr_code
  CREATE document_versions
  CREATE document_notes

Phase 2 — Core Services
  TransactionStatusService → FA/FI split logic
  OverdueService → calculate overdue status
  NotificationService → unified notification dispatch

Phase 3 — Backend Actions
  Return to Sender (update existing)
  Subsequent Release (new)
  Mark as Done (new)
  Reply (new)
  Close single + bulk (new)
  Edit & Re-release (new)
  Copy to New Document (new)
  Manage Recipients (new)
  Official Notes routes (new)
  QR Code on create (update store)

Phase 4 — Frontend
  useActionVisibility full update
  All new modals
  My Documents tabs (Draft|Active|Returned|Completed|Closed)
  Incoming Documents tabs (All|For Action|Overdue|In Progress|Completed|Closed)
  ViewDocument side panel (Transaction Logs + Official Notes)
  Status labels update
```

---

## Key Business Rules — Never Violate

```
1.  NEVER hard delete DocumentRecipient — isActive=false only
2.  NEVER manually set Transaction→Completed — use TransactionStatusService
3.  FA: Receive alone NEVER completes a transaction
4.  FI: Receive IS the terminal action
5.  CC/BCC NEVER count toward completion
6.  Sequential: ONLY active step (lowest sequence, no terminal action) can act
7.  Return to Sender: halts ALL routing — not just the returning office
8.  Forward: creates NEW transaction_no. Forwarder steps out permanently.
9.  Reply: creates NEW document_no entirely
10. Close: origin only, always requires remarks, any status except Draft/Closed
11. Manage Recipients remove: only if NO Received log for that office on this transaction
12. QR code: generated on document create, stored in documents.qr_code
13. Dissemination of Information: Multiple routing locked, auto-completes on Release
14. Urgent Action: urgency_level auto-locked to Urgent, cannot be overridden
15. Bulk close: Completed documents ONLY — never Active/Returned/Draft
16. document_comments → deprecated, use document_notes
17. document_logs → deprecated, use document_transaction_logs
18. All DB multi-step operations MUST be inside DB::transaction()
19. Always return full refreshed transaction after every action
20. BCC recipients hidden from API response for non-origin callers
```
