# MikroTik V2 Upgrade — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend V1 card-generation system with Points Ledger, Instant Delivery & Auto-Revoke, Advanced User Manager, Challenges Engine, and V2 REST APIs.

**Architecture:** Six sequential phases — DB schema → Points System → Instant Delivery → Challenges → UM Module → V2 APIs. Each phase builds on the previous, all within the existing Laravel/Blade/Queue architecture.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL, RouterOS v6.49.19 API, Blade (no JS build)

---

## File Structure

### New Files

| File | Responsibility |
|------|---------------|
| `database/migrations/2026_06_21_000001_create_points_balance_table.php` | points_balance table |
| `database/migrations/2026_06_21_000002_create_points_transactions_table.php` | points_transactions table |
| `database/migrations/2026_06_21_000003_create_system_settings_table.php` | system_settings table |
| `database/migrations/2026_06_21_000004_create_challenges_tables.php` | challenges, challenge_conditions, challenge_rewards, user_challenges |
| `database/migrations/2026_06_21_000005_create_batch_generations_table.php` | batch_generations table |
| `database/migrations/2026_06_21_000006_create_maintenance_logs_table.php` | maintenance_logs table |
| `database/migrations/2026_06_21_000007_alter_transactions_table.php` | Add V2 columns to transactions |
| `database/migrations/2026_06_21_000008_alter_users_table.php` | Add ban columns to users |
| `database/migrations/2026_06_21_000009_create_voucher_themes_table.php` | voucher_themes table |
| `database/seeders/SystemSettingSeeder.php` | Seed default system settings |
| `app/Models/PointsBalance.php` | Points balance model |
| `app/Models/PointsTransaction.php` | Append-only ledger model |
| `app/Models/SystemSetting.php` | Key-value settings model |
| `app/Models/Challenge.php` | Challenge definition |
| `app/Models/ChallengeCondition.php` | Challenge condition |
| `app/Models/ChallengeReward.php` | Challenge reward |
| `app/Models/UserChallenge.php` | User challenge progress |
| `app/Models/BatchGeneration.php` | Batch generation audit |
| `app/Models/MaintenanceLog.php` | Maintenance action log |
| `app/Services/PointsService.php` | Point ledger operations |
| `app/Services/InstantDeliveryService.php` | Verify-transaction orchestration |
| `app/Services/MikroTikMaintenanceService.php` | Router backup/rebuild/clean |
| `app/Jobs/AutoRevokeJob.php` | 5-min revoke check |
| `app/Jobs/GenerateMikrotikCardBatchJob.php` | Batch card dispatcher |
| `app/Events/CardPurchased.php` | Fired after card generation |
| `app/Events/PointsCredited.php` | Fired after points credit |
| `app/Events/PointsSpent.php` | Fired after points debit |
| `app/Listeners/CheckChallenges.php` | Challenge evaluation engine |
| `app/Http/Controllers/Api/V2Controller.php` | All V2 API endpoints |
| `app/Http/Controllers/Admin/PointsController.php` | Admin points management |
| `app/Http/Controllers/Admin/ChallengeController.php` | Challenges CRUD |
| `app/Http/Controllers/Admin/BatchGenerationController.php` | Batch generation UI |
| `app/Http/Controllers/Admin/VoucherController.php` | Card print themes |
| `app/Http/Controllers/Admin/MaintenanceController.php` | Router maintenance |
| `app/Http/Controllers/Admin/SystemSettingController.php` | System settings |
| `resources/views/admin/points/index.blade.php` | Points management page |
| `resources/views/admin/points/transactions.blade.php` | Points ledger view |
| `resources/views/admin/challenges/index.blade.php` | Challenges list |
| `resources/views/admin/challenges/form.blade.php` | Challenge create/edit form |
| `resources/views/admin/batch_generations/index.blade.php` | Batch generation page |
| `resources/views/admin/vouchers/index.blade.php` | Voucher print page |
| `resources/views/admin/vouchers/themes/classic.blade.php` | Default print theme |
| `resources/views/admin/vouchers/themes/modern.blade.php` | Modern print theme |
| `resources/views/admin/maintenance/index.blade.php` | Maintenance page |
| `resources/views/admin/settings/index.blade.php` | System settings page |

### Modified Files

| File | Changes |
|------|---------|
| `app/Models/Transaction.php` | Add type, verification_status, points columns, new constants, relations |
| `app/Models/User.php` | Add is_banned, device_uuid, pointsBalance relation, ban scopes |
| `app/Services/CardGeneratorService.php` | Update generateCredentials() to accept config array |
| `app/Services/MikroTikService.php` | Add removeUser(), maintenance methods |
| `app/Jobs/GenerateMikrotikCardJob.php` | Accept optional generation config |
| `app/Http/Controllers/Api/WebhookController.php` | Handle verification_status matching |
| `routes/api.php` | Add V2 routes |
| `routes/web.php` | Add admin V2 routes |
| `config/mikrotik.php` | Add maintenance paths |

---

## Phase A: Database Migrations

### Task A1: Create points_balance table

**File:** `database/migrations/2026_06_21_000001_create_points_balance_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_balance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_balance');
    }
};
```

- [ ] **Run migration**

Run: `php artisan migrate`

### Task A2: Create points_transactions table

**File:** `database/migrations/2026_06_21_000002_create_points_transactions_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reason', 255);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
    }
};
```

- [ ] **Run migration**

### Task A3: Create system_settings table

**File:** `database/migrations/2026_06_21_000003_create_system_settings_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
```

- [ ] **Run migration**

### Task A4: Create challenges tables

**File:** `database/migrations/2026_06_21_000004_create_challenges_tables.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_completions')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->string('condition_type', 100);
            $table->string('operator', 20)->default('gte');
            $table->json('value');
            $table->unsignedTinyInteger('logic_group')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->string('reward_type', 100);
            $table->json('value');
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->json('progress_data')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->unsignedInteger('completion_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
        Schema::dropIfExists('challenge_rewards');
        Schema::dropIfExists('challenge_conditions');
        Schema::dropIfExists('challenges');
    }
};
```

- [ ] **Run migration**

### Task A5: Create batch_generations table

**File:** `database/migrations/2026_06_21_000005_create_batch_generations_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->nullOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('generated_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partially_completed'])->default('pending');
            $table->json('generation_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_generations');
    }
};
```

- [ ] **Run migration**

### Task A6: Create maintenance_logs table

**File:** `database/migrations/2026_06_21_000006_create_maintenance_logs_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->nullOnDelete();
            $table->string('action', 100);
            $table->enum('status', ['success', 'failed']);
            $table->text('raw_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
```

- [ ] **Run migration**

### Task A7: Alter transactions table (V2 columns)

