# 🤖 AI Development Guide - Stock Service

> **Comprehensive AI Prompt untuk Development, Debugging, dan Maintenance Stock Service**

---

## 📋 KONTEKS PROYEK

### Deskripsi Sistem
Stock Service adalah microservice berbasis Laravel 11 + GraphQL (Lighthouse) yang berfungsi sebagai:

1. **Auth Provider** - Single source of truth untuk autentikasi warehouse staff (JWT-based)
2. **Inventory Manager** - Kelola master data stok barang elektronik
3. **Stock Monitor** - Real-time alerts dan audit trail untuk perubahan stok
4. **Integration Provider** - Menyediakan API untuk Shipping Service (internal) dan Product Service (eksternal)

### Teknologi Stack
- **Framework**: Laravel 11.x
- **GraphQL**: Lighthouse PHP 6.x
- **Database**: MySQL 8.0 (Port 3308)
- **Authentication**: JWT (HS256) via custom middleware
- **Container**: Docker + Docker Compose
- **Port**: 8003 (GraphQL endpoint: `/graphql`)

### Arsitektur Microservices
```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│ Product Service │◄────────┤  Stock Service   ├────────►│Shipping Service │
│   (External)    │ API Key │   (Port 8003)    │   JWT   │  (Port 8004)    │
└─────────────────┘         └────────┬─────────┘         └─────────────────┘
                                     │
                                     ▼
                            ┌─────────────────┐
                            │   MySQL DB      │
                            │  stock_db:3308  │
                            └─────────────────┘
```

---

## 🎯 CORE RESPONSIBILITIES

### 1. Authentication & Authorization
```yaml
Fungsi:
  - Generate JWT token saat staff login
  - Validate token untuk setiap GraphQL request
  - Blacklist token saat logout
  - Share JWT secret dengan Shipping Service

Token Claims:
  sub: User ID
  username: Username
  role: admin|manager|staff
  department: inventory|shipping|both
  exp: Expiration timestamp (30 menit)
  jti: JWT ID untuk blacklisting

Roles & Permissions:
  admin:
    - Full access ke semua fitur
    - Manage staff (CRUD)
    - Adjust stock tanpa approval
  
  manager:
    - Manage inventory
    - Update stock (increment/decrement)
    - View staff list
    - Resolve alerts
  
  staff:
    - View inventory
    - Check stock availability
    - View own transactions
```

### 2. Inventory Management
```yaml
Entitas:
  - Product Code (unique identifier)
  - Product Name
  - Current Stock (real-time)
  - Min/Max Stock Levels
  - Auto-generated Alerts

Stock Operations:
  INCREMENT: Tambah stok (restocking)
  DECREMENT: Kurangi stok (damage, returns)
  SET: Set absolute value (stock opname)
  RESERVE: Reserve untuk order (temporary lock)
  RELEASE: Release reservation (order cancelled)

Business Rules:
  - Stock tidak boleh negatif
  - DECREMENT gagal jika stok tidak cukup
  - RESERVE gagal jika stok tidak tersedia
  - Alert auto-generate saat stock < min_level
  - Transaction history harus selalu terecord
```

### 3. External Integration
```yaml
Internal (Shipping Service):
  Endpoint: http://stock-service:8003/graphql
  Auth: JWT Token (shared secret)
  Use Cases:
    - Shipping staff login
    - Reserve stock untuk shipment
    - Release stock saat shipment cancelled
    - Check stock availability

External (Product Service):
  Endpoint: http://localhost:8003/graphql
  Auth: X-API-Key header
  Use Cases:
    - Bulk stock check untuk display di toko
    - Get inventory list
    - Check product availability
```

---

## 📁 STRUKTUR FILE PENTING

### GraphQL Schema
```
services/stock-service/graphql/schema.graphql
├── Authentication Mutations (login, logout)
├── Staff Management (registerStaff, updateStaff, deleteStaff)
├── Inventory Queries (inventory, inventoryList, checkStock, bulkCheckStock)
├── Stock Mutations (updateStock, reserveStock, releaseStock, adjustStock)
├── Alert Management (lowStockAlerts, resolveAlert)
└── Transaction History (stockTransactions)
```

