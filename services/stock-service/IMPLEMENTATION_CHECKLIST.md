# ✅ STOCK SERVICE IMPLEMENTATION CHECKLIST

> **Lead Architect**: Checklist ini harus diikuti secara berurutan  
> **Target**: Production-ready service dengan 100% compliance terhadap spesifikasi

---

## PHASE 0: ARCHITECTURE DECISIONS & RECONCILIATION ✓

### 0.1 Critical Decisions Resolved

**Decision 1: Tabel `users` vs `warehouse_staff`**
- ✅ **FINAL**: Gunakan `users`
- **Alasan**: 
  - ERD publik menggunakan `users`
  - GraphQL schema external sudah defined dengan tipe `User`
  - Laravel convention menggunakan `users` untuk authentication
  - Konsistensi dengan inter-service contract
- **Trade-off**: Proposal internal menyebut `warehouse_staff`, tapi kita prioritaskan konsistensi dengan ERD dan standar Laravel

**Decision 2: Database Port Mapping**
- ✅ **FINAL**: stock-db → Port **3307** (host), shipping-db → Port 3308
- **Alasan**: Sesuai Proposal original untuk menghindari konflik port
- **Action**: Pastikan `docker-compose.yml` di root menggunakan `3307:3306` untuk stock-db

**Decision 3: Foreign Key Strategy (CRITICAL untuk Integrasi)**
- ✅ **FINAL**: `product_code` adalah **LOGICAL RELATIONSHIP**, bukan FK fisik cross-database
- **Penjelasan**:
  ```
  Stock Service (stock_db):
    - inventory.product_code (UNIQUE)
    - stock_transactions.product_code → FK fisik ke inventory (same DB)
  
  Shipping Service (shipping_db):
    - warehouse_orders.product_code → TIDAK ADA FK fisik
    - Hanya store sebagai string value
    - Validasi via GraphQL query ke Stock Service
  ```
- **Reason**: Microservices dengan database terpisah tidak boleh ada FK fisik cross-database
- **Integration Pattern**: 
  - Shipping Service call `checkStock(productCode)` via GraphQL sebelum create order
  - Stock Service menjaga data integrity di database-nya sendiri

### 0.2 Port Allocation Summary
```
Service Ports:
- Stock Service API:     8003
- Shipping Service API:  8004

Database Ports (Host Access):
- stock-db:     3307 → MySQL 3306
- shipping-db:  3308 → MySQL 3306
```

---

## PHASE 1: DATABASE SCHEMA ✓

### 1.1 Create Migrations
```bash
cd services/stock-service

# Migration 1: Users table (base + warehouse extensions)
php artisan make:migration create_users_table

# Migration 2: Inventory table (with monitoring columns)
php artisan make:migration create_inventory_table

# Migration 3: Stock Transactions (audit trail)
php artisan make:migration create_stock_transactions_table

# Migration 4: Stock Alerts (monitoring system)
php artisan make:migration create_stock_alerts_table
```

**Expected Files:**
- `database/migrations/2024_01_01_000001_create_users_table.php`
- `database/migrations/2024_01_01_000002_create_inventory_table.php`
- `database/migrations/2024_01_01_000003_create_stock_transactions_table.php`
- `database/migrations/2024_01_01_000004_create_stock_alerts_table.php`

**Validation:**
- [ ] Semua migrations menggunakan Schema::create
- [ ] Foreign keys dengan onDelete('cascade') atau appropriate action
- [ ] Timestamps menggunakan $table->timestamps()
- [ ] ENUM values sesuai spesifikasi

---

### 1.2 Schema Specification

**`users` Table Schema:**
```php
// IMPORTANT: Nama tabel 'users' (bukan 'warehouse_staff')
// Alasan: Konsistensi dengan ERD dan GraphQL schema publik
// Proposal internal menyebut warehouse_staff, tapi kita ikuti ERD
// Laravel default juga menggunakan 'users' untuk auth
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('username')->unique();  // LOGIN IDENTIFIER
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');  // bcrypt, cost 10+
    $table->enum('role', ['admin', 'manager', 'staff'])->default('staff');
    $table->enum('department', ['inventory', 'shipping', 'both'])->default('inventory');
    $table->timestamps();
    
    // Indexes for performance
    $table->index('username');
    $table->index('role');
});
```

**`inventory` Table Schema:**
```php
Schema::create('inventory', function (Blueprint $table) {
    $table->id();
    $table->string('product_code')->unique();  // KUNCI INTEGRASI LOGIS
    $table->string('product_name');
    $table->integer('stock')->default(0);  // current_stock (ERD: stock)
    $table->integer('min_stock_level')->default(10);
    $table->integer('max_stock_level')->default(1000);
    $table->timestamps();
    
    // Critical indexes
    $table->index('product_code');
    $table->index(['stock', 'min_stock_level']);  // For alert queries
});
```