**File:** `database/migrations/2026_06_21_000007_alter_transactions_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 50)->default('card_purchase')->after('status');
            $table->string('verification_status', 50)->nullable()->after('type');
            $table->decimal('points_amount', 12, 2)->nullable()->after('webhook_amount');
            $table->decimal('points_before', 12, 2)->nullable()->after('points_amount');
            $table->decimal('points_after', 12, 2)->nullable()->after('points_before');
            $table->timestamp('auto_revoke_at')->nullable()->after('card_generated_at');
            $table->timestamp('revoked_at')->nullable()->after('auto_revoke_at');
            $table->boolean('revoke_job_dispatched')->default(false)->after('revoked_at');

            $table->index('verification_status');
            $table->index(['jeeb_reference', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'verification_status', 'points_amount',
                'points_before', 'points_after', 'auto_revoke_at',
                'revoked_at', 'revoke_job_dispatched',
            ]);
        });
    }
};
```

- [ ] **Run migration**

### Task A8: Alter users table (ban columns)

**File:** `database/migrations/2026_06_21_000008_alter_users_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_uuid', 255)->nullable()->after('device_token');
            $table->boolean('is_banned')->default(false)->after('device_uuid');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->text('ban_reason')->nullable()->after('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['device_uuid', 'is_banned', 'banned_at', 'ban_reason']);
        });
    }
};
```

- [ ] **Run migration**

### Task A9: Create voucher_themes table

**File:** `database/migrations/2026_06_21_000009_create_voucher_themes_table.php`

- [ ] **Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('blade_view', 200);
            $table->string('thumbnail', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_themes');
    }
};
```

- [ ] **Run migration**

### Task A10: Create SystemSettingSeeder

**File:** `database/seeders/SystemSettingSeeder.php`

- [ ] **Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'point_price_yri'],
            ['value' => '10', 'description' => 'سعر النقطة الواحدة بالريال اليمني']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => 'false', 'description' => 'وضع الصيانة للشبكة']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'jeeb_wallet_phone'],
            ['value' => '', 'description' => 'رقم محفظة جيب الخاصة بصاحب الشبكة']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'jeeb_wallet_full_name'],
            ['value' => '', 'description' => 'الاسم الرباعي لحساب صاحب الشبكة في محفظة جيب']
        );
    }
}
```

- [ ] **Run seeder**

Run: `php artisan db:seed --class=SystemSettingSeeder`

### Task A11: Commit Phase A

```bash
git add database/
git commit -m "feat(v2): add database migrations for V2 features

- points_balance, points_transactions, system_settings tables
- challenges, challenge_conditions, challenge_rewards, user_challenges
- batch_generations, maintenance_logs, voucher_themes
- Alter transactions with verification_status, points columns
- Alter users with ban columns
- Seed default system settings"
```

---

## Phase B: Points System

### Task B1: Create PointsBalance model

**File:** `app/Models/PointsBalance.php`

- [ ] **Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsBalance extends Model
{
    protected $table = 'points_balance';

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Task B2: Create PointsTransaction model

**File:** `app/Models/PointsTransaction.php`

- [ ] **Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    protected $table = 'points_transactions';

    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Task B3: Create SystemSetting model

**File:** `app/Models/SystemSetting.php`

- [ ] **Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

### Task B4: Update User model with ban and points

**File:** `app/Models/User.php`

- [ ] **Add relations and ban scopes**

```php
// Add to existing User model:

use App\Models\PointsBalance;
use App\Models\PointsTransaction;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

// New fillable columns:
// Add 'device_uuid', 'is_banned', 'banned_at', 'ban_reason' to $fillable

// New casts:
// Add 'is_banned' => 'boolean', 'banned_at' => 'datetime' to $casts

// New relations:
public function pointsBalance(): HasOne
{
    return $this->hasOne(PointsBalance::class);
}

public function pointsTransactions(): HasMany
{
    return $this->hasMany(PointsTransaction::class);
}

// New scopes:
public function scopeNotBanned($query)
{
    return $query->where('is_banned', false);
}

public function scopeBanned($query)
{
    return $query->where('is_banned', true);
}
```

### Task B5: Update Transaction model with V2 constants and relations

**File:** `app/Models/Transaction.php`

- [ ] **Add V2 constants, fillable, casts**

```php
// Add to existing $fillable:
// 'type', 'verification_status', 'points_amount', 'points_before', 'points_after',
// 'auto_revoke_at', 'revoked_at', 'revoke_job_dispatched'

// Add to existing $casts:
// 'points_amount' => 'decimal:2',
// 'points_before' => 'decimal:2',
// 'points_after' => 'decimal:2',
// 'auto_revoke_at' => 'datetime',
// 'revoked_at' => 'datetime',
// 'revoke_job_dispatched' => 'boolean',

// Add new constants:
public const TYPE_CARD_PURCHASE = 'card_purchase';
public const TYPE_POINTS_RECHARGE = 'points_recharge';

public const VERIFICATION_PENDING = 'pending_verification';
public const VERIFICATION_VERIFIED = 'verified';
public const VERIFICATION_REVOKED = 'revoked';
```

### Task B6: Create PointsService

**File:** `app/Services/PointsService.php`

- [ ] **Write the service**

```php
<?php

namespace App\Services;

use App\Models\PointsBalance;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PointsService
{
    public function credit(User $user, float $amount, string $reason, ?string $refType = null, ?int $refId = null): PointsTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الإضافة يجب أن يكون أكبر من صفر');
        }

        return DB::transaction(function () use ($user, $amount, $reason, $refType, $refId) {
            $balance = PointsBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
            );

            $before = $balance->balance;
            $after = $before + $amount;

            $balance->update([
                'balance' => $after,
                'total_earned' => $balance->total_earned + $amount,
            ]);

            return PointsTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    public function debit(User $user, float $amount, string $reason, ?string $refType = null, ?int $refId = null): PointsTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الخصم يجب أن يكون أكبر من صفر');
        }

        return DB::transaction(function () use ($user, $amount, $reason, $refType, $refId) {
            $balance = PointsBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
            );

            if ($balance->balance < $amount) {
                throw new RuntimeException('رصيد النقاط غير كافٍ');
            }

            $before = $balance->balance;
            $after = $before - $amount;

            $balance->update([
                'balance' => $after,
                'total_spent' => $balance->total_spent + $amount,
            ]);

            return PointsTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    public function balance(User $user): float
    {
        $balance = PointsBalance::where('user_id', $user->id)->first();
        return $balance ? (float) $balance->balance : 0.0;
    }

    public function hasSufficient(User $user, float $amount): bool
    {
        return $this->balance($user) >= $amount;
    }

    public function revertTransaction(Transaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            if ($tx->type === Transaction::TYPE_POINTS_RECHARGE && $tx->points_amount > 0) {
                $this->debit(
                    $tx->user,
                    $tx->points_amount,
                    'revoked',
                    'transaction',
                    $tx->id
                );
            }
        });
    }
}
```

### Task B7: Create PointsController (Admin)

**File:** `app/Http/Controllers/Admin/PointsController.php`

- [ ] **Write the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointsBalance;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    public function __construct(private PointsService $pointsService) {}

    public function index()
    {
        $balances = PointsBalance::with('user')
            ->orderBy('balance', 'desc')
            ->paginate(30);

        $recentTransactions = PointsTransaction::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.points.index', compact('balances', 'recentTransactions'));
    }

    public function transactions(Request $request)
    {
        $query = PointsTransaction::with('user')->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(30)->withQueryString();
        return view('admin.points.transactions', compact('transactions'));
    }

    public function adjust(Request $request, User $user)
    {
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($user, $data) {
                if ($data['type'] === 'credit') {
                    $this->pointsService->credit($user, $data['amount'], $data['reason'], 'admin');
                } else {
                    $this->pointsService->debit($user, $data['amount'], $data['reason'], 'admin');
                }
            });

            return back()->with('success', 'تم تعديل الرصيد بنجاح.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

### Task B8: Create Points admin view

**File:** `resources/views/admin/points/index.blade.php`

- [ ] **Write the view**

```blade
@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>إدارة النقاط</h1>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">أرصدة المستخدمين</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>الهاتف</th>
                                <th>الرصيد</th>
                                <th>الإجمالي المكتسب</th>
                                <th>الإجمالي المنصرف</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balances as $balance)
                            <tr>
                                <td>{{ $balance->user->full_name ?? '—' }}</td>
                                <td>{{ $balance->user->phone ?? '—' }}</td>
                                <td>{{ number_format($balance->balance, 2) }}</td>
                                <td>{{ number_format($balance->total_earned, 2) }}</td>
                                <td>{{ number_format($balance->total_spent, 2) }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $balance->id }}">
                                        تعديل
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $balances->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">آخر الحركات</div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        @foreach($recentTransactions as $tx)
                        <li class="mb-2">
                            <small class="text-muted">{{ $tx->created_at->diffForHumans() }}</small><br>
                            <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                {{ $tx->type === 'credit' ? 'إيداع' : 'خصم' }}
                            </span>
                            {{ number_format($tx->amount, 2) }} — {{ $tx->reason }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Task B9: Create Points transactions view

**File:** `resources/views/admin/points/transactions.blade.php`

- [ ] **Write the view**

```blade
@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>حركات النقاط</h1>

    <form method="GET" class="row mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="بحث باسم المستخدم أو الهاتف" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">الكل</option>
                <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>إيداع</option>
                <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>خصم</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">بحث</button>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>المستخدم</th>
                <th>النوع</th>
                <th>المبلغ</th>
                <th>الرصيد قبل</th>
                <th>الرصيد بعد</th>
                <th>السبب</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ $tx->user->full_name ?? '—' }}<br><small>{{ $tx->user->phone ?? '' }}</small></td>
                <td><span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">{{ $tx->type === 'credit' ? 'إيداع' : 'خصم' }}</span></td>
                <td>{{ number_format($tx->amount, 2) }}</td>
                <td>{{ number_format($tx->balance_before, 2) }}</td>
                <td>{{ number_format($tx->balance_after, 2) }}</td>
                <td>{{ $tx->reason }}</td>
                <td>{{ $tx->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $transactions->links() }}