### Models
```
app/Models/
├── WarehouseStaff.php    # User model untuk warehouse staff
├── Inventory.php         # Master data inventory
├── StockTransaction.php  # Audit trail stock changes
└── StockAlert.php        # Low stock / out of stock alerts
```

### Middleware
```
app/Http/Middleware/
├── AuthenticateJWT.php         # Validate JWT token
├── CheckRole.php               # Role-based access control
└── ValidateExternalApiKey.php  # Validate API key untuk Product Service
```

### GraphQL Resolvers
```
app/GraphQL/
├── Mutations/
│   ├── Login.php              # Login logic + JWT generation
│   ├── Logout.php             # Token blacklisting
│   ├── RegisterStaff.php      # Create new staff (admin only)
│   ├── UpdateStaff.php        # Update staff info
│   ├── UpdateStock.php        # Increment/Decrement/Set stock
│   ├── ReserveStock.php       # Reserve untuk order
│   ├── ReleaseStock.php       # Release reservation
│   ├── AdjustStock.php        # Manual adjustment (admin/manager)
│   └── ResolveAlert.php       # Mark alert as resolved
│
└── Queries/
    ├── Me.php                 # Get current authenticated user
    ├── StaffById.php          # Get staff by ID
    ├── StaffList.php          # List all staff (filtered)
    ├── Inventory.php          # Get single inventory item
    ├── InventoryList.php      # List all inventory (with filters)
    ├── CheckStock.php         # Single product availability check
    ├── BulkCheckStock.php     # Multiple products check
    ├── LowStockAlerts.php     # Get alerts (active/resolved)
    └── StockTransactions.php  # Get transaction history
```

### Database Migrations
```
database/migrations/
├── 2024_01_01_000000_create_warehouse_staff_table.php
├── 2024_01_01_000001_create_inventory_table.php
├── 2024_01_01_000002_create_stock_transactions_table.php
└── 2024_01_01_000003_create_stock_alerts_table.php
```

### Seeders
```
database/seeders/
├── DatabaseSeeder.php         # Master seeder
├── WarehouseStaffSeeder.php   # Default users (admin, manager, staff)
└── InventorySeeder.php        # Sample electronics products
```

### Configuration
```
config/
├── jwt.php          # JWT secret, expiration, algorithm
├── lighthouse.php   # GraphQL configuration
└── database.php     # MySQL connection (stock_db:3308)
```

---

## 🔧 COMMON DEVELOPMENT TASKS

### Task 1: Menambah Field Baru di Inventory

**Step 1: Update Migration**
```php
// database/migrations/xxxx_add_field_to_inventory.php
Schema::table('inventory', function (Blueprint $table) {
    $table->string('supplier_code')->nullable()->after('product_name');
    $table->decimal('unit_price', 10, 2)->default(0)->after('supplier_code');
});
```

**Step 2: Update Model**
```php
// app/Models/Inventory.php
protected $fillable = [
    // ... existing fields
    'supplier_code',
    'unit_price',
];
```

**Step 3: Update GraphQL Schema**
```graphql
# graphql/schema.graphql
type Inventory {
    # ... existing fields
    supplierCode: String
    unitPrice: Float!
}

input CreateInventoryInput {
    # ... existing fields
    supplierCode: String
    unitPrice: Float
}
```

**Step 4: Run Migration**
```bash
docker-compose exec stock-service php artisan migrate
```

**Step 5: Test dengan GraphQL**
```graphql
query {
  inventory(productCode: "ELECT-001") {
    productName
    supplierCode
    unitPrice
  }
}
```

---

### Task 2: Menambah Role/Permission Baru

**Step 1: Update Enum di Migration**
```php
// Create new migration
Schema::table('warehouse_staff', function (Blueprint $table) {
    $table->enum('role', ['admin', 'manager', 'staff', 'supervisor'])->change();
});
```

**Step 2: Update GraphQL Schema**
```graphql
enum UserRole {
    admin
    manager
    staff
    supervisor
}
```

