# MikroTik Cards System V2 — Architecture & Design Spec

**Date:** 2026-06-21
**System:** Laravel 12 / PHP 8.2 / MySQL / RouterOS v6.49.19
**Author:** Backend Architecture Design

---

## Overview

V2 extends the existing V1 system (MikroTik User Manager card generation, Jeeb wallet webhook, queue-based processing) with five major features: Points Ledger System, Instant Delivery & Auto-Revoke, Advanced User Manager Module, Gamification/Challenges Engine, and V2 REST APIs.

---

## 1. Database Schema Changes

### 1.1 New Tables

#### `points_balance` — Per-user point balance (one row per user)

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK auto-increment |
| user_id | bigint | FK → users, **unique** |
| balance | decimal(12,2) | default 0.00 |
| total_earned | decimal(12,2) | default 0.00 |
| total_spent | decimal(12,2) | default 0.00 |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `points_transactions` — Append-only point movement ledger

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| type | enum('credit','debit') | |
| amount | decimal(12,2) | |
| balance_before | decimal(12,2) | |
| balance_after | decimal(12,2) | |
| reason | string(255) | e.g. points_recharge, card_purchase, challenge_reward, admin_adjustment, revoked |
| reference_type | enum('webhook','verify','admin','challenge') | |
| reference_id | bigint | nullable, polymorphic FK |
| created_at | timestamp | (no updated_at — append-only) |

Index: `(user_id, created_at)`

#### `system_settings` — Key-value configuration

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| key | string(100) | **unique** |
| value | text | |
| description | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

Pre-seeded keys:
- `point_price_yri` — سعر النقطة الواحدة بالريال اليمني
- `maintenance_mode` — `true`/`false`
- `jeeb_wallet_phone` — رقم محفظة جيب الخاصة بصاحب الشبكة
- `jeeb_wallet_full_name` — الاسم الرباعي المطابق لحساب صاحب الشبكة في محفظة جيب (للمطابقة مع الإشعارات)

#### `challenges` — Core challenge definition

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string(255) | |
| description | text | nullable |
| is_active | boolean | default false |
| starts_at | datetime | nullable |
| ends_at | datetime | nullable |
| max_completions | integer | default 0 (0 = unlimited) |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `challenge_conditions` — One challenge = multiple conditions (AND within group, OR between groups)

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| challenge_id | bigint | FK → challenges ON CASCADE |
| condition_type | enum('purchase_count','points_spent_total','points_spent_single','consecutive_days','profile_purchase','amount_threshold','custom_event') | |
| operator | enum('gte','eq','lte','between') | |
| value | json | flexible value (e.g. {"min":3}, {"profile_ids":[1,2],"count":2}) |
| logic_group | integer | default 0 |

#### `challenge_rewards` — One challenge = multiple rewards

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| challenge_id | bigint | FK → challenges ON CASCADE |
| reward_type | enum('points','free_card','profile_upgrade','custom') | |
| value | json | e.g. {"points":50}, {"profile_id":3} |
| priority | integer | default 0 |

#### `user_challenges` — Per-user challenge progress

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| challenge_id | bigint | FK → challenges |
| progress_data | json | flexible per-condition progress |
| started_at | timestamp | |
| completed_at | timestamp | nullable |
| reward_claimed_at | timestamp | nullable |
| completion_count | integer | default 0 |

Unique: `(user_id, challenge_id)`

#### `batch_generations` — Audit trail for bulk card creation

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| admin_id | bigint | FK → admins |
| profile_id | bigint | FK → profiles |
| quantity | integer | |
| generated_count | integer | default 0 |
| status | enum('pending','processing','completed','failed','partially_completed') | default 'pending' |
| generation_config | json | **Flexible generation options (see below)** |
| created_at | timestamp | |
| updated_at | timestamp | |

**`generation_config` JSON structure:**
```json
{
    "credential_mode": "match" | "separate",
    "username_length": 10,
    "password_length": 10,
    "username_prefix": "UM-",
    "charset": "ABCDEFGHJKMNPQRSTUVWXYZ23456789",
    "exclude_chars": "0O1IL"
}
```