</div>
@endsection
```

### Task B10: Commit Phase B

```bash
git add app/Models/PointsBalance.php app/Models/PointsTransaction.php app/Models/SystemSetting.php
git add app/Models/User.php app/Models/Transaction.php
git add app/Services/PointsService.php
git add app/Http/Controllers/Admin/PointsController.php
git add resources/views/admin/points/
git commit -m "feat(v2): implement Points Ledger System

- PointsBalance, PointsTransaction, SystemSetting models
- PointsService with credit/debit/revert in DB transactions
- Admin points management controller and views
- User model updated with ban columns and relations
- Transaction model extended with V2 constants"
```

---

## Phase C: Instant Delivery & Auto-Revoke

### Task C1: Update CardGeneratorService with configurable generation

**File:** `app/Services/CardGeneratorService.php`

- [ ] **Update generateCredentials to accept config array**

Replace the existing `generateCredentials(int $length = 10)` method:

```php
public function generateCredentials(array $config = []): array
{
    $mode = $config['credential_mode'] ?? 'match';
    $usernameLength = max(6, min(32, $config['username_length'] ?? 10));
    $passwordLength = max(6, min(32, $config['password_length'] ?? 10));
    $prefix = $config['username_prefix'] ?? '';
    $charset = $config['charset'] ?? 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $exclude = $config['exclude_chars'] ?? '0O1IL';

    // Apply exclusions
    $charset = preg_replace('/[' . preg_quote($exclude, '/') . ']/', '', $charset);
    if (empty($charset)) $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    $username = $this->randomString($usernameLength, $charset);
    $password = $mode === 'match'
        ? $username
        : $this->randomString($passwordLength, $charset);

    return [
        'username' => $prefix . $username,
        'password' => $password,
    ];
}

private function randomString(int $length, string $charset): string
{
    $bytes = random_bytes($length);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $charset[ord($bytes[$i]) % strlen($charset)];
    }
    return $result;
}
```

Also update the `generate()` method to use the new format and accept optional config.

### Task C2: Update GenerateMikrotikCardJob to accept config

**File:** `app/Jobs/GenerateMikrotikCardJob.php`

- [ ] **Add $generationConfig property and update handle()**

```php
public function __construct(
    public int $transactionId,
    public ?array $generationConfig = null
) {}

public function handle(CardGeneratorService $generator): void
{
    // ... existing find + status check ...

    if ($this->generationConfig) {
        $generator->generate($transaction, $this->generationConfig);
    } else {
        $generator->generate($transaction);
    }
}
```

### Task C3: Create InstantDeliveryService

**File:** `app/Services/InstantDeliveryService.php`

- [ ] **Write the service**

```php
<?php

namespace App\Services;