**Step 3: Update Middleware Logic**
```php
// app/Http/Middleware/CheckRole.php
protected function hasAccess($userRole, $requiredRoles) {
    $hierarchy = [
        'admin' => 4,
        'supervisor' => 3,
        'manager' => 2,
        'staff' => 1
    ];
    
    // Implementation...
}
```

**Step 4: Update Seeder**
```php
// database/seeders/WarehouseStaffSeeder.php
WarehouseStaff::create([
    'username' => 'supervisor_test',
    'role' => 'supervisor',
    // ... other fields
]);
```

---

### Task 3: Menambah GraphQL Mutation Baru

**Contoh: Mutation untuk Transfer Stock antar Warehouse**

**Step 1: Define Schema**
```graphql
# graphql/schema.graphql
type Mutation {
    transferStock(input: TransferStockInput!): TransferStockResult!
        @field(resolver: "App\\GraphQL\\Mutations\\TransferStock")
        @middleware(checks: ["auth:api", "role:manager,admin"])
}

input TransferStockInput {
    productCode: String!
    quantity: Int!
    fromWarehouse: String!
    toWarehouse: String!
    note: String
}

type TransferStockResult {
    success: Boolean!
    message: String!
    transaction: StockTransaction
}
```

**Step 2: Create Mutation Class**
```php
// app/GraphQL/Mutations/TransferStock.php
<?php

namespace App\GraphQL\Mutations;

use App\Models\Inventory;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class TransferStock
{
    public function __invoke($rootValue, array $args, $context, $resolveInfo)
    {
        $input = $args['input'];
        $user = auth()->user();
        
        return DB::transaction(function () use ($input, $user) {
            // Validate source warehouse has enough stock
            $inventory = Inventory::where('product_code', $input['productCode'])
                ->where('warehouse_code', $input['fromWarehouse'])
                ->lockForUpdate()
                ->firstOrFail();
                
            if ($inventory->current_stock < $input['quantity']) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock in source warehouse',
                    'transaction' => null
                ];
            }
            
            // Decrement from source
            $inventory->decrement('current_stock', $input['quantity']);
            
            // Increment to destination
            $destInventory = Inventory::where('product_code', $input['productCode'])
                ->where('warehouse_code', $input['toWarehouse'])
                ->lockForUpdate()
                ->firstOrFail();
                
            $destInventory->increment('current_stock', $input['quantity']);
            
            // Record transaction
            $transaction = StockTransaction::create([
                'product_code' => $input['productCode'],
                'quantity' => $input['quantity'],
                'action' => 'TRANSFER',
                'note' => $input['note'] ?? "Transfer from {$input['fromWarehouse']} to {$input['toWarehouse']}",
                'staff_id' => $user->id,
                'stock_before' => $inventory->current_stock + $input['quantity'],
                'stock_after' => $inventory->current_stock,
            ]);
            
            return [
                'success' => true,
                'message' => 'Stock transferred successfully',
                'transaction' => $transaction
            ];
        });
    }
}
```

**Step 3: Test**
```graphql
mutation {
  transferStock(input: {
    productCode: "ELECT-001"
    quantity: 10
    fromWarehouse: "WH-JAKARTA"
    toWarehouse: "WH-BANDUNG"
    note: "Rebalancing inventory"
  }) {
    success
    message
    transaction {
      id
      quantity
      action
      note
    }
  }
}
```

---

### Task 4: Debugging JWT Issues

**Problem: Token Invalid di Shipping Service**

**Step 1: Verify Shared Secret**
```bash
# Stock Service
docker-compose exec stock-service php artisan tinker
>>> env('JWT_SECRET')

# Shipping Service
docker-compose exec shipping-service php artisan tinker
>>> env('JWT_SECRET')

# HARUS SAMA!
```