- `credential_mode: "match"` — username == password (V1 behavior)
- `credential_mode: "separate"` — username and password generated independently with different lengths
- `username_prefix` — optional prefix prepended to all generated usernames (e.g. "UM-", "NET-", "VIP-")
- `username_length` / `password_length` — configurable length (6–32 chars)
- `charset` — custom character set
- `exclude_chars` — characters to exclude for visual clarity

#### `maintenance_logs` — Router maintenance action audit

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| admin_id | bigint | FK → admins |
| action | enum('backup_db','clear_logs','rebuild_db','list_users','delete_user') | |
| status | enum('success','failed') | |
| raw_output | text | nullable |
| created_at | timestamp | |

### 1.2 Modified Tables

#### `transactions` — Add columns

| New Column | Type | Notes |
|------------|------|-------|
| type | enum('card_purchase','points_recharge') | default 'card_purchase' |
| verification_status | enum('pending_verification','verified','revoked') | nullable, only for instant-delivery |
| points_amount | decimal(12,2) | nullable |
| points_before | decimal(12,2) | nullable |
| points_after | decimal(12,2) | nullable |
| auto_revoke_at | timestamp | nullable |
| revoked_at | timestamp | nullable |
| revoke_job_dispatched | boolean | default false |

Add unique index: `(jeeb_reference, type)` — prevents duplicate processing.

#### `users` — Add columns

| New Column | Type | Notes |
|------------|------|-------|
| device_uuid | string(255) | nullable, for ban |
| is_banned | boolean | default false |
| banned_at | timestamp | nullable |
| ban_reason | text | nullable |

---

## 2. Points Ledger System

### 2.1 `PointsService`

Single service managing all point operations. Every method uses **DB transactions**.

```php
class PointsService {
    // Credit points (recharge, challenge reward, admin adjustment)
    credit(User $user, float $amount, string $reason,
           string $refType, ?int $refId): PointsTransaction

    // Debit points (card purchase, admin deduction)
    debit(User $user, float $amount, string $reason,
          string $refType, ?int $refId): PointsTransaction

    // Get current balance
    balance(User $user): float

    // Check if sufficient
    hasSufficient(User $user, float $amount): bool

    // Revert a transaction (for revoke)
    revert(Transaction $tx): void
}
```

### 2.2 Point Price

- Stored in `system_settings` key `point_price_yri`
- Only admin can change it from the panel
- Example: `point_price_yri = 10` → 1 point = 10 YRI
- Points calculated as: `floor(amount_yri / point_price_yri)`

### 2.3 Integration with Existing Flow

- Points recharge: `verify-transaction` → `PointsService::credit()` → points_transactions log
- Card purchase paid by points: new `POST /api/purchase-by-points` endpoint → `PointsService::debit()` → card generation
- Challenge rewards: `PointsService::credit()` with `refType=challenge`

---

## 3. Instant Delivery & Auto-Revoke

### 3.1 Verify Transaction Flow

```
Flutter captures Jeeb notification:
  { reference, amount, full_name, phone, user_id, profile_id? }

POST /api/v2/verify-transaction

Backend:
  1. Idempotency check: Cache::lock("verify_tx_{reference}", 30s)
  2. Check duplicate: transaction where jeeb_reference = reference
  3. Determine type:
     a. profile_id present → card purchase
     b. no profile_id → points recharge
  4. Execute:
     - Card purchase: generate in MikroTik, set verification_status=pending_verification
     - Points recharge: PointsService::credit(), set verification_status=pending_verification
  5. Set auto_revoke_at = now() + 5 minutes
  6. Dispatch AutoRevokeJob → delayed 5 minutes
  7. Return { success, card_credentials? }
```

### 3.2 Webhook Matching (Updated)

When admin's emulator sends webhook to `/api/webhook/jeeb`:
- Parse reference (as before)
- Look for transaction by `jeeb_reference`
- If found AND `verification_status = pending_verification`:
  - Set `verification_status = verified`
  - Cancel AutoRevokeJob (check if dispatched, skip if already running)
- If not found → proceed with existing V1 matching logic

### 3.3 AutoRevokeJob