use App\Jobs\AutoRevokeJob;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\User;
use App\Events\CardPurchased;
use App\Events\PointsCredited;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstantDeliveryService
{
    public function __construct(
        private PointsService $pointsService,
        private CardGeneratorService $cardGenerator,
        private MikroTikService $mikroTik
    ) {}

    public function process(array $data): array
    {
        $reference = $data['reference'];
        $lock = Cache::lock("verify_tx_{$reference}", 30);

        if (!$lock->get()) {
            throw new RuntimeException('هذه العملية قيد المعالجة حالياً.');
        }

        try {
            // Check for duplicate
            $existing = Transaction::where('jeeb_reference', $reference)
                ->where('type', $data['profile_id'] ? Transaction::TYPE_CARD_PURCHASE : Transaction::TYPE_POINTS_RECHARGE)
                ->first();

            if ($existing) {
                if ($existing->verification_status === Transaction::VERIFICATION_PENDING) {
                    return $this->buildResponse($existing);
                }
                throw new RuntimeException('رقم العملية مستخدم مسبقاً.');
            }

            $user = User::notBanned()->findOrFail($data['user_id']);

            if (!empty($data['profile_id'])) {
                return $this->processCardPurchase($user, $data);
            }

            return $this->processPointsRecharge($user, $data);

        } finally {
            $lock->release();
        }
    }

    private function processCardPurchase(User $user, array $data): array
    {
        $profile = Profile::active()->findOrFail($data['profile_id']);

        $tx = DB::transaction(function () use ($user, $profile, $data) {
            $tx = Transaction::create([
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'jeeb_reference' => $data['reference'],
                'webhook_amount' => $data['amount'],
                'webhook_phone' => $data['phone'] ?? null,
                'webhook_full_name' => $data['full_name'] ?? null,
                'type' => Transaction::TYPE_CARD_PURCHASE,
                'status' => Transaction::STATUS_PROCESSING,
                'verification_status' => Transaction::VERIFICATION_PENDING,
                'auto_revoke_at' => now()->addMinutes(5),
                'revoke_job_dispatched' => true,
            ]);

            $creds = $this->cardGenerator->generateCredentials([]);

            $this->mikroTik->connect();
            $this->mikroTik->createUserManagerUser(
                $creds['username'],
                $creds['password'],
                $profile->mikrotik_profile_name
            );

            $tx->update([
                'mikrotik_username' => $creds['username'],
                'mikrotik_password' => $creds['password'],
                'card_generated_at' => now(),
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            AutoRevokeJob::dispatch($tx->id)->delay(now()->addMinutes(5));

            event(new CardPurchased($user, $tx));

            return $tx;
        });

        return $this->buildResponse($tx);
    }

    private function processPointsRecharge(User $user, array $data): array
    {
        $pointPrice = (float) (\App\Models\SystemSetting::getValue('point_price_yri', '10'));
        $points = (int) ($data['amount'] / $pointPrice);

        if ($points <= 0) {
            throw new RuntimeException('المبلغ غير كافٍ لشحن النقاط.');
        }

        $tx = DB::transaction(function () use ($user, $points, $data, $pointPrice) {
            $balanceBefore = $this->pointsService->balance($user);

            $tx = Transaction::create([
                'user_id' => $user->id,
                'jeeb_reference' => $data['reference'],
                'webhook_amount' => $data['amount'],
                'webhook_phone' => $data['phone'] ?? null,
                'webhook_full_name' => $data['full_name'] ?? null,
                'type' => Transaction::TYPE_POINTS_RECHARGE,
                'status' => Transaction::STATUS_COMPLETED,
                'verification_status' => Transaction::VERIFICATION_PENDING,
                'points_amount' => $points,
                'points_before' => $balanceBefore,
                'points_after' => $balanceBefore + $points,
                'auto_revoke_at' => now()->addMinutes(5),
                'revoke_job_dispatched' => true,
            ]);

            $this->pointsService->credit($user, $points, 'points_recharge', 'verify', $tx->id);

            AutoRevokeJob::dispatch($tx->id)->delay(now()->addMinutes(5));

            event(new PointsCredited($user, $tx));

            return $tx;
        });

        return $this->buildResponse($tx);
    }

    private function buildResponse(Transaction $tx): array
    {
        $response = [
            'success' => true,
            'type' => $tx->type,
            'verification_status' => $tx->verification_status,
        ];

        if ($tx->type === Transaction::TYPE_CARD_PURCHASE) {
            $response['card'] = [
                'username' => $tx->mikrotik_username,
                'password' => $tx->mikrotik_password,
                'profile' => $tx->profile?->name,
            ];
        } else {
            $response['points'] = [
                'credited' => (float) $tx->points_amount,
                'balance' => (float) ($tx->points_after ?? 0),
                'amount_paid' => (float) $tx->webhook_amount,
                'point_price' => (float) \App\Models\SystemSetting::getValue('point_price_yri', '10'),
            ];
        }

        $response['message'] = $tx->type === Transaction::TYPE_CARD_PURCHASE
            ? 'تم توليد الكرت. سيتم تأكيد الدفع خلال 5 دقائق.'
            : 'تم إضافة النقاط. سيتم تأكيد الدفع خلال 5 دقائق.';

        return $response;
    }
}
```

### Task C4: Create AutoRevokeJob

**File:** `app/Jobs/AutoRevokeJob.php`

- [ ] **Write the job**

```php
<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use App\Services\MikroTikService;
use App\Services\PointsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRevokeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'cards';
    public int $tries = 3;

    public function __construct(public int $transactionId) {}

    public function handle(MikroTikService $mikroTik, PointsService $pointsService): void
    {
        $tx = Transaction::with('user')->find($this->transactionId);

        if (!$tx || $tx->verification_status !== Transaction::VERIFICATION_PENDING) {
            return;
        }

        DB::transaction(function () use ($tx, $mikroTik, $pointsService) {
            if ($tx->type === Transaction::TYPE_CARD_PURCHASE && $tx->mikrotik_username) {
                try {
                    $mikroTik->connect();
                    $removeQuery = (new \RouterOS\Query('/tool/user-manager/user/remove'))
                        ->equal('numbers', $tx->mikrotik_username);
                    $mikroTik->getClient()->query($removeQuery)->read();
                } catch (\Throwable $e) {
                    Log::error('AutoRevoke: failed to remove user from MikroTik', [
                        'username' => $tx->mikrotik_username,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($tx->type === Transaction::TYPE_POINTS_RECHARGE && $tx->points_amount > 0) {
                try {
                    $pointsService->revertTransaction($tx);
                } catch (\Throwable $e) {
                    Log::error('AutoRevoke: failed to revert points', [
                        'transaction_id' => $tx->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $tx->update([
                'verification_status' => Transaction::VERIFICATION_REVOKED,
                'revoked_at' => now(),
            ]);

            if ($tx->user) {
                $tx->user->update([
                    'is_banned' => true,
                    'banned_at' => now(),
                    'ban_reason' => 'احتيال - تأكيد وهمي - لم يصل Webhook خلال 5 دقائق',
                ]);
            }
        });
    }
}
```

### Task C5: Add getClient() method to MikroTikService

**File:** `app/Services/MikroTikService.php`

- [ ] **Add public getter for client**

```php
public function getClient(): ?\RouterOS\Client
{
    return $this->client;
}
```

### Task C6: Update WebhookController for verification matching

**File:** `app/Http/Controllers/Api/WebhookController.php`

- [ ] **After parsing, check for pending verification transactions**

In the `receive()` method, after successful parsing and before the existing user matching logic, add:

```php
// Check if this reference matches a pending verification transaction
if ($parsed['reference']) {
    $pendingTx = Transaction::where('jeeb_reference', $parsed['reference'])
        ->where('verification_status', Transaction::VERIFICATION_PENDING)
        ->first();

    if ($pendingTx) {
        $pendingTx->update([
            'verification_status' => Transaction::VERIFICATION_VERIFIED,
            'raw_webhook_id' => $rawWebhook->id,
        ]);

        Log::info('Webhook verified instant-delivery transaction', [
            'transaction_id' => $pendingTx->id,
            'reference' => $parsed['reference'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الدفع واستقرار الكرت.',
            'verified' => true,
            'transaction_id' => $pendingTx->id,
        ]);
    }
}
```

### Task C7: Create V2Controller with verify-transaction endpoint

**File:** `app/Http/Controllers/Api/V2Controller.php`

- [ ] **Write the controller**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstantDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class V2Controller extends Controller
{
    public function __construct(private InstantDeliveryService $instantDelivery) {}

    public function verifyTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reference' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_id' => 'nullable|exists:profiles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->instantDelivery->process($validator->validated());
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function networkStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'router_connected' => \App\Models\RouterSetting::current()->is_connected,
            'queue_size' => \Illuminate\Support\Facades\DB::table('jobs')->count(),
            'maintenance_mode' => \App\Models\SystemSetting::getValue('maintenance_mode') === 'true',
        ]);
    }

    public function appConfig(): JsonResponse
    {
        $profiles = \App\Models\Profile::active()
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_hours', 'speed_limit']);

        return response()->json([
            'success' => true,
            'maintenance_mode' => \App\Models\SystemSetting::getValue('maintenance_mode') === 'true',
            'point_price' => (float) \App\Models\SystemSetting::getValue('point_price_yri', '10'),
            'jeeb_wallet_phone' => \App\Models\SystemSetting::getValue('jeeb_wallet_phone', ''),
            'jeeb_wallet_full_name' => \App\Models\SystemSetting::getValue('jeeb_wallet_full_name', ''),
            'profiles' => $profiles,
            'currency' => 'YRI',
        ]);
    }
}
```

### Task C8: Update routes/api.php

- [ ] **Add V2 routes**

```php
// At the end of routes/api.php:

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::post('/verify-transaction', [\App\Http\Controllers\Api\V2Controller::class, 'verifyTransaction'])->name('verify');
    Route::get('/network-status', [\App\Http\Controllers\Api\V2Controller::class, 'networkStatus'])->name('status');
    Route::get('/app-config', [\App\Http\Controllers\Api\V2Controller::class, 'appConfig'])->name('config');
});
```

### Task C9: Commit Phase C

```bash
git add app/Services/CardGeneratorService.php
git add app/Services/InstantDeliveryService.php
git add app/Services/MikroTikService.php
git add app/Jobs/AutoRevokeJob.php
git add app/Jobs/GenerateMikrotikCardJob.php
git add app/Http/Controllers/Api/V2Controller.php
git add app/Http/Controllers/Api/WebhookController.php
git add routes/api.php
git commit -m "feat(v2): implement Instant Delivery & Auto-Revoke

- Configurable CardGeneratorService with credential modes
- InstantDeliveryService for verify-transaction orchestration
- AutoRevokeJob with 5-min delay, MikroTik remove + ban
- WebhookController updated to verify pending transactions
- V2Controller with verify-transaction endpoint
- V2 API routes"
```

---

## Phase D: Challenges Engine

### Task D1: Create Challenge models

**File:** `app/Models/Challenge.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $table = 'challenges';

    protected $fillable = [
        'name', 'description', 'is_active', 'starts_at', 'ends_at', 'max_completions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_completions' => 'integer',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(ChallengeCondition::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ChallengeReward::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
```

**File:** `app/Models/ChallengeCondition.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeCondition extends Model
{
    protected $table = 'challenge_conditions';

    protected $fillable = [
        'challenge_id', 'condition_type', 'operator', 'value', 'logic_group',
    ];

    protected $casts = [
        'value' => 'array',
        'logic_group' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
```

**File:** `app/Models/ChallengeReward.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeReward extends Model
{
    protected $table = 'challenge_rewards';

    protected $fillable = [
        'challenge_id', 'reward_type', 'value', 'priority',
    ];

    protected $casts = [
        'value' => 'array',
        'priority' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
```

**File:** `app/Models/UserChallenge.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChallenge extends Model
{
    protected $table = 'user_challenges';

    protected $fillable = [
        'user_id', 'challenge_id', 'progress_data', 'started_at',
        'completed_at', 'reward_claimed_at', 'completion_count',
    ];

    protected $casts = [
        'progress_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
        'completion_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
```

### Task D2: Create events

**File:** `app/Events/CardPurchased.php`

```php
<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CardPurchased
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Transaction $transaction
    ) {}
}
```

**File:** `app/Events/PointsCredited.php`

```php
<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PointsCredited
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Transaction $transaction
    ) {}
}
```

**File:** `app/Events/PointsSpent.php`

```php
<?php

namespace App\Events;

use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PointsSpent
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public PointsTransaction $pointsTransaction
    ) {}
}
```

### Task D3: Create CheckChallenges listener

**File:** `app/Listeners/CheckChallenges.php`

```php
<?php

namespace App\Listeners;

use App\Events\CardPurchased;
use App\Events\PointsCredited;
use App\Events\PointsSpent;
use App\Models\Challenge;
use App\Models\UserChallenge;
use App\Services\PointsService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckChallenges
{
    public function __construct(private PointsService $pointsService) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CardPurchased::class, [$this, 'handleCardPurchased']);
        $events->listen(PointsCredited::class, [$this, 'handlePointsEvent']);
        $events->listen(PointsSpent::class, [$this, 'handlePointsEvent']);
    }

    public function handleCardPurchased(CardPurchased $event): void
    {
        $this->evaluate($event->user, 'purchase_count', $event);
        $this->evaluate($event->user, 'profile_purchase', $event);
    }

    public function handlePointsEvent(PointsCredited|PointsSpent $event): void
    {
        $this->evaluate($event->user, 'points_spent_total', $event);
        $this->evaluate($event->user, 'points_spent_single', $event);
    }

    private function evaluate($user, string $eventType, $event): void
    {
        $challenges = Challenge::active()
            ->whereHas('conditions', fn($q) => $q->where('condition_type', $eventType))
            ->get();

        foreach ($challenges as $challenge) {
            DB::transaction(function () use ($challenge, $user, $event, $eventType) {
                $progress = UserChallenge::firstOrCreate([
                    'user_id' => $user->id,
                    'challenge_id' => $challenge->id,
                ], ['progress_data' => []]);

                if ($progress->completed_at && !$progress->reward_claimed_at) {
                    return; // Already completed, waiting for claim
                }

                if ($challenge->max_completions > 0
                    && $progress->completion_count >= $challenge->max_completions) {
                    return; // Max completions reached
                }

                $data = $progress->progress_data ?? [];
                $data[$eventType] = ($data[$eventType] ?? 0) + 1;
                $progress->progress_data = $data;

                $allMet = $challenge->conditions->every(function ($condition) use ($data) {
                    $current = $data[$condition->condition_type] ?? 0;
                    $target = $condition->value['min'] ?? $condition->value['count'] ?? 1;
                    return $current >= $target;
                });

                if ($allMet) {
                    $progress->completed_at = now();
                    $progress->completion_count += 1;
                    $this->awardRewards($challenge, $user);
                }

                $progress->save();
            });
        }
    }

    private function awardRewards(Challenge $challenge, $user): void
    {
        foreach ($challenge->rewards as $reward) {
            try {
                match ($reward->reward_type) {
                    'points' => $this->pointsService->credit(
                        $user,
                        $reward->value['points'] ?? 0,
                        'challenge_reward',
                        'challenge',
                        $challenge->id
                    ),
                    default => Log::info('Unknown challenge reward type', [
                        'type' => $reward->reward_type,
                    ]),
                };
            } catch (\Throwable $e) {
                Log::error('Challenge reward failed', [
                    'challenge_id' => $challenge->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

### Task D4: Register event and listener in AppServiceProvider

**File:** `app/Providers/AppServiceProvider.php`

- [ ] **Add to boot() method**

```php
use App\Events\CardPurchased;
use App\Events\PointsCredited;
use App\Events\PointsSpent;
use App\Listeners\CheckChallenges;

public function boot(): void
{
    \Event::subscribe(CheckChallenges::class);
}
```

### Task D5: Create ChallengeController (Admin)

**File:** `app/Http/Controllers/Admin/ChallengeController.php`

- [ ] **Write CRUD controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Challenge::withCount(['conditions', 'rewards'])->latest()->paginate(20);
        return view('admin.challenges.index', compact('challenges'));
    }

    public function create()
    {
        return view('admin.challenges.form', ['challenge' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'max_completions' => 'integer|min:0',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|json',
            'conditions.*.logic_group' => 'integer',
            'rewards' => 'required|array|min:1',
            'rewards.*.reward_type' => 'required|string',
            'rewards.*.value' => 'required|json',
            'rewards.*.priority' => 'integer',
        ]);

        $challenge = Challenge::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'max_completions' => $data['max_completions'] ?? 0,
        ]);

        foreach ($data['conditions'] as $cond) {
            $challenge->conditions()->create([
                'condition_type' => $cond['condition_type'],
                'operator' => $cond['operator'],
                'value' => json_decode($cond['value'], true),
                'logic_group' => $cond['logic_group'] ?? 0,
            ]);
        }

        foreach ($data['rewards'] as $reward) {
            $challenge->rewards()->create([
                'reward_type' => $reward['reward_type'],
                'value' => json_decode($reward['value'], true),
                'priority' => $reward['priority'] ?? 0,
            ]);
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'تم إنشاء التحدي بنجاح.');
    }

    public function edit(Challenge $challenge)
    {
        $challenge->load(['conditions', 'rewards']);
        return view('admin.challenges.form', compact('challenge'));
    }

    public function update(Request $request, Challenge $challenge)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'max_completions' => 'integer|min:0',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|json',
            'conditions.*.logic_group' => 'integer',
            'rewards' => 'required|array|min:1',
            'rewards.*.reward_type' => 'required|string',
            'rewards.*.value' => 'required|json',
            'rewards.*.priority' => 'integer',
        ]);

        $challenge->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'max_completions' => $data['max_completions'] ?? 0,
        ]);

        $challenge->conditions()->delete();
        $challenge->rewards()->delete();

        foreach ($data['conditions'] as $cond) {
            $challenge->conditions()->create([
                'condition_type' => $cond['condition_type'],
                'operator' => $cond['operator'],
                'value' => json_decode($cond['value'], true),
                'logic_group' => $cond['logic_group'] ?? 0,
            ]);
        }

        foreach ($data['rewards'] as $reward) {
            $challenge->rewards()->create([
                'reward_type' => $reward['reward_type'],
                'value' => json_decode($reward['value'], true),
                'priority' => $reward['priority'] ?? 0,
            ]);
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'تم تحديث التحدي بنجاح.');
    }

    public function destroy(Challenge $challenge)
    {
        $challenge->delete();
        return back()->with('success', 'تم حذف التحدي.');
    }
}
```

### Task D6: Challenge views and API

- [ ] **Create admin views** at `resources/views/admin/challenges/index.blade.php` and `form.blade.php`
- [ ] **Add challenges API endpoint** in V2Controller:

```php
public function challenges(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $user = \App\Models\User::find($request->user_id);
    $active = \App\Models\Challenge::active()->with(['conditions', 'rewards'])->get();
    $userProgress = \App\Models\UserChallenge::where('user_id', $user->id)->get()->keyBy('challenge_id');

    $result = $active->map(function ($challenge) use ($userProgress) {
        $progress = $userProgress->get($challenge->id);
        return [
            'id' => $challenge->id,
            'name' => $challenge->name,
            'description' => $challenge->description,
            'progress' => $progress?->progress_data ?? [],
            'conditions' => $challenge->conditions,
            'rewards' => $challenge->rewards,
            'completed' => !is_null($progress?->completed_at),
            'claimed' => !is_null($progress?->reward_claimed_at),
        ];
    });

    return response()->json([
        'success' => true,
        'active' => $result,
    ]);
}
```

- [ ] **Add route**: `Route::get('/challenges', [V2Controller::class, 'challenges'])->name('challenges');`

### Task D7: Commit Phase D

```bash
git add app/Models/Challenge.php app/Models/ChallengeCondition.php
git add app/Models/ChallengeReward.php app/Models/UserChallenge.php
git add app/Events/ app/Listeners/
git add app/Providers/AppServiceProvider.php
git add app/Http/Controllers/Admin/ChallengeController.php
git add app/Http/Controllers/Api/V2Controller.php
git add resources/views/admin/challenges/
git commit -m "feat(v2): implement Challenges Engine with event-driven architecture

- Challenge, ChallengeCondition, ChallengeReward, UserChallenge models
- CardPurchased, PointsCredited, PointsSpent events
- CheckChallenges listener with condition evaluation and reward system
- Admin CRUD for challenges with flexible conditions/rewards
- User API endpoint for active challenges"
```

---

## Phase E: User Manager Module

### Task E1: Add removeUser & maintenance methods to MikroTikService

**File:** `app/Services/MikroTikService.php`

- [ ] **Add methods**

```php
use RouterOS\Query;

public function removeUser(string $username): bool
{
    if ($this->client === null) $this->connect();

    $query = (new Query('/tool/user-manager/user/remove'))
        ->equal('numbers', $username);

    $this->client->query($query)->read();
    return true;
}

public function executeMaintenance(string $action): array
{
    if ($this->client === null) $this->connect();

    $path = match ($action) {
        'backup_db' => '/tool/user-manager/database/backup',
        'clear_logs' => '/tool/user-manager/database/clear-logs',
        'rebuild_db' => '/tool/user-manager/database/rebuild',
        default => throw new \InvalidArgumentException("Unknown action: {$action}"),
    };

    $response = $this->client->query($path)->read();
    return ['output' => json_encode($response)];
}
```

### Task E2: Create MikroTikMaintenanceService

**File:** `app/Services/MikroTikMaintenanceService.php`

```php
<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\Log;

class MikroTikMaintenanceService
{
    public function __construct(private MikroTikService $mikroTik) {}

    public function execute(string $action, Admin $admin): MaintenanceLog
    {
        try {
            $result = $this->mikroTik->executeMaintenance($action);

            return MaintenanceLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'status' => 'success',
                'raw_output' => $result['output'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Maintenance action failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return MaintenanceLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'status' => 'failed',
                'raw_output' => $e->getMessage(),
            ]);
        }
    }
}
```

### Task E3: Create GenerateMikrotikCardBatchJob

**File:** `app/Jobs/GenerateMikrotikCardBatchJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\BatchGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMikrotikCardBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'cards';
    public int $timeout = 600;

    public function __construct(public int $batchId) {}

    public function handle(): void
    {
        $batch = BatchGeneration::find($this->batchId);
        if (!$batch || $batch->status === 'completed') return;

        $batch->update(['status' => 'processing']);

        $successCount = 0;
        for ($i = 0; $i < $batch->quantity; $i++) {
            $transaction = \App\Models\Transaction::create([
                'profile_id' => $batch->profile_id,
                'type' => \App\Models\Transaction::TYPE_CARD_PURCHASE,
                'status' => \App\Models\Transaction::STATUS_PENDING_MATCH,
            ]);

            GenerateMikrotikCardJob::dispatch(
                $transaction->id,
                $batch->generation_config
            )->onQueue('cards');

            $successCount++;
        }

        $batch->update([
            'generated_count' => $successCount,
            'status' => $successCount === $batch->quantity ? 'completed' : 'partially_completed',
        ]);
    }
}
```

### Task E4: Create BatchGeneration, MaintenanceLog, VoucherTheme models

- [ ] **Create `app/Models/BatchGeneration.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchGeneration extends Model
{
    protected $table = 'batch_generations';

    protected $fillable = ['admin_id', 'profile_id', 'quantity', 'generated_count', 'status', 'generation_config'];

    protected $casts = [
        'quantity' => 'integer',
        'generated_count' => 'integer',
        'generation_config' => 'array',
    ];

    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
```

- [ ] **Create `app/Models/MaintenanceLog.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $table = 'maintenance_logs';

    protected $fillable = ['admin_id', 'action', 'status', 'raw_output'];

    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
}
```

- [ ] **Create `app/Models/VoucherTheme.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTheme extends Model
{
    protected $table = 'voucher_themes';

    protected $fillable = ['name', 'blade_view', 'thumbnail', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];
}
```

### Task E5: Create admin controllers

- [ ] **Create `app/Http/Controllers/Admin/BatchGenerationController.php`**
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMikrotikCardBatchJob;
use App\Models\BatchGeneration;
use App\Models\Profile;
use Illuminate\Http\Request;

class BatchGenerationController extends Controller
{
    public function index()
    {
        $batches = BatchGeneration::with(['admin', 'profile'])->latest()->paginate(20);
        $profiles = Profile::active()->get();
        return view('admin.batch_generations.index', compact('batches', 'profiles'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'credential_mode' => 'nullable|in:match,separate',
            'username_length' => 'nullable|integer|min:6|max:32',
            'password_length' => 'nullable|integer|min:6|max:32',
            'username_prefix' => 'nullable|string|max:10',
        ]);

        $batch = BatchGeneration::create([
            'admin_id' => auth('admin')->id(),
            'profile_id' => $data['profile_id'],
            'quantity' => $data['quantity'],
            'generation_config' => [
                'credential_mode' => $data['credential_mode'] ?? 'match',
                'username_length' => (int) ($data['username_length'] ?? 10),
                'password_length' => (int) ($data['password_length'] ?? 10),
                'username_prefix' => $data['username_prefix'] ?? '',
            ],
        ]);

        GenerateMikrotikCardBatchJob::dispatch($batch->id)->onQueue('cards');

        return back()->with('success', "تم إرسال مهمة توليد {$data['quantity']} بطاقة إلى قائمة الانتظار.");
    }

    public function progress(int $id)
    {
        $batch = BatchGeneration::findOrFail($id);
        return response()->json(['id' => $batch->id, 'status' => $batch->status, 'generated' => $batch->generated_count, 'total' => $batch->quantity]);
    }
}
```

- [ ] **Create `app/Http/Controllers/Admin/MaintenanceController.php`**
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Services\MikroTikMaintenanceService;

class MaintenanceController extends Controller
{
    public function __construct(private MikroTikMaintenanceService $maintenance) {}

    public function index()
    {
        $logs = MaintenanceLog::with('admin')->latest()->paginate(20);
        return view('admin.maintenance.index', compact('logs'));
    }

    public function execute(string $action, MikroTikMaintenanceService $maintenance)
    {
        $valid = ['backup_db', 'clear_logs', 'rebuild_db'];
        if (!in_array($action, $valid)) {
            return back()->withErrors(['error' => 'إجراء غير معروف.']);
        }

        $log = $maintenance->execute($action, auth('admin')->user());
        $msg = $log->status === 'success' ? 'تم تنفيذ الإجراء بنجاح.' : 'فشل تنفيذ الإجراء.';
        return back()->with($log->status === 'success' ? 'success' : 'error', $msg);
    }
}
```

- [ ] **Create `app/Http/Controllers/Admin/VoucherController.php`**
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherTheme;
use App\Models\Transaction;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $themes = VoucherTheme::all();
        return view('admin.vouchers.index', compact('themes'));
    }

    public function preview(Request $request)
    {
        $theme = VoucherTheme::findOrFail($request->theme_id);
        $cards = Transaction::whereIn('id', $request->transaction_ids ?? [])
            ->whereNotNull('mikrotik_username')
            ->get()->map(fn($t) => [
                'username' => $t->mikrotik_username,
                'password' => $t->mikrotik_password,
                'profile' => $t->profile?->name,
            ]);

        return view($theme->blade_view, compact('cards'));
    }

    public function print(Request $request)
    {
        // Same logic as preview but with print-specific layout
    }
}
```

### Task E6: Create voucher print themes

- [ ] **Classic theme** at `resources/views/admin/vouchers/themes/classic.blade.php`
- [ ] **Modern theme** at `resources/views/admin/vouchers/themes/modern.blade.php`

Each renders a printable grid of cards with username, password, profile name.

### Task E7: Add admin routes

**File:** `routes/web.php`

- [ ] **Add inside the admin group**

```php
Route::prefix('points')->name('points.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PointsController::class, 'index'])->name('index');
    Route::get('/transactions', [\App\Http\Controllers\Admin\PointsController::class, 'transactions'])->name('transactions');
    Route::post('/adjust/{user}', [\App\Http\Controllers\Admin\PointsController::class, 'adjust'])->name('adjust');
});

Route::resource('challenges', \App\Http\Controllers\Admin\ChallengeController::class);

Route::prefix('batch-generations')->name('batch.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BatchGenerationController::class, 'index'])->name('index');
    Route::post('/generate', [\App\Http\Controllers\Admin\BatchGenerationController::class, 'generate'])->name('generate');
    Route::get('/{id}/progress', [\App\Http\Controllers\Admin\BatchGenerationController::class, 'progress'])->name('progress');
});

Route::prefix('vouchers')->name('vouchers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\VoucherController::class, 'index'])->name('index');
    Route::post('/preview', [\App\Http\Controllers\Admin\VoucherController::class, 'preview'])->name('preview');
    Route::post('/print', [\App\Http\Controllers\Admin\VoucherController::class, 'print'])->name('print');
});

Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('index');
    Route::post('/{action}', [\App\Http\Controllers\Admin\MaintenanceController::class, 'execute'])->name('execute');
});

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\SystemSettingController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\SystemSettingController::class, 'update'])->name('update');
});
```

### Task E8: Commit Phase E

```bash
git add app/Services/MikroTikService.php app/Services/MikroTikMaintenanceService.php
git add app/Jobs/GenerateMikrotikCardBatchJob.php
git add app/Models/BatchGeneration.php app/Models/MaintenanceLog.php
git add app/Http/Controllers/Admin/BatchGenerationController.php
git add app/Http/Controllers/Admin/MaintenanceController.php
git add app/Http/Controllers/Admin/VoucherController.php
git add app/Http/Controllers/Admin/SystemSettingController.php
git add app/Http/Controllers/Admin/ChallengeController.php
git add resources/views/admin/vouchers/ resources/views/admin/batch_generations/
git add resources/views/admin/maintenance/ resources/views/admin/settings/
git add routes/web.php
git commit -m "feat(v2): implement User Manager Module - batch gen, vouchers, maintenance"
```

---

## Phase F: System Settings & Final Integration

### Task F1: Create SystemSettingController

**File:** `app/Http/Controllers/Admin/SystemSettingController.php`

- [ ] **Write the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::orderBy('key')->get();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $keys = SystemSetting::pluck('key')->toArray();
        $rules = [];
        foreach ($keys as $key) {
            $rules["settings.$key"] = 'nullable|string';
        }

        $data = $request->validate($rules);

        foreach ($data['settings'] ?? [] as $key => $value) {
            SystemSetting::setValue($key, $value ?? '');
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
```