**Step 2: Decode Token Manual**
```php
// Di Stock Service tinker
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = "eyJ0eXAiOiJKV1Qi...";
$secret = env('JWT_SECRET');

try {
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    print_r($decoded);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Step 3: Check Token Expiration**
```php
$decoded = JWT::decode($token, new Key($secret, 'HS256'));
echo "Expires at: " . date('Y-m-d H:i:s', $decoded->exp);
echo "\nCurrent time: " . date('Y-m-d H:i:s');
```

**Step 4: Verify Token Not Blacklisted**
```bash
# Check cache
docker-compose exec stock-service php artisan cache:get "blacklisted_token:{$jti}"
# Should return null jika tidak di-blacklist
```

**Step 5: Test Login Fresh**
```graphql
mutation {
  login(username: "admin", password: "admin123") {
    token
    expiresIn
  }
}
```

---

### Task 5: Performance Optimization

**Problem: Slow GraphQL Query untuk Large Inventory**

**Step 1: Add Database Index**
```php
Schema::table('inventory', function (Blueprint $table) {
    $table->index('product_code');
    $table->index('current_stock');
    $table->index(['current_stock', 'min_stock_level']); // Compound index
});
```

**Step 2: Eager Loading in Resolver**
```php
// BEFORE (N+1 problem)
public function __invoke($rootValue, array $args) {
    return Inventory::all(); // Relationships loaded lazily
}

// AFTER
public function __invoke($rootValue, array $args) {
    return Inventory::with(['alerts', 'transactions.staff'])
        ->get();
}
```

**Step 3: Implement Pagination**
```graphql
type Query {
    inventoryList(
        page: Int = 1
        limit: Int = 20
        search: String
        lowStockOnly: Boolean
    ): InventoryPagination!
}

type InventoryPagination {
    data: [Inventory!]!
    paginatorInfo: PaginatorInfo!
}
```

**Step 4: Add Caching untuk Read-Heavy Queries**
```php
use Illuminate\Support\Facades\Cache;

public function __invoke($rootValue, array $args) {
    $cacheKey = 'inventory_list_' . json_encode($args);
    
    return Cache::remember($cacheKey, 300, function () use ($args) {
        return Inventory::filter($args)->get();
    });
}

// Invalidate cache saat ada update
// app/GraphQL/Mutations/UpdateStock.php
Cache::forget("inventory_list_*"); // or use tags
```

---

## 🐛 COMMON BUGS & SOLUTIONS

### Bug 1: "SQLSTATE[HY000] [2002] Connection refused"

**Cause**: Database belum ready saat Laravel boot

**Solution**:
```yaml
# docker-compose.yml
stock-service:
  depends_on:
    stock-db:
      condition: service_healthy
  
stock-db:
  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
    interval: 10s
    timeout: 5s
    retries: 5
