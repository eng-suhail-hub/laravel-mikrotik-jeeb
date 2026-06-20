# Graph Report - .  (2026-06-21)

## Corpus Check
- Corpus is ~29,387 words - fits in a single context window. You may not need a graph.

## Summary
- 293 nodes · 365 edges · 50 communities (45 shown, 5 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 9 edges (avg confidence: 0.73)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Composer Dependencies|Composer Dependencies]]
- [[_COMMUNITY_Eloquent Models|Eloquent Models]]
- [[_COMMUNITY_API Controllers|API Controllers]]
- [[_COMMUNITY_Queue Jobs|Queue Jobs]]
- [[_COMMUNITY_Core Services|Core Services]]
- [[_COMMUNITY_MikroTik Connectivity|MikroTik Connectivity]]
- [[_COMMUNITY_Router Settings|Router Settings]]
- [[_COMMUNITY_Admin Auth|Admin Auth]]
- [[_COMMUNITY_Profile Sync|Profile Sync]]
- [[_COMMUNITY_Auth Guards|Auth Guards]]
- [[_COMMUNITY_Webhook Parser|Webhook Parser]]
- [[_COMMUNITY_Composer Config|Composer Config]]
- [[_COMMUNITY_Purchase API|Purchase API]]
- [[_COMMUNITY_AdminAuth Middleware|AdminAuth Middleware]]
- [[_COMMUNITY_LocalhostOnly Middleware|LocalhostOnly Middleware]]
- [[_COMMUNITY_Jeeb Webhook|Jeeb Webhook]]
- [[_COMMUNITY_System Overview|System Overview]]
- [[_COMMUNITY_Card Generator|Card Generator]]
- [[_COMMUNITY_Service Provider|Service Provider]]
- [[_COMMUNITY_Flutter API|Flutter API]]
- [[_COMMUNITY_Router Encrypted Config|Router Encrypted Config]]
- [[_COMMUNITY_Profile Model Singleton|Profile Model Singleton]]

## God Nodes (most connected - your core abstractions)
1. `Controller` - 17 edges
2. `GenerateMikrotikCardJob` - 12 edges
3. `Transaction` - 11 edges
4. `MikroTikService` - 11 edges
5. `ProfileController` - 9 edges
6. `RouterSetting` - 9 edges
7. `TransactionController` - 8 edges
8. `Transaction` - 7 edges
9. `WebhookController` - 7 edges
10. `WebhookParser` - 7 edges

## Surprising Connections (you probably didn't know these)
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/AuthController.php → app/Http/Controllers/Controller.php
- `ProfileController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/ProfileController.php → app/Http/Controllers/Controller.php
- `RouterController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/RouterController.php → app/Http/Controllers/Controller.php
- `TransactionController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/TransactionController.php → app/Http/Controllers/Controller.php
- `PurchaseController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/PurchaseController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Full webhook pipeline** — AndroidEmulator, route_webhook_jeeb, App_Http_Middleware_LocalhostOnly, App_Models_RawWebhook, App_Services_WebhookParser, App_Models_User, App_Models_Transaction, App_Jobs_GenerateMikrotikCardJob, Laravel_Queue, App_Services_MikroTikService, MikroTikRouter [INFERRED]
- **Three-service architecture** — App_Services_WebhookParser, App_Services_MikroTikService, App_Services_CardGeneratorService [INFERRED]
- **Dual auth system** — App_Models_Admin, App_Models_User, Laravel_Auth_Admin, Laravel_Auth_Web [INFERRED]
- **Cache::Lock prevents concurrent connections** — App_Jobs_GenerateMikrotikCardJob, Laravel_Cache_Lock, App_Services_MikroTikService, MikroTikRouter [INFERRED]
- **Transaction matching via regex parser** — App_Models_Transaction, App_Models_User, App_Services_WebhookParser, config_jeeb_php [INFERRED]

## Communities (50 total, 5 thin omitted)

