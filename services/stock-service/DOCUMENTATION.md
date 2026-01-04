# 📦 Stock Service - Complete Documentation

> **Auth Provider & Inventory Management Microservice**  
> Port: **8003** | Database: **stock_db** (Port 3308)  
> GraphQL Endpoint: `http://localhost:8003/graphql`

---

## 🎯 Deskripsi Proyek

Stock Service adalah **single source of truth** untuk:
1. **Authentication & Authorization** - JWT-based auth untuk semua staff gudang
2. **Inventory Management** - Kelola data stok barang elektronik
3. **Stock Monitoring** - Real-time alerts untuk low/out of stock
4. **Transaction Audit** - Complete audit trail untuk semua perubahan stok
5. **Inter-Service Integration** - Provider untuk Shipping Service (internal) dan Product Service (eksternal)

---

## 🏗️ Arsitektur Sistem

### Role dalam Microservices
- **Auth Provider**: Menerbitkan JWT token untuk autentikasi staff gudang
- **Inventory Provider**: Menyediakan data stok untuk Shipping Service dan Product Service Toko
- **Transaction Logger**: Mencatat semua perubahan stok untuk compliance & audit

### Integrasi
1. **Internal (Shipping Service)**: Komunikasi via Docker network `http://stock-service:8003/graphql`
2. **External (Product Service Toko)**: Komunikasi via API Key authentication

---

## 🔑 Fitur Utama

### 1. Authentication Module
- ✅ Login dengan username/password → JWT token (HS256)
- ✅ Token expiration: 30 menit
- ✅ Token claims: `sub`, `username`, `role`, `department`, `exp`, `jti`
- ✅ Logout dengan token blacklisting
- ✅ Shared JWT secret dengan Shipping Service

### 2. Staff Management
- ✅ CRUD lengkap untuk warehouse staff
- ✅ Role-based access: `admin`, `manager`, `staff`
- ✅ Department: `inventory`, `shipping`, `both`
- ✅ Password hashing dengan bcrypt (cost factor 10)

### 3. Inventory Management
- ✅ Master data barang dengan min/max stock levels
- ✅ Real-time stock checking (single & bulk)
- ✅ Stock transactions: `INCREMENT`, `DECREMENT`, `SET`, `RESERVE`, `RELEASE`
- ✅ Automatic alerts untuk low stock / out of stock

### 4. Stock Monitoring & Alerts
- ✅ Auto-generate alerts saat stok < minimum atau habis
- ✅ Alert resolution tracking
- ✅ Complete transaction history dengan staff attribution

---

## 📊 Database Schema

### Tabel: `warehouse_staff`
```sql
id              BIGINT PRIMARY KEY
username        VARCHAR(255) UNIQUE
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255)  -- bcrypt
role            ENUM('admin', 'manager', 'staff')
department      ENUM('inventory', 'shipping', 'both')
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Tabel: `inventory`
```sql
id                BIGINT PRIMARY KEY
product_code      VARCHAR(255) UNIQUE
product_name      VARCHAR(255)
current_stock     INT
min_stock_level   INT
max_stock_level   INT
created_at        TIMESTAMP
updated_at        TIMESTAMP
```

### Tabel: `stock_transactions`
```sql
id              BIGINT PRIMARY KEY
product_code    VARCHAR(255) FK → inventory.product_code
quantity        INT
action          ENUM('INCREMENT', 'DECREMENT', 'SET', 'RESERVE', 'RELEASE')
note            TEXT NULL
staff_id        BIGINT NULL FK → warehouse_staff.id
stock_before    INT
stock_after     INT
created_at      TIMESTAMP
```

### Tabel: `stock_alerts`
```sql
id              BIGINT PRIMARY KEY
product_code    VARCHAR(255) FK → inventory.product_code
alert_type      ENUM('low', 'out')
current_stock   INT
resolved        BOOLEAN DEFAULT FALSE
resolved_at     TIMESTAMP NULL
resolved_by     BIGINT NULL FK → warehouse_staff.id
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Tabel: `jwt_blacklist`
```sql
id              BIGINT PRIMARY KEY
token           TEXT
jti             VARCHAR(255) UNIQUE
expires_at      TIMESTAMP
blacklisted_at  TIMESTAMP
```