```
class AutoRevokeJob implements ShouldQueue {
    $transactionId;
    $queue = 'cards';

    handle():
        $tx = Transaction::find($transactionId)
        if !$tx || $tx->verification_status !== 'pending_verification' → return

        DB::transaction:
            if type = card_purchase:
                MikroTikService::removeUser($tx->mikrotik_username)
                // Call /tool/user-manager/user/remove
            if type = points_recharge:
                PointsService::revert($tx)

            $tx->update(verification_status='revoked', revoked_at=now())
            $tx->user->update(is_banned=true, banned_at=now(),
                ban_reason='احتيال - تأكيد وهمي')
}
```

### 3.4 Ban Mechanism

- `users.is_banned = true` blocks all purchase/verify endpoints
- Admin can manually unban from panel
- Future: ban by `device_uuid` to prevent re-registration

---

## 4. Advanced User Manager Module

### 4.1 Profiles CRUD (Enhanced)

Current V1 profiles sync FROM MikroTik only. V2 adds:
- Edit profile details from admin panel → push to MikroTik via API
- Sync button: pull latest profiles from MikroTik
- Clone profile: duplicate an existing profile definition
- Status badges showing sync state (in-sync / modified / unsynced)

### 4.2 CardGeneratorService — Updated

The existing `CardGeneratorService::generateCredentials()` now accepts a config array:

```php
generateCredentials(array $config = []): array
// Returns ['username' => '...', 'password' => '...']
//
// Config options:
//   credential_mode: 'match' | 'separate'  (default: 'match')
//   username_length: int  (default: 10)
//   password_length: int  (default: 10)
//   username_prefix: string  (default: '')
//   charset: string  (default: 'ABCDEFGHJKMNPQRSTUVWXYZ23456789')
//   exclude_chars: string  (default: '0O1IL')
```

When `credential_mode = 'separate'`, username and password are generated independently using the same charset but potentially different lengths. The prefix is prepended to the username only.

### 4.3 Batch Card Generation

```
Admin panel:
  1. Select profile
  2. Enter quantity (1-1000)
  3. Configure generation options:
     - Credential mode: "username = password" or "username ≠ password"
     - Username length (6-32), Password length (6-32)
     - Username prefix (optional, e.g. "UM-", "VIP-")
     - Character set / excluded characters
  4. Click "توليد"
  5. Preview sample credentials before confirming

Backend:
  - Create BatchGeneration record (status=pending) with generation_config JSON
  - Dispatch GenerateMikrotikCardBatchJob
  - BatchJob dispatches N individual GenerateMikrotikCardJob(s), each receiving the config
  - Each card generated according to config:
    - match mode: username=password (10-char default, 0/O/1/I/L excluded)
    - separate mode: independent username & password with configured lengths
    - prefix prepended to username if set
  - On completion: BatchGeneration.generated_count++, status check
  - Live progress bar via polling or SSE
```

### 4.4 Voucher Print & Themes

**`voucher_themes` table:**
| Column | Type |
|--------|------|
| id | bigint PK |
| name | string(100) |
| blade_view | string(200) — e.g. `admin.vouchers.themes.classic` |
| thumbnail | string(255) — nullable, image path |
| is_default | boolean |

Admin workflow:
1. Select profile + quantity
2. Select theme
3. Preview (Blade rendered in iframe or modal)
4. Print (browser print or PDF via `barryvdh/laravel-dompdf`)

Each theme is a Blade partial that receives:
```php
$cards = [['username' => 'ABC123', 'password' => 'ABC123',
           'profile' => 'باقة 5000', 'expires_at' => '2026-07-21']]
```

### 4.5 Router Maintenance

Admin panel buttons → MikroTik API calls:

| Action | RouterOS Path | Method |
|--------|---------------|--------|
| Backup DB | `/tool/user-manager/database/backup` | Query write |
| Clear Logs | `/tool/user-manager/database/clear-logs` | Query write |
| Rebuild DB | `/tool/user-manager/database/rebuild` | Query write |
| List Users | `/tool/user-manager/user/print` | Query read |
| Remove User | `/tool/user-manager/user/remove` | Query write |

Each logged in `maintenance_logs` table. Errors shown as flash messages.

---

## 5. Gamification & Challenges

### 5.1 Event-Driven Architecture

Events (doers fire these):
- `App\Events\CardPurchased` — payload: user, transaction
- `App\Events\PointsCredited` — payload: user, points_transaction
- `App\Events\PointsSpent` — payload: user, points_transaction