**`stock_transactions` Table Schema:**
```php
Schema::create('stock_transactions', function (Blueprint $table) {
    $table->id();
    $table->string('product_code');  // LOGICAL FK (bukan fisik cross-database)
    $table->integer('quantity');
    $table->enum('action', ['INCREMENT', 'DECREMENT', 'SET', 'RESERVE', 'RELEASE']);
    $table->text('note')->nullable();
    $table->unsignedBigInteger('staff_id')->nullable();
    $table->integer('stock_before');
    $table->integer('stock_after');
    $table->timestamp('created_at');
    
    // Foreign keys
    // product_code: FK fisik ke inventory (same database)
    $table->foreign('product_code')->references('product_code')->on('inventory')->onDelete('cascade');
    // staff_id: FK fisik ke users (same database)
    $table->foreign('staff_id')->references('id')->on('users')->onDelete('set null');
    
    // ⚠️ IMPORTANT: product_code adalah LOGICAL relationship untuk cross-service
    // Shipping Service TIDAK boleh buat FK fisik ke tabel ini (beda database)
    // Mereka hanya reference product_code sebagai string value
    
    // Indexes
    $table->index('product_code');
    $table->index('staff_id');
    $table->index('created_at');
});
```

**`stock_alerts` Table Schema:**
```php
Schema::create('stock_alerts', function (Blueprint $table) {
    $table->id();
    $table->string('product_code');
    $table->enum('alert_type', ['LOW_STOCK', 'OUT_OF_STOCK']);
    $table->boolean('resolved')->default(false);
    $table->unsignedBigInteger('resolved_by')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    // Foreign keys
    $table->foreign('product_code')->references('product_code')->on('inventory')->onDelete('cascade');
    $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
    
    // Indexes
    $table->index(['product_code', 'resolved']);
    $table->index('alert_type');
});
```

**Validation Checklist:**
- [ ] product_code sebagai UNIQUE constraint di inventory ✓
- [ ] Foreign keys menggunakan product_code (logical key) ✓
- [ ] ENUM values match spesifikasi ✓
- [ ] Timestamps untuk audit ✓

---

### 1.3 Run Migrations

```bash
# Inside Docker container
docker-compose exec stock-service php artisan migrate

# Expected output: 4 migrations berhasil
# Verify tables created
docker-compose exec stock-service php artisan db:show
```

**Validation:**
- [ ] 4 tabel berhasil dibuat
- [ ] Foreign key constraints active
- [ ] Indexes terbuat dengan benar

---

## PHASE 2: MODELS & RELATIONSHIPS ✓

### 2.1 Create Eloquent Models

```bash
php artisan make:model User  # Extend default User model
php artisan make:model Inventory
php artisan make:model StockTransaction
php artisan make:model StockAlert
```

### 2.2 Model Configuration

**`app/Models/User.php`:**
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'department',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'staff_id');
    }

    public function resolvedAlerts()
    {
        return $this->hasMany(StockAlert::class, 'resolved_by');
    }

    // Helper Methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}
```

**`app/Models/Inventory.php`:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_code',
        'product_name',
        'stock',
        'min_stock_level',
        'max_stock_level',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'product_code', 'product_code')
                    ->orderBy('created_at', 'desc');
    }

    public function alerts()
    {
        return $this->hasMany(StockAlert::class, 'product_code', 'product_code');
    }

    public function activeAlerts()
    {
        return $this->alerts()->where('resolved', false);
    }

    // Business Logic Methods
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock_level && $this->stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function needsAlert(): bool
    {
        return $this->isLowStock() || $this->isOutOfStock();
    }
}
```

**`app/Models/StockTransaction.php`:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    const UPDATED_AT = null; // Only created_at, no updated_at

    protected $fillable = [
        'product_code',
        'quantity',
        'action',
        'note',
        'staff_id',
        'stock_before',
        'stock_after',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'product_code', 'product_code');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
```

**`app/Models/StockAlert.php`:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    protected $fillable = [
        'product_code',
        'alert_type',
        'resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'product_code', 'product_code');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
```

**Validation:**
- [ ] All relationships defined correctly
- [ ] Fillable/guarded configured
- [ ] Casts untuk type safety
- [ ] Helper methods untuk business logic

---

## PHASE 3: AUTHENTICATION SYSTEM ✓

### 3.1 JWT Configuration

**Create `config/jwt.php`:**
```php
<?php

return [
    'secret' => env('JWT_SECRET', 'your_secret_key_here'),
    'algo' => 'HS256',
    'expiration' => env('JWT_EXPIRATION', 1800), // 30 minutes
    'leeway' => 60, // 1 minute clock skew tolerance
];
```

**Update `.env.example`:**
```env
# JWT Configuration (SHARED WITH SHIPPING SERVICE)
JWT_SECRET=shared_secret_stock_shipping_2024
JWT_EXPIRATION=1800

# API Key for External Access (Product Service - Toko)
EXTERNAL_API_KEY_TOKO=toko_api_key_2024_secure
```

### 3.2 Install JWT Library