---

## 🚀 Quick Start

### 1. Setup Environment
```bash
cd services/stock-service
cp .env.example .env
```

**Update .env critical settings:**
```env
DB_HOST=stock-db
DB_DATABASE=stock_db
DB_USERNAME=stock_user
DB_PASSWORD=stock_password

JWT_SECRET=shared_secret_stock_shipping_2024
JWT_EXPIRATION=1800

EXTERNAL_API_KEY=stock_api_key_external_2024
```

### 2. Install Dependencies
```bash
composer install
php artisan key:generate
```

### 3. Run dengan Docker
```bash
# Dari root project
docker-compose up -d stock-service

# Check logs
docker-compose logs -f stock-service

# Verify database
docker exec -it stock-db mysql -u stock_user -p stock_db
```

### 4. Access GraphQL Playground
Browser: **http://localhost:8003/graphql**

---

## 🔐 Authentication Examples

### 1. Login - Mendapatkan JWT Token
```graphql
mutation Login {
  login(username: "admin", password: "admin123") {
    token
    expiresIn
    user {
      id
      username
      name
      email
      role
      department
    }
  }
}
```

**Response:**
```json
{
  "data": {
    "login": {
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJTdG9jayBTZXJ2aWNlIiwic3ViIjoiMSIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoiYWRtaW4iLCJkZXBhcnRtZW50IjoiYm90aCIsImlhdCI6MTczNTY4OTYwMCwiZXhwIjoxNzM1NjkxNDAwLCJqdGkiOiJhMWIyYzNkNGU1ZjYifQ.signature",
      "expiresIn": 1800,
      "user": {
        "id": "1",
        "username": "admin",
        "name": "Administrator",
        "email": "admin@warehouse.com",
        "role": "admin",
        "department": "both"
      }
    }
  }
}
```

### 2. Get Current User (me)
```graphql
query Me {
  me {
    id
    username
    name
    email
    role
    department
    createdAt
  }
}
```

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 3. Logout (Blacklist Token)
```graphql
mutation Logout {
  logout
}
```

**Headers:**
```
Authorization: Bearer <your-token>
```

**Response:**
```json
{
  "data": {
    "logout": true
  }
}
```

---

## 📦 Inventory Operations

### 1. Check Stock (Single Product)
**Use Case:** Shipping Service butuh validasi stok sebelum proses order

```graphql
query CheckStock {
  checkStock(productCode: "ELECT-001", quantity: 5) {
    productCode
    productName
    available
    currentStock
    requestedQuantity
    message
  }
}
```

**Response:**
```json
{
  "data": {
    "checkStock": {
      "productCode": "ELECT-001",
      "productName": "Laptop ASUS ROG Strix G15",
      "available": true,
      "currentStock": 50,
      "requestedQuantity": 5,
      "message": "Stock available"
    }
  }
}
```

### 2. Bulk Check Stock
**Use Case:** Product Service Toko butuh check banyak produk sekaligus

```graphql
query BulkCheck {
  bulkCheckStock(items: [
    { productCode: "ELECT-001", quantity: 5 },
    { productCode: "ELECT-002", quantity: 10 },
    { productCode: "ELECT-010", quantity: 1 }
  ]) {
    allAvailable
    results {
      productCode
      productName
      available
      currentStock
      requestedQuantity
      message
    }
  }
}
```

**Response:**
```json
{
  "data": {
    "bulkCheckStock": {
      "allAvailable": false,
      "results": [
        {
          "productCode": "ELECT-001",
          "available": true,
          "currentStock": 50,
          "message": "Stock available"
        },
        {
          "productCode": "ELECT-010",
          "available": false,
          "currentStock": 0,
          "message": "Product out of stock"
        }
      ]
    }
  }
}
```