Listener:
- `App\Listeners\CheckChallenges` — subscribed to all three events

### 5.2 Challenge Evaluation Logic

```php
class CheckChallenges {
    handle($event):
        $user = $event->user;
        $activeChallenges = Challenge::active()->get();

        foreach ($activeChallenges as $challenge):
            $progress = UserChallenge::firstOrCreate(user, challenge);
            $conditions = $challenge->conditions; // hasMany

            foreach ($conditions as $condition):
                $met = $this->evaluate($condition, $event, $progress);
                // Update progress_data JSON
            endforeach;

            if all_conditions_met($progress->progress_data):
                if $challenge->max_completions > 0
                   && $progress->completion_count >= $challenge->max_completions:
                    continue;
                $this->award($challenge, $user, $progress);
            endif;
        endforeach;
}
```

### 5.3 Condition Types

| Type | Evaluates | Value Example |
|------|-----------|---------------|
| `purchase_count` | Total card purchases by user | `{"min":3}` |
| `points_spent_total` | Accumulated points spent | `{"gte":100}` |
| `points_spent_single` | Points spent in one transaction | `{"gte":50}` |
| `consecutive_days` | Active consecutive days | `{"days":5}` |
| `profile_purchase` | Purchase specific profile N times | `{"profile_id":2,"count":3}` |
| `amount_threshold` | Single payment amount >= X | `{"min":"15000"}` |
| `custom_event` | Extensible for future event types | `{"event":"CardPurchased","count":1}` |

### 5.4 Reward Types

| Type | Effect | Value Example |
|------|--------|---------------|
| `points` | Credit points to user | `{"points":50}` |
| `free_card` | Generate free card with profile | `{"profile_id":3}` |
| `profile_upgrade` | Upgrade card to better profile | `{"target_profile_id":5}` |
| `custom` | Future extensibility | `{"handler":"CustomRewardClass"}` |

---

## 6. V2 REST APIs

### 6.1 `POST /api/v2/verify-transaction`

Request:
```json
{
    "user_id": 1,
    "reference": "JEEB-TXN-12345",
    "amount": 5000,
    "full_name": "محمد أحمد علي الحضرمي",
    "phone": "9677XXXXXXXX",
    "profile_id": 2
}
```

Response (card purchase):
```json
{
    "success": true,
    "type": "card_purchase",
    "verification_status": "pending_verification",
    "card": {
        "username": "ABC123DEFG",
        "password": "ABC123DEFG",
        "profile": "باقة 5000 ريال"
    },
    "message": "تم توليد الكرت. سيتم تأكيد الدفع خلال 5 دقائق."
}
```

Response (points recharge):
```json
{
    "success": true,
    "type": "points_recharge",
    "verification_status": "pending_verification",
    "points": {
        "credited": 500,
        "balance": 1500,
        "amount_paid": 5000,
        "point_price": 10
    },
    "message": "تم إضافة النقاط. سيتم تأكيد الدفع خلال 5 دقائق."
}
```

### 6.2 `GET /api/v2/network-status`

```json
{
    "success": true,
    "server_time": "2026-06-21T12:00:00+03:00",
    "router_connected": true,
    "queue_size": 3,
    "maintenance_mode": false
}
```

### 6.3 `GET /api/v2/app-config`

```json
{
    "success": true,
    "maintenance_mode": false,
    "point_price": 10,
    "jeeb_wallet_phone": "77XXXXXXX",
    "jeeb_wallet_full_name": "صاحب الشبكة",
    "profiles": [
        {"id": 1, "name": "باقة 5000", "price": 5000, "duration_hours": 24}
    ],
    "min_deposit_amount": 1000,
    "currency": "YRI"
}
```

### 6.4 `GET /api/v2/challenges?user_id=1`

```json
{
    "success": true,
    "active": [
        {
            "id": 1,
            "name": "تحدي 3 كروت",
            "description": "اشترِ 3 كروت في أسبوع",
            "progress": {"purchase_count": 2},
            "conditions": [
                {"type": "purchase_count", "operator": "gte", "value": {"min": 3}}
            ],
            "rewards": [
                {"type": "points", "value": {"points": 50}}
            ],
            "completed": false
        }
    ]
}
```