**Update `composer.json`:**
```json
{
    "require": {
        "firebase/php-jwt": "^6.10"
    }
}
```

```bash
docker-compose exec stock-service composer require firebase/php-jwt
```

### 3.3 JWT Helper Service

**Create `app/Services/JWTService.php`:**
```php
<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class JWTService
{
    private string $secret;
    private string $algo;
    private int $expiration;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->algo = config('jwt.algo');
        $this->expiration = config('jwt.expiration');
    }

    public function generateToken(User $user): array
    {
        $now = time();
        $exp = $now + $this->expiration;
        $jti = uniqid('jwt_', true);

        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'department' => $user->department,
            'iat' => $now,
            'exp' => $exp,
            'jti' => $jti,
        ];

        $token = JWT::encode($payload, $this->secret, $this->algo);

        return [
            'token' => $token,
            'expiresIn' => $this->expiration,
            'expiresAt' => date('Y-m-d H:i:s', $exp),
        ];
    }

    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

            // Check if token is blacklisted
            if ($this->isBlacklisted($decoded->jti)) {
                return null;
            }

            return $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Invalid token signature');
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    public function blacklistToken(string $jti, int $exp): void
    {
        $ttl = max(0, $exp - time());
        Cache::put("blacklisted_token:{$jti}", true, $ttl);
    }

    public function isBlacklisted(string $jti): bool
    {
        return Cache::has("blacklisted_token:{$jti}");
    }
}
```

### 3.4 Authentication Middleware

**Create `app/Http/Middleware/AuthenticateJWT.php`:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\JWTService;
use App\Models\User;

class AuthenticateJWT
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'errors' => [
                    [
                        'message' => 'Unauthenticated - No token provided',
                        'extensions' => ['category' => 'authentication']
                    ]
                ]
            ], 401);
        }

        try {
            $decoded = $this->jwtService->validateToken($token);

            if (!$decoded) {
                throw new \Exception('Token is blacklisted');
            }

            // Load user and set in request
            $user = User::find($decoded->sub);

            if (!$user) {
                throw new \Exception('User not found');
            }

            $request->merge(['user' => $user]);
            auth()->setUser($user);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    [
                        'message' => 'Unauthenticated - ' . $e->getMessage(),
                        'extensions' => ['category' => 'authentication']
                    ]
                ]
            ], 401);
        }
    }
}
```

**Create `app/Http/Middleware/ValidateExternalApiKey.php`:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateExternalApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        $validKey = config('services.external_api_key');

        if (!$apiKey || $apiKey !== $validKey) {
            return response()->json([
                'errors' => [
                    [
                        'message' => 'Invalid or missing API Key',
                        'extensions' => ['category' => 'authorization']
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
```

**Update `config/services.php`:**
```php
return [
    // ... existing services
    'external_api_key' => env('EXTERNAL_API_KEY_TOKO'),
];
```

**Validation:**
- [ ] JWT generation works
- [ ] Token validation works
- [ ] Blacklisting mechanism works
- [ ] Middleware blocks unauthorized requests

---

## PHASE 4: GRAPHQL SCHEMA DEFINITION ✓

### 4.1 Main Schema Structure