### 3. List All Inventory
```graphql
query ListInventory {
  inventoryList(lowStockOnly: false, search: "Laptop") {
    id
    productCode
    productName
    currentStock
    minStockLevel
    maxStockLevel
    createdAt
  }
}
```

### 4. Get Low Stock Items Only
```graphql
query LowStockItems {
  inventoryList(lowStockOnly: true) {
    productCode
    productName
    currentStock
    minStockLevel
  }
}
```

---

## 🔄 Stock Management Operations

### 1. Update Stock - INCREMENT
```graphql
mutation AddStock {
  updateStock(input: {
    productCode: "ELECT-001"
    quantity: 50
    action: INCREMENT
    note: "Restocking from supplier XYZ"
  }) {
    productCode
    productName
    currentStock
    minStockLevel
    maxStockLevel
  }
}
```

### 2. Update Stock - DECREMENT
```graphql
mutation ReduceStock {
  updateStock(input: {
    productCode: "ELECT-001"
    quantity: 10
    action: DECREMENT
    note: "Manual adjustment - damaged items"
  }) {
    productCode
    currentStock
  }
}
```

### 3. Reserve Stock (untuk Order)
**Use Case:** Shipping Service reserve stok saat order dibuat

```graphql
mutation ReserveStock {
  reserveStock(input: {
    productCode: "ELECT-001"
    quantity: 5
    orderId: "ORD-2024-12345"
  }) {
    productCode
    productName
    available
    currentStock
    requestedQuantity
    message
  }
}
```

### 4. Release Stock (Cancel Order)
**Use Case:** Shipping Service release stok saat order dibatalkan

```graphql
mutation ReleaseStock {
  releaseStock(
    productCode: "ELECT-001"
    quantity: 5
    orderId: "ORD-2024-12345"
  ) {
    productCode
    productName
    currentStock
  }
}
```

### 5. Adjust Stock (Admin/Manager Only)
```graphql
mutation AdjustStock {
  adjustStock(
    productCode: "ELECT-001"
    newStock: 100
    note: "Stock opname correction"
  ) {
    productCode
    productName
    currentStock
  }
}
```

---

## 👥 Staff Management

### 1. Register New Staff (Admin Only)
```graphql
mutation RegisterStaff {
  registerStaff(input: {
    username: "staff_new"
    name: "New Staff Member"
    email: "newstaff@warehouse.com"
    password: "secure123"
    role: staff
    department: inventory
  }) {
    id
    username
    name
    email
    role
    department
  }
}
```

### 2. Update Staff Profile
```graphql
mutation UpdateProfile {
  updateStaff(id: 2, input: {
    name: "Updated Name"
    email: "updated@warehouse.com"
  }) {
    id
    name
    email
    updatedAt
  }
}
```

### 3. List All Staff (Admin/Manager Only)
```graphql
query AllStaff {
  staffList {
    id
    username
    name
    email
    role
    department
    createdAt
  }
}
```

### 4. Filter Staff by Role
```graphql
query ManagersOnly {
  staffList(role: manager) {
    id
    name
    role
    department
  }
}
```

### 5. Get Staff by ID (Admin Only)
```graphql
query GetStaff {
  staffById(id: 2) {
    id
    username
    name
    email
    role
    department
  }
}
```

### 6. Delete Staff (Admin Only)
```graphql
mutation DeleteStaff {
  deleteStaff(id: 3)
}
```

---

## 📊 Monitoring & Reporting

### 1. Get Stock Alerts (Unresolved)
```graphql
query ActiveAlerts {
  lowStockAlerts(resolved: false) {
    id
    productCode
    alertType
    currentStock
    createdAt
    inventory {
      productName
      minStockLevel
    }
  }
}
```