### Task F2: Add middleware to block banned users

**File:** `app/Http/Middleware/CheckBanned.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('user_id')) {
            $user = \App\Models\User::find($request->user_id);
            if ($user && $user->is_banned) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك محظور بسبب نشاط غير نظامي. يرجى التواصل مع الدعم.',
                ], 403);
            }
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`: `$middleware->alias(['check.banned' => \App\Http\Middleware\CheckBanned::class]);`

Apply to V2 API routes by updating `routes/api.php`:
```php
Route::prefix('v2')->name('api.v2.')->middleware(['check.banned'])->group(function () {
    // ... existing v2 routes ...
});
```

### Task F3: Seed voucher themes

**File:** Update `SystemSettingSeeder` or create `VoucherThemeSeeder`

```php
\App\Models\VoucherTheme::firstOrCreate(
    ['blade_view' => 'admin.vouchers.themes.classic'],
    ['name' => 'كلاسيك', 'is_default' => true]
);
\App\Models\VoucherTheme::firstOrCreate(
    ['blade_view' => 'admin.vouchers.themes.modern'],
    ['name' => 'مودرن', 'is_default' => false]
);
```

### Task F4: Create settings admin view

**File:** `resources/views/admin/settings/index.blade.php`

- [ ] **Write the view** — Lists all settings in an editable form with key as label and text input for value.