**Edit `graphql/schema.graphql`:**
```graphql
"A datetime string with format `Y-m-d H:i:s`, e.g. `2024-01-01 13:00:00`."
scalar DateTime @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\DateTime")

# ============================================
# AUTHENTICATION & AUTHORIZATION
# ============================================

type Mutation {
    "Login untuk warehouse staff, returns JWT token"
    login(username: String!, password: String!): AuthPayload!
        @field(resolver: "App\\GraphQL\\Mutations\\Login")

    "Logout - blacklist current token"
    logout: Boolean!
        @field(resolver: "App\\GraphQL\\Mutations\\Logout")
        @middleware(checks: ["auth:api"])

    "Register new staff (Admin only)"
    registerStaff(input: RegisterStaffInput!): User!
        @field(resolver: "App\\GraphQL\\Mutations\\RegisterStaff")
        @middleware(checks: ["auth:api", "role:admin"])

    "Update staff information"
    updateStaff(id: ID!, input: UpdateStaffInput!): User!
        @field(resolver: "App\\GraphQL\\Mutations\\UpdateStaff")
        @middleware(checks: ["auth:api", "role:admin,manager"])

    "Delete staff (Admin only)"
    deleteStaff(id: ID!): Boolean!
        @field(resolver: "App\\GraphQL\\Mutations\\DeleteStaff")
        @middleware(checks: ["auth:api", "role:admin"])
}

type Query {
    "Get currently authenticated user"
    me: User!
        @field(resolver: "App\\GraphQL\\Queries\\Me")
        @middleware(checks: ["auth:api"])

    "Get staff by ID (Admin/Manager only)"
    staffById(id: ID!): User
        @field(resolver: "App\\GraphQL\\Queries\\StaffById")
        @middleware(checks: ["auth:api", "role:admin,manager"])

    "List all staff with optional filters (Admin/Manager only)"
    staffList(
        role: UserRole
        department: Department
    ): [User!]!
        @field(resolver: "App\\GraphQL\\Queries\\StaffList")
        @middleware(checks: ["auth:api", "role:admin,manager"])
}

type AuthPayload {
    token: String!
    expiresIn: Int!
    user: User!
}

type User {
    id: ID!
    username: String!
    name: String!
    email: String!
    role: UserRole!
    department: Department!
    createdAt: DateTime!
    updatedAt: DateTime!
    
    "Transactions created by this user"
    transactions: [StockTransaction!]
}

enum UserRole {
    admin
    manager
    staff
}

enum Department {
    inventory
    shipping
    both
}

input RegisterStaffInput {
    username: String!
    name: String!
    email: String!
    password: String!
    role: UserRole!
    department: Department!
}

input UpdateStaffInput {
    name: String
    email: String
    password: String
    role: UserRole
    department: Department
}

# ============================================
# INVENTORY MANAGEMENT
# ============================================

extend type Query {
    "Get single inventory item by product code"
    inventory(productCode: String!): Inventory
        @field(resolver: "App\\GraphQL\\Queries\\Inventory")

    "List all inventory with optional filters"
    inventoryList(
        search: String
        lowStockOnly: Boolean
        limit: Int
    ): [Inventory!]!
        @field(resolver: "App\\GraphQL\\Queries\\InventoryList")

    "Check stock availability for single product (PUBLIC - API Key or JWT)"
    checkStock(productCode: String!, quantity: Int!): StockCheckResult!
        @field(resolver: "App\\GraphQL\\Queries\\CheckStock")

    "Bulk check stock for multiple products (PUBLIC - API Key or JWT)"
    bulkCheckStock(items: [BulkStockCheckInput!]!): BulkStockCheckResult!
        @field(resolver: "App\\GraphQL\\Queries\\BulkCheckStock")
}

extend type Mutation {
    "Update stock (increment/decrement/set)"
    updateStock(input: UpdateStockInput!): Inventory!
        @field(resolver: "App\\GraphQL\\Mutations\\UpdateStock")
        @middleware(checks: ["auth:api"])

    "Reserve stock for order (used by Shipping Service)"
    reserveStock(input: ReserveStockInput!): StockCheckResult!
        @field(resolver: "App\\GraphQL\\Mutations\\ReserveStock")
        @middleware(checks: ["auth:api"])

    "Release reserved stock (order cancelled)"
    releaseStock(productCode: String!, quantity: Int!, orderId: String!): Inventory!
        @field(resolver: "App\\GraphQL\\Mutations\\ReleaseStock")
        @middleware(checks: ["auth:api"])

    "Adjust stock to absolute value (Admin/Manager only)"
    adjustStock(productCode: String!, newStock: Int!, note: String): Inventory!
        @field(resolver: "App\\GraphQL\\Mutations\\AdjustStock")
        @middleware(checks: ["auth:api", "role:admin,manager"])
}

type Inventory {
    id: ID!
    productCode: String!
    productName: String!
    stock: Int!
    minStockLevel: Int!
    maxStockLevel: Int!
    createdAt: DateTime!
    updatedAt: DateTime!
    
    "Active alerts for this product"
    alerts: [StockAlert!]
    
    "Transaction history"
    transactions: [StockTransaction!]
}

type StockCheckResult {
    productCode: String!
    productName: String!
    available: Boolean!
    currentStock: Int!
    requestedQuantity: Int!
    message: String!
}

type BulkStockCheckResult {
    allAvailable: Boolean!
    results: [StockCheckResult!]!
}

input BulkStockCheckInput {
    productCode: String!
    quantity: Int!
}

input UpdateStockInput {
    productCode: String!
    quantity: Int!
    action: StockAction!
    note: String
}

input ReserveStockInput {
    productCode: String!
    quantity: Int!
    orderId: String!
}

enum StockAction {
    INCREMENT
    DECREMENT
    SET
    RESERVE
    RELEASE
}

# ============================================
# MONITORING & ALERTS
# ============================================

extend type Query {
    "Get stock alerts with optional filter"
    lowStockAlerts(resolved: Boolean): [StockAlert!]!
        @field(resolver: "App\\GraphQL\\Queries\\LowStockAlerts")
        @middleware(checks: ["auth:api"])

    "Get stock transaction history"
    stockTransactions(
        productCode: String
        action: StockAction
        limit: Int = 50
    ): [StockTransaction!]!
        @field(resolver: "App\\GraphQL\\Queries\\StockTransactions")
        @middleware(checks: ["auth:api"])
}

extend type Mutation {
    "Resolve stock alert"
    resolveAlert(alertId: ID!): StockAlert!
        @field(resolver: "App\\GraphQL\\Mutations\\ResolveAlert")
        @middleware(checks: ["auth:api", "role:admin,manager"])
}

type StockAlert {
    id: ID!
    productCode: String!
    alertType: AlertType!
    resolved: Boolean!
    resolvedBy: ID
    resolvedAt: DateTime
    createdAt: DateTime!
    updatedAt: DateTime!
    
    "Related inventory item"
    inventory: Inventory
    
    "User who resolved this alert"
    resolver: User
}

type StockTransaction {
    id: ID!
    productCode: String!
    quantity: Int!
    action: StockAction!
    note: String
    staffId: ID
    stockBefore: Int!
    stockAfter: Int!
    createdAt: DateTime!
    
    "Related inventory item"
    inventory: Inventory
    
    "Staff who performed this transaction"
    staff: User
}

enum AlertType {
    LOW_STOCK
    OUT_OF_STOCK
}
```