**Response:**
```json
{
  "data": {
    "lowStockAlerts": [
      {
        "id": "1",
        "productCode": "ELECT-008",
        "alertType": "low",
        "currentStock": 8,
        "createdAt": "2024-12-31T10:00:00Z",
        "inventory": {
          "productName": "Smartwatch Apple Watch Series 9",
          "minStockLevel": 10
        }
      },
      {
        "id": "2",
        "productCode": "ELECT-010",
        "alertType": "out",
        "currentStock": 0,
        "createdAt": "2024-12-31T10:05:00Z",
        "inventory": {
          "productName": "Webcam Logitech Brio 4K",
          "minStockLevel": 5
        }
      }
    ]
  }
}
```

### 2. Stock Transaction History
```graphql
query TransactionHistory {
  stockTransactions(productCode: "ELECT-001", limit: 10) {
    id
    quantity
    action
    note
    stockBefore
    stockAfter
    createdAt
    staff {
      name
      role
    }
  }
}
```

### 3. Resolve Alert
```graphql
mutation ResolveAlert {
  resolveAlert(alertId: 1) {
    id
    resolved
    resolvedAt
    resolver {
      name
      role
    }
  }
}
```

---

## 🔗 Integrasi dengan Service Lain

### A. Shipping Service (Internal - Docker Network)

**Endpoint:** `http://stock-service:8003/graphql`

**Authentication:** JWT Token (shared secret)

**Cara Integrasi dari Shipping Service:**
```php
// Shipping Service memanggil Stock Service
$client = new \GuzzleHttp\Client();

$response = $client->post('http://stock-service:8003/graphql', [
    'headers' => [
        'Authorization' => 'Bearer ' . $jwtToken,
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'query' => '
            query CheckStock($code: String!, $qty: Int!) {
                checkStock(productCode: $code, quantity: $qty) {
                    available
                    currentStock
                    message
                }
            }
        ',
        'variables' => [
            'code' => 'ELECT-001',
            'qty' => 5
        ]
    ]
]);

$data = json_decode($response->getBody(), true);
```

### B. Product Service Toko (External - API Key)

**Endpoint:** `http://localhost:8003/graphql`

**Authentication:** API Key via header `X-API-Key`

**API Key:** `stock_api_key_external_2024`

**Cara Integrasi:**

**Via cURL:**
```bash
curl -X POST http://localhost:8003/graphql \
  -H "Content-Type: application/json" \
  -H "X-API-Key: stock_api_key_external_2024" \
  -d '{
    "query": "query { checkStock(productCode: \"ELECT-001\", quantity: 1) { available currentStock message } }"
  }'
```

**Via JavaScript (Fetch API):**
```javascript
fetch('http://localhost:8003/graphql', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'stock_api_key_external_2024'
  },
  body: JSON.stringify({
    query: `
      query CheckStock($code: String!, $qty: Int!) {
        checkStock(productCode: $code, quantity: $qty) {
          available
          currentStock
          productName
          message
        }
      }
    `,
    variables: {
      code: 'ELECT-001',
      qty: 5
    }
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

**Via PHP (Guzzle):**
```php
$client = new \GuzzleHttp\Client();