### Task F5: Final review and migration

- [ ] **Run all pending migrations** (`php artisan migrate`)
- [ ] **Run all seeders** (`php artisan db:seed --class=SystemSettingSeeder`)
- [ ] **Confirm all routes work** (`php artisan route:list`)
- [ ] **Run final commit**

```bash
git add app/Http/Middleware/CheckBanned.php
git add bootstrap/app.php
git add database/seeders/
git commit -m "feat(v2): final integration - ban middleware, settings, seeders"
```

---

## Voucher Theme Templates

### Classic theme
**File:** `resources/views/admin/vouchers/themes/classic.blade.php`

```blade
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>قسائم الإنترنت</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'DejaVu Sans', sans-serif; }
        .card { border: 2px solid #333; padding: 10px; margin: 5px; width: 200px; display: inline-block; text-align: center; }
        .card h3 { margin: 0 0 5px; font-size: 14px; }
        .card .cred { font-size: 18px; font-weight: bold; letter-spacing: 2px; }
        .card .profile { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    @foreach($cards as $card)
    <div class="card">
        <h3>{{ $network_name ?? 'شبكتي' }}</h3>
        <div>اسم المستخدم</div>
        <div class="cred">{{ $card['username'] }}</div>
        <div>كلمة المرور</div>
        <div class="cred">{{ $card['password'] }}</div>
        <div class="profile">{{ $card['profile'] }}</div>
        @if(!empty($card['expires_at']))
        <div class="profile">صالح حتى: {{ $card['expires_at'] }}</div>
        @endif
    </div>
    @endforeach
</body>
</html>
```