**Validation:**
- [ ] All types defined
- [ ] Middleware directives correct
- [ ] Dual auth support (JWT dan API Key)
- [ ] Relationships mapped

---

## PHASE 5: GRAPHQL RESOLVERS ✓

### 5.1 Authentication Resolvers

**Create `app/GraphQL/Mutations/Login.php`:**
```php
<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\JWTService;
use Illuminate\Support\Facades\Hash;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Login
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = User::where('username', $args['username'])->first();

        if (!$user || !Hash::check($args['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $tokenData = $this->jwtService->generateToken($user);

        return [
            'token' => $tokenData['token'],
            'expiresIn' => $tokenData['expiresIn'],
            'user' => $user,
        ];
    }
}
```

**Create `app/GraphQL/Mutations/Logout.php`:**
```php
<?php

namespace App\GraphQL\Mutations;

use App\Services\JWTService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Logout
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $request = $context->request();
        $token = $request->bearerToken();

        try {
            $decoded = $this->jwtService->validateToken($token);
            $this->jwtService->blacklistToken($decoded->jti, $decoded->exp);
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Logout failed: ' . $e->getMessage());
        }
    }
}
```

**Create `app/GraphQL/Queries/Me.php`:**
```php
<?php

namespace App\GraphQL\Queries;

class Me
{
    public function __invoke($rootValue, array $args)
    {
        return auth()->user();
    }
}
```

### 5.2 Inventory Resolvers (CRITICAL)

**Create `app/GraphQL/Queries/CheckStock.php`:**
```php
<?php

namespace App\GraphQL\Queries;

use App\Models\Inventory;

class CheckStock
{
    public function __invoke($rootValue, array $args)
    {
        $inventory = Inventory::where('product_code', $args['productCode'])->first();

        if (!$inventory) {
            return [
                'productCode' => $args['productCode'],
                'productName' => 'Unknown Product',
                'available' => false,
                'currentStock' => 0,
                'requestedQuantity' => $args['quantity'],
                'message' => 'Product not found in inventory',
            ];
        }

        $available = $inventory->stock >= $args['quantity'];

        return [
            'productCode' => $inventory->product_code,
            'productName' => $inventory->product_name,
            'available' => $available,
            'currentStock' => $inventory->stock,
            'requestedQuantity' => $args['quantity'],
            'message' => $available 
                ? 'Stock available' 
                : "Insufficient stock. Available: {$inventory->stock}, Requested: {$args['quantity']}",
        ];
    }
}
```

**Create `app/GraphQL/Mutations/UpdateStock.php`:**
```php
<?php

namespace App\GraphQL\Mutations;

use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\StockAlert;
use Illuminate\Support\Facades\DB;

class UpdateStock
{
    public function __invoke($rootValue, array $args)
    {
        $input = $args['input'];
        $user = auth()->user();

        return DB::transaction(function () use ($input, $user) {
            // Lock inventory row
            $inventory = Inventory::where('product_code', $input['productCode'])
                ->lockForUpdate()
                ->firstOrFail();

            $stockBefore = $inventory->stock;
            $quantity = $input['quantity'];

            // Apply stock change based on action
            switch ($input['action']) {
                case 'INCREMENT':
                    $inventory->stock += $quantity;
                    break;
                case 'DECREMENT':
                    if ($inventory->stock < $quantity) {
                        throw new \Exception("Insufficient stock. Available: {$inventory->stock}, Requested: {$quantity}");
                    }
                    $inventory->stock -= $quantity;
                    break;
                case 'SET':
                    $inventory->stock = $quantity;
                    break;
                case 'RESERVE':
                    if ($inventory->stock < $quantity) {
                        throw new \Exception("Cannot reserve. Insufficient stock.");
                    }
                    $inventory->stock -= $quantity;
                    break;
                case 'RELEASE':
                    $inventory->stock += $quantity;
                    break;
            }

            $stockAfter = $inventory->stock;
            $inventory->save();

            // Create transaction record (AUDIT TRAIL)
            StockTransaction::create([
                'product_code' => $inventory->product_code,
                'quantity' => $quantity,
                'action' => $input['action'],
                'note' => $input['note'] ?? null,
                'staff_id' => $user->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ]);

            // Auto-generate alerts
            $this->checkAndGenerateAlerts($inventory);

            return $inventory->fresh();
        });
    }

    private function checkAndGenerateAlerts(Inventory $inventory): void
    {
        // Resolve previous alerts if stock is back to normal
        if ($inventory->stock > $inventory->min_stock_level) {
            StockAlert::where('product_code', $inventory->product_code)
                ->where('resolved', false)
                ->update(['resolved' => true, 'resolved_at' => now()]);
            return;
        }

        // Determine alert type
        $alertType = $inventory->stock <= 0 ? 'OUT_OF_STOCK' : 'LOW_STOCK';

        // Check if alert already exists
        $existingAlert = StockAlert::where('product_code', $inventory->product_code)
            ->where('alert_type', $alertType)
            ->where('resolved', false)
            ->first();

        if (!$existingAlert) {
            StockAlert::create([
                'product_code' => $inventory->product_code,
                'alert_type' => $alertType,
                'resolved' => false,
            ]);
        }
    }
}
```