$response = $client->post('http://localhost:8003/graphql', [
    'headers' => [
        'X-API-Key' => 'stock_api_key_external_2024',
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'query' => '
            query BulkCheck($items: [BulkStockCheckInput!]!) {
                bulkCheckStock(items: $items) {
                    allAvailable
                    results {
                        productCode
                        available
                        currentStock
                    }
                }
            }
        ',
        'variables' => [
            'items' => [
                ['productCode' => 'ELECT-001', 'quantity' => 5],
                ['productCode' => 'ELECT-002', 'quantity' => 10]
            ]
        ]
    ]
]);
```

---

## 🔒 Security Implementation

### JWT Token Details
**Algorithm:** HS256 (HMAC with SHA-256)  
**Lifetime:** 1800 seconds (30 minutes)  
**Secret:** Shared dengan Shipping Service

**Token Payload:**
```json
{
  "iss": "Stock Service",
  "sub": "1",
  "username": "admin",
  "role": "admin",
  "department": "both",
  "iat": 1735689600,
  "exp": 1735691400,
  "jti": "a1b2c3d4e5f6a7b8c9d0"
}
```

**Token Blacklisting:**
- Saat logout, token ditambahkan ke tabel `jwt_blacklist`
- Setiap request divalidasi terhadap blacklist
- Expired tokens otomatis dibersihkan (cleanup job)

### Password Security
- **Hashing:** bcrypt
- **Cost Factor:** 10 (default Laravel)
- **Auto-hashing:** Otomatis di Model boot method

### Role-Based Access Control

| Endpoint | Admin | Manager | Staff |
|----------|-------|---------|-------|
| Login/Logout | ✅ | ✅ | ✅ |
| Register Staff | ✅ | ❌ | ❌ |
| List Staff | ✅ | ✅ | ❌ |
| Update Own Profile | ✅ | ✅ | ✅ |
| Update Other Staff | ✅ | ❌ | ❌ |
| Delete Staff | ✅ | ❌ | ❌ |
| Check Stock | ✅ | ✅ | ✅ |
| Update Stock | ✅ | ✅ | ✅ |
| Adjust Stock (SET) | ✅ | ✅ | ❌ |
| View Alerts | ✅ | ✅ | ✅ |
| Resolve Alerts | ✅ | ✅ | ❌ |

---

## 🧪 Testing & Sample Data

### Default Accounts (from Seeder)

```
┌──────────────────┬──────────────┬───────────┬────────────┐
│ Username         │ Password     │ Role      │ Department │
├──────────────────┼──────────────┼───────────┼────────────┤
│ admin            │ admin123     │ admin     │ both       │
│ manager_inv      │ manager123   │ manager   │ inventory  │
│ staff_ship       │ staff123     │ staff     │ shipping   │
│ staff_inv        │ staff123     │ staff     │ inventory  │
└──────────────────┴──────────────┴───────────┴────────────┘
```

### Sample Products (from Seeder)

```
┌─────────────┬──────────────────────────────────┬───────┬─────┬─────┬────────┐
│ Product Code│ Product Name                     │ Stock │ Min │ Max │ Status │
├─────────────┼──────────────────────────────────┼───────┼─────┼─────┼────────┤
│ ELECT-001   │ Laptop ASUS ROG Strix G15        │ 50    │ 10  │ 200 │ OK     │
│ ELECT-002   │ Samsung Galaxy S24 Ultra         │ 75    │ 15  │ 300 │ OK     │
│ ELECT-003   │ Monitor LG UltraGear 27"         │ 30    │ 8   │ 150 │ OK     │
│ ELECT-004   │ Mechanical Keyboard Logitech     │ 100   │ 20  │ 500 │ OK     │
│ ELECT-005   │ Wireless Mouse Logitech MX 3S    │ 120   │ 25  │ 600 │ OK     │
│ ELECT-006   │ Headphones Sony WH-1000XM5       │ 60    │ 12  │ 250 │ OK     │
│ ELECT-007   │ Tablet iPad Pro 12.9" M2         │ 40    │ 10  │ 180 │ OK     │
│ ELECT-008   │ Apple Watch Series 9             │ 8     │ 10  │ 200 │ ⚠️ LOW │
│ ELECT-009   │ External SSD Samsung T7 2TB      │ 90    │ 18  │ 400 │ OK     │
│ ELECT-010   │ Webcam Logitech Brio 4K          │ 0     │ 5   │ 100 │ ❌ OUT │
└─────────────┴──────────────────────────────────┴───────┴─────┴─────┴────────┘
```

### Test Scenarios

#### Scenario 1: Full Flow Stock Management
```graphql
# 1. Login
mutation { login(username: "admin", password: "admin123") { token } }