### Modern theme
**File:** `resources/views/admin/vouchers/themes/modern.blade.php`

```blade
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>قسائم الإنترنت - مودرن</title>
    <style>
        @page { margin: 0.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #f5f5f5; }
        .card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; padding: 15px; margin: 8px; width: 220px;
            display: inline-block; text-align: center; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .card h3 { margin: 0 0 10px; font-size: 16px; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 8px; }
        .card .label { font-size: 10px; opacity: 0.8; }
        .card .cred { font-size: 20px; font-weight: bold; letter-spacing: 3px; margin: 2px 0 8px; word-break: break-all; }
        .card .profile { font-size: 11px; opacity: 0.9; margin-top: 5px; }
        .watermark { font-size: 40px; opacity: 0.05; position: absolute; }
    </style>
</head>
<body>
    @foreach($cards as $card)
    <div class="card">
        <h3>{{ $network_name ?? 'شبكتي' }}</h3>
        <div class="label">اسم المستخدم</div>
        <div class="cred">{{ $card['username'] }}</div>
        <div class="label">كلمة المرور</div>
        <div class="cred">{{ $card['password'] }}</div>
        <div class="profile">{{ $card['profile'] }}</div>
        @if(!empty($card['expires_at']))
        <div class="profile">صالح حتى: {{ $card['expires_at'] }}</div>
        @endif
    </div>
    @endforeach
</body>
</html>
```