**Validation:**
- [ ] Database transactions untuk consistency
- [ ] Row locking untuk prevent race conditions
- [ ] Audit trail terecord di stock_transactions
- [ ] Auto-generate alerts

---

## PHASE 6: MIDDLEWARE REGISTRATION ✓

**Edit `config/lighthouse.php`:**
```php
'route' => [
    'uri' => '/graphql',
    'middleware' => [
        \App\Http\Middleware\DualAuthentication::class,  // NEW: Dual auth
    ],
],

'middleware_aliases' => [
    'auth:api' => \App\Http\Middleware\AuthenticateJWT::class,
    'role' => \App\Http\Middleware\CheckRole::class,
],
```

**Create `app/Http/Middleware/DualAuthentication.php`:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\JWTService;
use App\Models\User;

class DualAuthentication
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check for API Key first (Product Service - External)
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('services.external_api_key');

        if ($apiKey && $apiKey === $validApiKey) {
            // External access authenticated
            $request->merge(['auth_method' => 'api_key']);
            return $next($request);
        }

        // Check for JWT token (Shipping Service - Internal)
        $token = $request->bearerToken();

        if ($token) {
            try {
                $decoded = $this->jwtService->validateToken($token);

                if ($decoded) {
                    $user = User::find($decoded->sub);

                    if ($user) {
                        auth()->setUser($user);
                        $request->merge(['auth_method' => 'jwt', 'user' => $user]);
                        return $next($request);
                    }
                }
            } catch (\Exception $e) {
                // Token invalid, continue without auth
            }
        }

        // No authentication provided - allow for public queries
        $request->merge(['auth_method' => 'none']);
        return $next($request);
    }
}
```

**Create `app/Http/Middleware/CheckRole.php`:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'errors' => [[
                    'message' => 'Unauthenticated',
                    'extensions' => ['category' => 'authentication']
                ]]
            ], 401);
        }

        $allowedRoles = is_array($roles) ? $roles : explode(',', $roles);

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'errors' => [[
                    'message' => 'Unauthorized - Insufficient permissions',
                    'extensions' => ['category' => 'authorization']
                ]]
            ], 403);
        }

        return $next($request);
    }
}
```

---

## PHASE 7: DATABASE SEEDERS ✓