# 2. Check stock
query { checkStock(productCode: "ELECT-001", quantity: 5) { available } }

# 3. Reserve stock
mutation { 
  reserveStock(input: {
    productCode: "ELECT-001", 
    quantity: 5
  }) { available currentStock } 
}

# 4. View transaction
query { 
  stockTransactions(productCode: "ELECT-001", limit: 1) { 
    action quantity stockBefore stockAfter 
  } 
}
```

#### Scenario 2: Low Stock Alert Flow
```graphql
# 1. View alerts
query { lowStockAlerts(resolved: false) { productCode alertType } }

# 2. Add stock to resolve
mutation { 
  updateStock(input: {
    productCode: "ELECT-008"
    quantity: 20
    action: INCREMENT
  }) { currentStock } 
}

# 3. Resolve alert
mutation { resolveAlert(alertId: 1) { resolved } }
```

---

## 🐛 Troubleshooting

### Problem: Container tidak start
```bash
# Check logs
docker-compose logs stock-service

# Rebuild
docker-compose down
docker-compose build --no-cache stock-service
docker-compose up -d stock-service
```

### Problem: Database connection error
```bash
# Verify database container
docker ps | grep stock-db

# Check database connection from inside container
docker exec -it stock-service php artisan migrate:status

# Reset database
docker-compose down
docker volume rm iae-kurang-tidur_stock-db-data
docker-compose up -d stock-service
```

### Problem: JWT Token Invalid
**Possible Causes:**
1. Token expired (> 30 menit)
2. JWT_SECRET berbeda dengan Shipping Service
3. Token sudah di-blacklist (logout)

**Solution:**
```bash
# Check .env JWT_SECRET
cat services/stock-service/.env | grep JWT_SECRET