```

---

### Bug 2: "Field 'lowStockAlerts' not found"

**Cause**: Schema cache outdated

**Solution**:
```bash
docker-compose exec stock-service php artisan lighthouse:clear-cache
docker-compose exec stock-service php artisan config:clear
docker-compose exec stock-service php artisan cache:clear
```

---

### Bug 3: "Unauthenticated" meskipun token valid

**Cause**: Middleware order salah

**Solution**:
```graphql
# graphql/schema.graphql
type Query {
    me: WarehouseStaff!
        @field(resolver: "App\\GraphQL\\Queries\\Me")
        @middleware(checks: ["auth:api"])  # Harus ada auth:api
}
```

```php
// config/lighthouse.php
'middleware' => [
    \App\Http\Middleware\AuthenticateJWT::class,  // HARUS PERTAMA
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

---

### Bug 4: Stock Inconsistency (Race Condition)

**Cause**: Concurrent stock updates tanpa locking

**Solution**:
```php
// WRONG
$inventory = Inventory::find($id);
$inventory->current_stock -= $quantity;
$inventory->save();

// CORRECT
DB::transaction(function () use ($id, $quantity) {
    $inventory = Inventory::where('id', $id)
        ->lockForUpdate()  // Pessimistic lock
        ->firstOrFail();
    
    if ($inventory->current_stock < $quantity) {
        throw new \Exception('Insufficient stock');
    }
    
    $inventory->decrement('current_stock', $quantity);
});
```

---

## 🧪 TESTING WORKFLOWS

### Integration Test dengan Shipping Service

**Scenario**: Shipping staff login → reserve stock → confirm shipment

**Step 1: Login di Stock Service**
```graphql
# http://localhost:8003/graphql
mutation {
  login(username: "staff_ship", password: "staff123") {
    token
    user {
      username
      department
    }
  }
}
```

**Step 2: Gunakan Token di Shipping Service**
```graphql
# http://localhost:8004/graphql
# Headers: Authorization: Bearer <token dari step 1>

mutation {
  createShipment(input: {
    orderId: "ORD-001"
    productCode: "ELECT-001"
    quantity: 5
  }) {
    id
    status
  }
}
```

**Step 3: Verify Stock Decreased**
```graphql
# http://localhost:8003/graphql
query {
  inventory(productCode: "ELECT-001") {
    currentStock
    transactions(limit: 5) {
      action
      quantity
      note
      staff { username }
    }
  }
}
```

---

### Load Testing

**Tool**: Apache Bench atau Artillery

```bash
# Test concurrent stock checks
ab -n 1000 -c 10 -T 'application/json' \
  -p bulk-check.json \
  http://localhost:8003/graphql
```

```json
// bulk-check.json
{
  "query": "query { bulkCheckStock(items: [{productCode: \"ELECT-001\", quantity: 1}]) { allAvailable } }"
}
```

---

## 📚 REFERENCE DOCUMENTS

### Baca File Ini Saat:

1. **Setup Awal** → `QUICKSTART.md`
2. **Memahami Fitur** → `DOCUMENTATION.md`
3. **Integrasi dengan Service Lain** → `INTEGRATION.md`
4. **Testing GraphQL** → `docs/queries/examples.graphql`
5. **Troubleshooting VSCode** → `TROUBLESHOOTING-VSCODE.md`

---

## 💡 BEST PRACTICES

### 1. Security
```yaml
DO:
  - Selalu validate input (productCode, quantity, dll)
  - Hash password dengan bcrypt (cost factor >= 10)
  - Expire JWT dalam 30 menit atau kurang
  - Blacklist token saat logout
  - Use prepared statements (Eloquent ORM handles this)
  - Sanitize error messages untuk production

DON'T:
  - Jangan hardcode credentials
  - Jangan expose stack trace ke client
  - Jangan allow mass assignment tanpa $fillable
  - Jangan commit .env file
```

### 2. Database
```yaml
DO:
  - Use transactions untuk multi-table operations
  - Lock rows untuk concurrent stock updates
  - Index foreign keys dan frequently queried columns
  - Soft delete untuk audit trail
  - Log semua stock changes di StockTransaction

DON'T:
  - Jangan N+1 query (use eager loading)
  - Jangan select * (specify columns)
  - Jangan update tanpa WHERE clause
```

### 3. GraphQL
```yaml
DO:
  - Define explicit return types
  - Use middleware untuk auth checks
  - Implement pagination untuk large datasets
  - Provide meaningful error messages
  - Document schema dengan descriptions

DON'T:
  - Jangan expose internal errors
  - Jangan allow unlimited depth/complexity
  - Jangan return sensitive data tanpa auth
```

---

## 🚀 DEPLOYMENT CHECKLIST

### Pre-Production
- [ ] Run migrations di production DB
- [ ] Seed minimal data (admin user)
- [ ] Set APP_ENV=production di .env
- [ ] Set APP_DEBUG=false
- [ ] Generate strong JWT_SECRET (min 32 chars)
- [ ] Configure proper CORS origins
- [ ] Set up database backups
- [ ] Configure logging ke external service (Sentry, Papertrail)

### Production
- [ ] Use HTTPS untuk external access
- [ ] Rate limiting untuk GraphQL endpoint
- [ ] Monitor memory & CPU usage
- [ ] Set up alerts untuk critical errors
- [ ] Implement log rotation
- [ ] Regular security audits

---

## 📞 SUPPORT & ESCALATION

### Error Priority

**P0 (Critical - Fix Immediately)**
- Authentication down (staff tidak bisa login)
- Database connection lost
- Stock data corruption

**P1 (High - Fix dalam 4 jam)**
- Slow response time (>5s untuk query sederhana)
- JWT token issues
- Integration dengan Shipping Service gagal

**P2 (Medium - Fix dalam 24 jam)**
- Non-critical GraphQL errors
- Alert not generating
- Minor UI issues di GraphQL playground

**P3 (Low - Fix dalam 1 minggu)**
- Documentation updates
- Feature enhancements
- Non-blocking bugs

---

## 🎓 LEARNING PATH

### Untuk Developer Baru

**Week 1: Fundamentals**
- Setup local environment (Docker)
- Pahami GraphQL basics
- Run semua contoh query di `examples.graphql`
- Baca DOCUMENTATION.md end-to-end

**Week 2: Development**
- Tambah 1 field baru di Inventory
- Buat 1 mutation sederhana
- Fix 1 bug dari backlog
- Write unit test untuk mutation baru

**Week 3: Integration**
- Setup Shipping Service
- Test JWT flow end-to-end
- Implement 1 integration test
- Debug 1 integration issue

**Week 4: Advanced**
- Performance optimization (caching, indexing)
- Implement pagination
- Add monitoring/logging
- Code review untuk junior developer

---

## 🔮 FUTURE ENHANCEMENTS

### Roadmap Ideas
1. **Multi-Warehouse Support**
   - Track stock per warehouse location
   - Transfer stock antar warehouse
   - Location-based stock reservation

2. **Batch Operations**
   - Bulk import inventory via CSV
   - Batch stock adjustment
   - Mass alert resolution

3. **Advanced Reporting**
   - Stock movement analytics
   - Staff performance metrics
   - Low stock prediction (ML)

4. **Real-time Updates**
   - WebSocket untuk live stock updates
   - GraphQL subscriptions
   - Push notifications untuk critical alerts

5. **Audit & Compliance**
   - Export audit logs
   - Compliance reports
   - Data retention policies

---

## ✅ QUICK COMMAND REFERENCE

```bash
# Container Management
docker-compose up -d stock-service
docker-compose logs -f stock-service
docker-compose exec stock-service bash

# Laravel Commands
php artisan migrate
php artisan db:seed
php artisan tinker
php artisan cache:clear
php artisan config:clear
php artisan lighthouse:clear-cache

# Database
php artisan migrate:fresh --seed  # WARNING: Deletes all data
php artisan migrate:rollback
php artisan db:show

# Testing
php artisan test
php artisan test --filter=AuthenticationTest

# Code Quality
composer install
composer dump-autoload
./vendor/bin/phpstan analyse

# Debugging
php artisan route:list
php artisan lighthouse:print-schema
tail -f storage/logs/laravel.log
```

---

## 📝 GLOSSARY

| Term | Definition |
|------|------------|
| **JWT** | JSON Web Token - Self-contained token untuk autentikasi |
| **Lighthouse** | GraphQL server untuk Laravel |
| **Resolver** | Function yang menghandle GraphQL query/mutation |
| **Middleware** | Layer untuk intercept request sebelum masuk resolver |
| **Eager Loading** | Load relasi sekaligus untuk hindari N+1 |
| **Pessimistic Lock** | Database-level lock untuk prevent race condition |
| **Seeder** | Class untuk populate initial data |
| **Migration** | Version control untuk database schema |

---

## 🎯 CONCLUSION

Stock Service adalah **critical service** dalam arsitektur microservices ini karena:
1. **Auth Provider** → Semua staff gudang login disini
2. **Inventory Provider** → Single source of truth untuk data stok
3. **Transaction Logger** → Audit trail untuk compliance

**Key Success Factors:**
- ✅ Maintain data integrity (transactions, locking)
- ✅ Secure authentication (JWT, role-based access)
- ✅ Reliable integration (shared secrets, API keys)
- ✅ Complete audit trail (every stock change logged)

**When in doubt:**
1. Check DOCUMENTATION.md untuk business logic
2. Check INTEGRATION.md untuk cross-service issues
3. Check examples.graphql untuk query reference
4. Run `php artisan tinker` untuk quick debugging
5. Check logs di `storage/logs/laravel.log`

**Happy Coding! 🚀**