### Community 0 - "Composer Dependencies"
Cohesion: 0.05
Nodes (36): autoload, autoload-dev, psr-4, psr-4, description, extra, laravel, keywords (+28 more)

### Community 1 - "Eloquent Models"
Cohesion: 0.08
Nodes (14): HasMany, HasMany, HasMany, self, Authenticatable, AuthenticatableContract, BelongsTo, HasFactory (+6 more)

### Community 2 - "API Controllers"
Cohesion: 0.19
Nodes (11): DashboardController, AuthController, WebhookController, JsonResponse, Request, JsonResponse, Profile, Request (+3 more)

### Community 3 - "Queue Jobs"
Cohesion: 0.18
Nodes (10): TransactionController, Request, Transaction, CardGeneratorService, Dispatchable, InteractsWithQueue, GenerateMikrotikCardJob, Queueable (+2 more)

### Community 4 - "Core Services"
Cohesion: 0.13
Nodes (15): GenerateMikrotikCardJob, RawWebhook model (append-only), Transaction model, User model (Flutter clients), CardGeneratorService, MikroTikService, WebhookParser service, Cache::Lock (mikrotik_connection_lock) (+7 more)

### Community 5 - "MikroTik Connectivity"
Cohesion: 0.23
Nodes (3): Client, MikroTikService, Throwable

### Community 6 - "Router Settings"
Cohesion: 0.22
Nodes (4): RouterController, Request, self, RouterSetting

### Community 7 - "Admin Auth"
Cohesion: 0.24
Nodes (5): Admin, AuthController, Request, Seeder, InitialAdminSeeder

### Community 8 - "Profile Sync"
Cohesion: 0.36
Nodes (4): ProfileController, Profile, Request, MikroTikService

### Community 9 - "Auth Guards"
Cohesion: 0.25
Nodes (8): AdminAuth middleware, Admin model, InitialAdminSeeder, Admin session guard, Web guard (Flutter users), Laravel hashed cast, admin.auth middleware alias, Auth config

### Community 11 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 12 - "Purchase API"
Cohesion: 0.53
Nodes (3): PurchaseController, JsonResponse, Request

### Community 13 - "AdminAuth Middleware"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, AdminAuth

### Community 14 - "LocalhostOnly Middleware"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, LocalhostOnly

### Community 15 - "Jeeb Webhook"
Cohesion: 0.40
Nodes (5): Android Emulator (webhook sender), LocalhostOnly middleware, Jeeb Wallet, localhost.only middleware alias, POST /api/webhook/jeeb

### Community 16 - "System Overview"
Cohesion: 0.40
Nodes (5): Blade + Bootstrap 5 admin panel, CI: PHP Composer workflow, Laravel Sanctum, MikroTik Cards System, PHPUnit 11

### Community 19 - "Flutter API"
Cohesion: 0.50
Nodes (4): Flutter client app, POST /api/auth/register, GET /api/profiles, POST /api/purchase

## Knowledge Gaps
- **34 isolated node(s):** `self`, `self`, `name`, `type`, `description` (+29 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **5 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `API Controllers` to `Queue Jobs`, `Router Settings`, `Admin Auth`, `Profile Sync`, `Purchase API`?**
  _High betweenness centrality (0.111) - this node is a cross-community bridge._
- **Why does `RouterSetting` connect `Router Settings` to `Eloquent Models`, `MikroTik Connectivity`?**
  _High betweenness centrality (0.099) - this node is a cross-community bridge._
- **Why does `RouterController` connect `Router Settings` to `API Controllers`?**
  _High betweenness centrality (0.045) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `GenerateMikrotikCardJob` (e.g. with `.retry()` and `.receive()`) actually correct?**
  _`GenerateMikrotikCardJob` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `self`, `self`, `name` to the rest of the system?**
  _34 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Composer Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.05405405405405406 - nodes in this community are weakly interconnected._
- **Should `Eloquent Models` be split into smaller, more focused modules?**
  _Cohesion score 0.08412698412698413 - nodes in this community are weakly interconnected._