# Login ulang untuk token baru
# GraphQL: mutation { login(...) { token } }
```

### Problem: Migration Failed
```bash
# Drop all tables and re-migrate
docker exec -it stock-service php artisan migrate:fresh --seed
```

### Problem: Permission Denied
```bash
# Fix storage permissions
docker exec -it stock-service chmod -R 777 storage bootstrap/cache
```

---

## 📸 Screenshots untuk Laporan

### 1. GraphQL Playground - Login Berhasil
![Login Success](docs/screenshots/01-login-success.png)

### 2. checkStock dari Shipping Service
![Internal Integration](docs/screenshots/02-internal-checkstock.png)

### 3. checkStock dari Product Service (dengan API Key)
![External Integration](docs/screenshots/03-external-apikey.png)

### 4. Database Structure
![Database Schema](docs/screenshots/04-database-structure.png)

### 5. Docker Containers Running
![Docker PS](docs/screenshots/05-docker-ps.png)

### 6. Stock Transaction History
![Transactions](docs/screenshots/06-transaction-history.png)

### 7. Low Stock Alerts
![Alerts](docs/screenshots/07-low-stock-alerts.png)

---

## 📁 Project Structure

```
stock-service/
├── app/
│   ├── GraphQL/
│   │   ├── Mutations/
│   │   │   ├── AuthMutation.php
│   │   │   ├── StaffMutation.php
│   │   │   └── StockMutation.php
│   │   └── Queries/
│   │       ├── AuthQuery.php
│   │       ├── StaffQuery.php
│   │       └── StockQuery.php
│   ├── Http/
│   │   └── Middleware/
│   │       ├── JwtAuthentication.php
│   │       └── ApiKeyAuthentication.php
│   ├── Models/
│   │   ├── WarehouseStaff.php
│   │   ├── Inventory.php
│   │   ├── StockTransaction.php
│   │   ├── StockAlert.php
│   │   └── JwtBlacklist.php
│   └── Services/
│       └── JwtService.php
├── config/
│   ├── app.php (JWT config)
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_warehouse_staff_table.php
│   │   ├── 2024_01_01_000002_create_inventory_table.php
│   │   ├── 2024_01_01_000003_create_stock_transactions_table.php
│   │   ├── 2024_01_01_000004_create_stock_alerts_table.php
│   │   └── 2024_01_01_000005_create_jwt_blacklist_table.php
│   └── seeders/
│       ├── WarehouseStaffSeeder.php
│       ├── InventorySeeder.php
│       └── DatabaseSeeder.php
├── graphql/
│   └── schema.graphql (Complete GraphQL Schema)
├── .env.example
├── Dockerfile
├── composer.json
└── DOCUMENTATION.md (this file)
```

---

## 🎓 Acceptance Criteria Checklist

### ✅ Technical Requirements
- [x] Docker & Docker Compose setup
- [x] PHP 8.2-fpm dengan extensions (pdo, mysqli, bcmath)
- [x] GraphQL API dengan Lighthouse 6.x
- [x] JWT Authentication (HS256, 30 menit)
- [x] Database migrations (5 tabel)
- [x] Database seeders (4 staff + 10 produk)
- [x] Password hashing bcrypt cost 10
- [x] Token blacklist untuk logout

### ✅ Functional Requirements
- [x] Login mutation return JWT
- [x] CRUD Staff Management
- [x] CRUD Inventory dengan min/max levels
- [x] Stock transactions (5 actions)
- [x] Auto-generate alerts low/out stock
- [x] Stock availability check (single & bulk)
- [x] Transaction audit trail
- [x] Role-based access control

### ✅ Integration Requirements
- [x] Internal integration (Shipping Service) via JWT
- [x] External integration (Product Service) via API Key
- [x] Shared JWT secret dengan Shipping Service
- [x] Docker network communication

### ✅ Code Quality
- [x] PSR standards compliance
- [x] Clean code & comments
- [x] Model relationships
- [x] Middleware implementation
- [x] Service layer pattern

### ✅ Documentation
- [x] Comprehensive README
- [x] GraphQL schema documentation
- [x] API examples (queries & mutations)
- [x] Integration guides
- [x] Environment variables explained
- [x] Troubleshooting guide

### ✅ Bukti untuk Laporan
- [x] Screenshots preparation guide
- [x] Test scenarios
- [x] Sample data
- [x] Default accounts documented

---

## 📚 References

### GraphQL
- **Lighthouse PHP:** https://lighthouse-php.com/
- **GraphQL Spec:** https://graphql.org/learn/

### JWT
- **JWT.io:** https://jwt.io/
- **Firebase PHP-JWT:** https://github.com/firebase/php-jwt

### Laravel
- **Laravel Docs:** https://laravel.com/docs/10.x
- **Eloquent ORM:** https://laravel.com/docs/10.x/eloquent

### Docker
- **Docker Compose:** https://docs.docker.com/compose/
- **PHP Docker Images:** https://hub.docker.com/_/php

---

## 👨‍💻 Developer Info

**Developer:** Arthur  
**Team:** Kelompok Gudang  
**Service:** Stock Service  
**Port:** 8003  
**Database Port:** 3308  
**Responsibility:** Authentication Provider & Inventory Management

**Contact:** [arthur@warehouse-team.com]  
**GitHub:** [stock-service-repo]

---

## 📄 License & Academic Integrity

Tugas Besar Integrasi Aplikasi Enterprise - Semester 5

**DISCLAIMER:**  
Proyek ini dibuat untuk keperluan akademis sebagai Tugas Besar mata kuliah Integrasi Aplikasi Enterprise. Semua kode dan dokumentasi adalah hasil kerja sendiri dengan bantuan dokumentasi resmi dan best practices yang tersedia secara publik.

---

**🚀 Happy Coding & Semoga Sukses dengan Tugas Besar! 🚀**

---

**Last Updated:** December 31, 2024  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