**Create `database/seeders/UserSeeder.php`:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'username' => 'admin',
                'name' => 'Admin Warehouse',
                'email' => 'admin@warehouse.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'department' => 'both',
            ],
            [
                'username' => 'manager_inv',
                'name' => 'Manager Inventory',
                'email' => 'manager@warehouse.com',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'department' => 'inventory',
            ],
            [
                'username' => 'staff_ship',
                'name' => 'Staff Shipping',
                'email' => 'staff@warehouse.com',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'department' => 'shipping',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
```

**Create `database/seeders/InventorySeeder.php`:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['ELEC001', 'Laptop ASUS ROG', 50, 10, 100],
            ['ELEC002', 'Monitor LG 27 inch', 30, 5, 50],
            ['ELEC003', 'Keyboard Mechanical', 100, 20, 200],
            ['ELEC004', 'Mouse Wireless Logitech', 150, 30, 300],
            ['ELEC005', 'Headset Gaming', 80, 15, 150],
            ['ELEC006', 'Webcam HD 1080p', 40, 8, 80],
            ['ELEC007', 'SSD 1TB Samsung', 60, 12, 120],
            ['ELEC008', 'RAM DDR4 16GB', 90, 18, 180],
            ['ELEC009', 'Power Supply 650W', 25, 5, 50],
            ['ELEC010', 'CPU Cooler RGB', 35, 7, 70],
        ];

        foreach ($products as $product) {
            Inventory::create([
                'product_code' => $product[0],
                'product_name' => $product[1],
                'stock' => $product[2],
                'min_stock_level' => $product[3],
                'max_stock_level' => $product[4],
            ]);
        }
    }
}
```

**Update `database/seeders/DatabaseSeeder.php`:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
```

**Run Seeders:**
```bash
docker-compose exec stock-service php artisan db:seed
```

---

## PHASE 8: DOCKER & DEPLOYMENT ✓

### 8.1 Verify Docker Configuration

**Check `docker-compose.yml` (from root):**
```yaml
stock-service:
    build:
      context: ./services/stock-service
      dockerfile: Dockerfile
    ports:
      - "8003:8000"
    environment:
      - APP_ENV=local
      - DB_HOST=stock-db
      - DB_PORT=3306
      - DB_DATABASE=stock_db
      - DB_USERNAME=stock_user
      - DB_PASSWORD=stock_password
      - JWT_SECRET=shared_secret_stock_shipping_2024
      - EXTERNAL_API_KEY_TOKO=toko_api_key_2024_secure
    depends_on:
      - stock-db
    networks:
      - stock-network
      - shared-network  # For inter-service communication

  stock-db:
    image: mysql:8.0
    ports:
      - "3307:3306"  # Port 3307 (sesuai Proposal, bukan 3308)
    environment:
      - MYSQL_DATABASE=stock_db
      - MYSQL_USER=stock_user
      - MYSQL_PASSWORD=stock_password
      - MYSQL_ROOT_PASSWORD=root_password
    volumes:
      - stock-db-data:/var/lib/mysql
    networks:
      - stock-network

networks:
  stock-network:
  shared-network:
    external: true  # For cross-service communication

volumes:
  stock-db-data:
```

### 8.2 Build & Run

```bash
# From root directory
cd c:\Users\MyBook Hype AMD\Documents\SEMESTER 5\EAI\IAE-kurang-tidur

# Build service
docker-compose build stock-service

# Start services
docker-compose up -d stock-service stock-db

# Check logs
docker-compose logs -f stock-service

# Run migrations inside container
docker-compose exec stock-service php artisan migrate --force

# Run seeders
docker-compose exec stock-service php artisan db:seed --force
```

**Validation:**
- [ ] Container running on port 8003
- [ ] Database connection successful
- [ ] GraphQL playground accessible at http://localhost:8003/graphql

---

## PHASE 9: INTEGRATION TESTING ✓

### 9.1 Test Authentication (JWT)

**Test 1: Login**
```graphql
mutation {
  login(username: "admin", password: "admin123") {
    token
    expiresIn
    user {
      id
      username
      role
      department
    }
  }
}
```

**Expected:** Token returned successfully

**Test 2: Use Token**
```graphql
# Headers: Authorization: Bearer <token_from_test1>
query {
  me {
    username
    role
  }
}
```

**Expected:** User data returned

### 9.2 Test External API (API Key)

**Test 3: Check Stock with API Key**
```graphql
# Headers: X-API-Key: toko_api_key_2024_secure
query {
  checkStock(productCode: "ELEC001", quantity: 5) {
    productCode
    productName
    available
    currentStock
    message
  }
}
```

**Expected:** Stock data returned without JWT

### 9.3 Test Stock Operations

**Test 4: Update Stock**
```graphql
# Headers: Authorization: Bearer <token>
mutation {
  updateStock(input: {
    productCode: "ELEC001"
    quantity: 10
    action: DECREMENT
    note: "Test transaction"
  }) {
    productCode
    stock
  }
}
```

**Expected:** Stock decreased, transaction recorded

### 9.4 Test Auto-Alert Generation

**Test 5: Trigger Low Stock Alert**
```graphql
# Headers: Authorization: Bearer <token>
mutation {
  updateStock(input: {
    productCode: "ELEC001"
    quantity: 45  # Assuming current stock is 50, min is 10
    action: DECREMENT
    note: "Reduce to trigger alert"
  }) {
    productCode
    stock
  }
}

# Then check alerts
query {
  lowStockAlerts(resolved: false) {
    productCode
    alertType
    inventory {
      productName
      stock
      minStockLevel
    }
  }
}
```

**Expected:** Alert created for ELEC001

---

## PHASE 10: DOCUMENTATION FOR TOKO GROUP ✓

### 10.1 Create Integration Guide

**Create `docs/EXTERNAL_API_GUIDE.md`:**
```markdown
# Stock Service API - Integration Guide for Product Service (Toko)

## Endpoint
```
http://stock-service:8003/graphql  (Internal Docker network)
http://localhost:8003/graphql      (External testing)
```

## Authentication
Use header: `X-API-Key: toko_api_key_2024_secure`

## Available Queries

### 1. Check Single Product Stock
\`\`\`graphql
query {
  checkStock(productCode: "ELEC001", quantity: 10) {
    productCode
    productName
    available
    currentStock
    requestedQuantity
    message
  }
}
\`\`\`

### 2. Bulk Stock Check
\`\`\`graphql
query {
  bulkCheckStock(items: [
    { productCode: "ELEC001", quantity: 5 },
    { productCode: "ELEC002", quantity: 10 }
  ]) {
    allAvailable
    results {
      productCode
      available
      currentStock
      message
    }
  }
}
\`\`\`

### 3. Get All Inventory
\`\`\`graphql
query {
  inventoryList {
    productCode
    productName
    stock
    minStockLevel
  }
}
\`\`\`

## Example cURL Request
\`\`\`bash
curl -X POST http://localhost:8003/graphql \
  -H "Content-Type: application/json" \
  -H "X-API-Key: toko_api_key_2024_secure" \
  -d '{"query":"{ checkStock(productCode: \"ELEC001\", quantity: 5) { available currentStock } }"}'
\`\`\`

## Product Codes Available
- ELEC001 to ELEC010
```

---

## PHASE 11: SCREENSHOT CHECKLIST ✓

### Screenshots for PDF Report

1. **GraphQL Playground - Login Success**
   - URL: http://localhost:8003/graphql
   - Mutation: login
   - Show returned token and user data

2. **GraphQL Playground - API Key Access**
   - Show checkStock query with X-API-Key header
   - Highlight header configuration
   - Show successful response

3. **Database - inventory Table**
   - Screenshot of 10 products (ELEC001-ELEC010)
   - Show stock levels

4. **Database - stock_transactions Table**
   - Screenshot showing audit trail
   - Include staff_id, action, stock_before, stock_after

5. **Docker Logs - Request from Toko**
   - Show incoming GraphQL request with API Key
   - Demonstrate external access

6. **Docker Logs - Request from Shipping**
   - Show incoming request with JWT token
   - Demonstrate internal access

---

## FINAL VALIDATION CHECKLIST ✓

### Critical Success Criteria

- [ ] **Database Schema**
  - [ ] users table dengan role & department
  - [ ] inventory table dengan min/max levels
  - [ ] stock_transactions untuk audit
  - [ ] stock_alerts dengan auto-generation

- [ ] **Authentication**
  - [ ] JWT token generation (30 min expiry)
  - [ ] Token validation dengan shared secret
  - [ ] Token blacklisting pada logout
  - [ ] Password hashing bcrypt cost 10+

- [ ] **Dual Authorization**
  - [ ] JWT works untuk internal (Shipping)
  - [ ] API Key works untuk external (Toko)
  - [ ] Both dapat akses checkStock/bulkCheckStock

- [ ] **Business Logic**
  - [ ] Stock updates create transaction records
  - [ ] Alerts auto-generate when stock <= min_level
  - [ ] Database transactions untuk data consistency
  - [ ] Row locking untuk prevent race conditions

- [ ] **Integration**
  - [ ] Service runs on port 8003
  - [ ] GraphQL endpoint accessible
  - [ ] Can be called from other services via Docker network
  - [ ] API documentation ready untuk Toko group

- [ ] **Data & Testing**
  - [ ] 10 products seeded (ELEC001-ELEC010)
  - [ ] 3 users seeded (admin, manager, staff)
  - [ ] All GraphQL queries tested
  - [ ] Screenshots collected

---

## TROUBLESHOOTING COMMON ISSUES

### Issue: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
```bash
# Wait for DB to be ready
docker-compose restart stock-service
# Or add healthcheck in docker-compose.yml
```

### Issue: "Class 'Firebase\JWT\JWT' not found"
**Solution:**
```bash
docker-compose exec stock-service composer require firebase/php-jwt
docker-compose exec stock-service composer dump-autoload
```

### Issue: "Field 'checkStock' not found"
**Solution:**
```bash
docker-compose exec stock-service php artisan lighthouse:clear-cache
docker-compose exec stock-service php artisan config:clear
```

### Issue: JWT validation fails in Shipping Service
**Solution:**
```bash
# Ensure JWT_SECRET is EXACTLY the same in both .env files
# Stock Service .env
JWT_SECRET=shared_secret_stock_shipping_2024

# Shipping Service .env
JWT_SECRET=shared_secret_stock_shipping_2024
```

---

## NEXT STEPS AFTER COMPLETION

1. **Share dengan Shipping Service Team:**
   - JWT_SECRET value
   - GraphQL endpoint URL
   - Authentication flow documentation

2. **Share dengan Toko (Product Service) Team:**
   - API Key: `toko_api_key_2024_secure`
   - `docs/EXTERNAL_API_GUIDE.md`
   - Sample queries untuk checkStock dan bulkCheckStock

3. **Collect Evidence untuk Laporan:**
   - All screenshots dari checklist
   - Docker logs showing inter-service communication
   - Database dumps showing audit trail

4. **Performance Testing:**
   - Test concurrent stock updates
   - Measure response time untuk bulk queries
   - Verify no race conditions

---

## COMPLETION DECLARATION

**Service is READY FOR PRODUCTION when:**
✅ All items in Final Validation Checklist are checked  
✅ All 6 screenshots collected  
✅ Integration tested dengan minimal 1 external service  
✅ Documentation lengkap dan dibagikan ke team terkait  

**Estimated Total Implementation Time:** 8-12 hours untuk developer berpengalaman

**Point Value:** 25% dari total nilai (Integration criteria)

---

**Good luck, Arthur! Follow this checklist step-by-step and you'll have a production-grade Stock Service.** 🚀