---

## 7. New Services & Key Classes

```
app/
├── Services/
│   ├── PointsService.php              — Point ledger operations
│   ├── InstantDeliveryService.php     — Verify-transaction orchestration
│   └── MikroTikMaintenanceService.php — Router backup/rebuild/clean
├── Jobs/
│   ├── AutoRevokeJob.php              — 5-min revoke check
│   └── GenerateMikrotikCardBatchJob.php — Batch card generation dispatcher
├── Events/
│   ├── CardPurchased.php
│   ├── PointsCredited.php
│   └── PointsSpent.php
├── Listeners/
│   └── CheckChallenges.php            — Challenge evaluation engine
├── Models/
│   ├── PointsBalance.php
│   ├── PointsTransaction.php          — Append-only (no updated_at, no delete)
│   ├── SystemSetting.php
│   ├── Challenge.php
│   ├── ChallengeCondition.php
│   ├── ChallengeReward.php
│   ├── UserChallenge.php
│   ├── BatchGeneration.php
│   └── MaintenanceLog.php
├── Http/Controllers/Api/
│   └── V2Controller.php               — All V2 endpoints
└── Http/Controllers/Admin/
    ├── PointsController.php           — Admin point management
    ├── ChallengeController.php        — Challenges CRUD
    ├── BatchGenerationController.php  — Batch card generation
    ├── VoucherController.php          — Card print themes
    └── MaintenanceController.php      — Router maintenance actions
```

---

## 8. Admin Panel Routes (New/Modified)

| Method | Route | Controller |
|--------|-------|------------|
| GET | `/admin/points` | PointsController@index |
| POST | `/admin/points/adjust/{user}` | PointsController@adjust |
| GET | `/admin/points/transactions` | PointsController@transactions |
| GET | `/admin/challenges` | ChallengeController@index |
| GET | `/admin/challenges/create` | ChallengeController@create |
| POST | `/admin/challenges` | ChallengeController@store |
| GET | `/admin/challenges/{id}/edit` | ChallengeController@edit |
| PUT | `/admin/challenges/{id}` | ChallengeController@update |
| DELETE | `/admin/challenges/{id}` | ChallengeController@destroy |
| GET | `/admin/batch-generations` | BatchGenerationController@index |
| POST | `/admin/batch-generations/generate` | BatchGenerationController@generate |
| GET | `/admin/batch-generations/{id}/progress` | BatchGenerationController@progress |
| GET | `/admin/vouchers` | VoucherController@index |
| POST | `/admin/vouchers/preview` | VoucherController@preview |
| POST | `/admin/vouchers/print` | VoucherController@print |
| GET | `/admin/maintenance` | MaintenanceController@index |
| POST | `/admin/maintenance/{action}` | MaintenanceController@execute |
| PUT | `/admin/system-settings` | SettingsController@update |
| GET | `/admin/users/banned` | UserController@banned |
| POST | `/admin/users/{id}/unban` | UserController@unban |

---

## 9. Constraints & Security

- All point operations wrapped in `DB::transaction()`
- `Cache::lock("verify_tx_{reference}")` prevents duplicate verify-transaction processing
- `points_transactions` is append-only (no `updated_at`, guarded model like `RawWebhook`)
- `verification_status = revoked` transactions cannot be re-verified
- Banned users blocked at middleware level on all purchase/verify endpoints
- Router maintenance commands respect `Cache::lock("mikrotik_connection_lock")` — no concurrent execution
- Point price only changeable by admin — no user-facing price configuration

---

## 10. Implementation Order

1. **Phase A — Database**: migrations for all new/modified tables
2. **Phase B — Points System**: PointsService, PointsBalance model, PointsTransaction model, admin panel
3. **Phase C — Instant Delivery**: verify-transaction endpoint, InstantDeliveryService, AutoRevokeJob, webhook update
4. **Phase D — Challenges**: tables, events, listeners, admin CRUD, user API
5. **Phase E — User Manager Module**: batch generation, voucher print, maintenance commands
6. **Phase F — System Settings + V2 APIs**: remaining endpoints, config

Each phase includes models, services, controllers, and admin views.
