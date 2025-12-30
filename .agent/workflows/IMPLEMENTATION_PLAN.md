---
description: Rencana Implementasi Perbaikan Template Microservices Gudang Elektonik
---

# 🎯 RENCANA IMPLEMENTASI PERBAIKAN TEMPLATE
**Target: Sesuaikan Repository dengan Proposal & ERD Gudang Elektronik**

## 📊 ANALISIS KONDISI SAAT INI vs PROPOSAL

### Status Saat Ini (Repository)
- **Services**: 4 service terpisah
  - `product-service` (Port 8001) → Domain Toko
  - `order-service` (Port 8002) → Domain Toko  
  - `stock-service` (Port 8003) → Domain Gudang
  - `shipping-service` (Port 8004) → Domain Gudang
- **Database**: 4 MySQL containers terpisah ✅ (Sudah sesuai prinsip microservices)
- **Auth**: TIDAK ADA service auth terpisah (baik!)
- **Tabel**:
  - `stock-service`: `users`, `inventory` ✅
  - `shipping-service`: `users`, `warehouse_orders`, `shipments` ✅

### Target Proposal
- **Services**: 2 service utama untuk Gudang
  - **Stock Service**: Auth + Manajemen Stok
  - **Shipping Service**: Order Gudang + Pengiriman
- **Database**: `users` hanya di Stock Service (shipping tidak perlu users)
- **Mekanisme Keamanan**:
  - Stock Service: Menerbitkan JWT (login endpoint)
  - Shipping Service: Verifikasi JWT + API Key untuk eksternal
- **Endpoint Integrasi**: `requestRestock`, `trackOrder` untuk Toko

---

## ✅ BAGIAN YANG SUDAH SESUAI

1. ✅ **Isolasi Database Fisik** - 4 MySQL container terpisah
2. ✅ **Struktur Tabel** - Sudah sesuai ERD (inventory, warehouse_orders, shipments)
3. ✅ **GraphQL Schema** - Struktur sudah mendekati requirement
4. ✅ **Docker Compose** - Konfigurasi sudah baik
5. ✅ **Foreign Keys** - Relasi antar tabel sudah benar

---

## 🔥 PRIORITAS PERBAIKAN (9 FASE)

### FASE 1: PERBAIKAN STRUKTUR DATABASE (PRIORITAS TERTINGGI)
**Target**: Sesuaikan tabel users dan relasi dengan ERD proposal

#### 1.1. Update Migration `users` di Stock Service
**File**: `services/stock-service/database/migrations/2014_10_12_000000_create_users_table.php`

**Yang Perlu Ditambahkan**:
```php
$table->string('username', 50)->unique();
$table->enum('role', ['ADMIN_GUDANG', 'STAFF_GUDANG'])->default('STAFF_GUDANG');
// Kolom password sudah ada
```

**Catatan**: Tabel users sudah ada di stock-service, hanya perlu ditambah kolom `username` dan `role`.

#### 1.2. Hapus Migration `users` dari Shipping Service
**File**: `services/shipping-service/database/migrations/2014_10_12_000000_create_users_table.php`

**Alasan**: Users hanya perlu di Stock Service. Shipping Service akan menggunakan foreign key reference ke database stock_db atau cukup menyimpan `user_id` tanpa foreign key constraint (karena cross-database).

**PENTING**: Karena foreign key cross-database tidak didukung MySQL, lakukan salah satu:
- **Opsi A** (Direkomendasikan): Tetap simpan kolom `user_id` di `warehouse_orders` tapi HAPUS foreign key constraint. Validasi dilakukan di aplikasi layer.
- **Opsi B**: Simpan `user_id` sebagai integer biasa tanpa foreign key sama sekali.

---

### FASE 2: IMPLEMENTASI AUTH DI STOCK SERVICE
**Target**: Stock Service sebagai Auth Provider (JWT Issuer)

#### 2.1. Install Dependencies JWT
```bash
cd services/stock-service
composer require firebase/php-jwt
```

#### 2.2. Buat Auth Controller & Resolver
**File Baru**: `services/stock-service/app/GraphQL/Mutations/AuthMutation.php`

**Endpoint yang Harus Ada**:
- `login(email: String!, password: String!): AuthResponse`
- `register(username: String!, email: String!, password: String!, role: String): User`

**Response JWT**:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "admin_gudang",
    "email": "admin@gudang.com",
    "role": "ADMIN_GUDANG"
  }
}
```

#### 2.3. Update GraphQL Schema Stock Service
**File**: `services/stock-service/graphql/schema.graphql`

**Tambahkan**:
```graphql
type User {
  id: ID!
  username: String!
  email: String!
  role: String!
}

type AuthResponse {
  token: String!
  user: User!
}

type Mutation {
  login(email: String!, password: String!): AuthResponse
  register(input: RegisterInput!): User
  # ... mutation yang sudah ada
}

input RegisterInput {
  username: String!
  email: String!
  password: String!
  role: String
}
```

#### 2.4. Buat JWT Helper
**File Baru**: `services/stock-service/app/Helpers/JwtHelper.php`

**Fungsi**:
- `generateToken($user)` - Create JWT dengan HS256
- `verifyToken($token)` - Decode & validate JWT
- Secret key dari `.env`: `JWT_SECRET=supersecretkey123`

---

### FASE 3: IMPLEMENTASI JWT VERIFICATION DI SHIPPING SERVICE
**Target**: Shipping Service memverifikasi JWT dari Stock Service

#### 3.1. Install Dependencies JWT
```bash
cd services/shipping-service
composer require firebase/php-jwt
```

#### 3.2. Buat Middleware JWT Verification
**File Baru**: `services/shipping-service/app/Http/Middleware/JwtAuthMiddleware.php`

**Fungsi**:
- Extract token dari header `Authorization: Bearer <token>`
- Verify signature menggunakan shared secret (sama dengan Stock Service)
- Inject user info ke request

#### 3.3. Register Middleware
**File**: `services/shipping-service/app/Http/Kernel.php` (Laravel 10)
atau `bootstrap/app.php` (Laravel 11)

#### 3.4. Apply Middleware ke GraphQL Routes
**File**: `services/shipping-service/config/lighthouse.php`

```php
'route' => [
    'middleware' => ['api', 'jwt.auth'],
],
```

---

### FASE 4: IMPLEMENTASI API KEY + HMAC UNTUK EKSTERNAL (TOKO)
**Target**: Endpoint requestRestock aman untuk konsumsi Toko

#### 4.1. Buat Middleware API Key Verification
**File Baru**: `services/shipping-service/app/Http/Middleware/ApiKeyMiddleware.php`

**Mekanisme**:
- Toko mengirim header: `X-API-Key`, `X-Signature`, `X-Timestamp`
- Signature = HMAC-SHA256(API_KEY + TIMESTAMP + REQUEST_BODY, SECRET)
- Validasi timestamp (max 5 menit)

#### 4.2. Conditional Middleware
**File**: `services/shipping-service/graphql/schema.graphql`

**Strategi**:
- Mutation `requestRestock` → Gunakan API Key Middleware
- Query/Mutation internal → Gunakan JWT Middleware

**Implementasi**:
```php
// Tambahkan directive custom atau route terpisah
Route::post('/graphql/public', ...)->middleware('api.key');
Route::post('/graphql', ...)->middleware('jwt.auth');
```

---

### FASE 5: UPDATE GRAPHQL SCHEMA UNTUK INTEGRASI
**Target**: Endpoint jelas untuk konsumsi eksternal

#### 5.1. Shipping Service Schema Enhancement
**File**: `services/shipping-service/graphql/schema.graphql`

**Tambahkan**:
```graphql
# ==============================
# EXTERNAL API (UNTUK TOKO)
# ==============================
input RestockRequestInput {
  storeId: String!
  productCode: String!
  quantity: Int!
}

type RestockResponse {
  success: Boolean!
  orderCode: String
  estimatedDelivery: String
  message: String
}

type Mutation {
  # Endpoint untuk Toko (External)
  requestRestock(input: RestockRequestInput!): RestockResponse
  
  # Endpoint Internal Gudang
  createWarehouseOrder(input: WarehouseOrderInput! @spread): WarehouseOrder @create
  approveWarehouseOrder(id: ID!, status: String = "DITERIMA"): WarehouseOrder @update
  rejectWarehouseOrder(id: ID!, status: String = "DITOLAK"): WarehouseOrder @update
  createShipment(input: ShipmentInput! @spread): Shipment @create
}

type Query {
  # Endpoint untuk Toko (External)
  trackOrder(orderCode: String!): OrderTrackingStatus
  
  # Endpoint Internal Gudang
  trackShipment(shippingCode: String! @eq(key: "shipping_code")): Shipment @find
  warehouseOrders: [WarehouseOrder!]! @all
  warehouseOrder(id: ID! @eq): WarehouseOrder @find
}

type OrderTrackingStatus {
  orderCode: String!
  status: String!
  estimatedDelivery: String
  events: [TrackingEvent!]
}

type TrackingEvent {
  timestamp: String!
  description: String!
}
```

#### 5.2. Buat Resolver untuk External Endpoints
**File Baru**: 
- `services/shipping-service/app/GraphQL/Mutations/RequestRestock.php`
- `services/shipping-service/app/GraphQL/Queries/TrackOrder.php`

**Logika `requestRestock`**:
1. Validasi input
2. Cek stock di Stock Service via GraphQL (HTTP call)
3. Buat record di `warehouse_orders` dengan status `MENUNGGU`
4. Return response dengan `orderCode` dan `estimatedDelivery`

**Logika `trackOrder`**:
1. Query `warehouse_orders` dan `shipments` by `toko_order_code`
2. Return status tracking lengkap

---

### FASE 6: DOKUMENTASI API LENGKAP
**Target**: Developer Toko bisa integrasi dengan mudah

#### 6.1. Buat Dokumentasi GraphQL di README
**File**: Update `README.md`

**Section Baru**:
```markdown
## 📡 API ENDPOINTS UNTUK INTEGRASI TOKO

### Authentication (Stock Service - Port 8003)
#### Login
**Endpoint**: `POST http://localhost:8003/graphql`
**Mutation**:
```graphql
mutation {
  login(email: "admin@gudang.com", password: "password123") {
    token
    user {
      id
      username
      email
      role
    }
  }
}
```

### Shipping Integration (Shipping Service - Port 8004)
#### Request Restock (External - Untuk Toko)
**Endpoint**: `POST http://localhost:8004/graphql/public`
**Headers**:
- `X-API-Key`: `TOKO_API_KEY_123`
- `X-Signature`: `HMAC-SHA256(...)`
- `X-Timestamp`: `2025-12-30T10:00:00Z`

**Mutation**:
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01",
    productCode: "ELEC001",
    quantity: 50
  }) {
    success
    orderCode
    estimatedDelivery
    message
  }
}
```

#### Track Order (External - Untuk Toko)
**Query**:
```graphql
query {
  trackOrder(orderCode: "WH-2025-001") {
    orderCode
    status
    estimatedDelivery
    events {
      timestamp
      description
    }
  }
}
```
```

#### 6.2. Buat File .env.example Lengkap
**File**: Update `services/stock-service/.env.example`

```env
APP_NAME=StockService
APP_ENV=local
APP_KEY=base64:xxx
APP_DEBUG=true
APP_URL=http://localhost:8003

# Database
DB_CONNECTION=mysql
DB_HOST=mysql-stock
DB_PORT=3306
DB_DATABASE=stock_db
DB_USERNAME=root
DB_PASSWORD=root

# JWT Configuration
JWT_SECRET=supersecretkey123
JWT_EXPIRATION=86400

# GraphQL
LIGHTHOUSE_CACHE_ENABLE=false
```

**File**: Update `services/shipping-service/.env.example`

```env
APP_NAME=ShippingService
APP_ENV=local
APP_KEY=base64:xxx
APP_DEBUG=true
APP_URL=http://localhost:8004

# Database
DB_CONNECTION=mysql
DB_HOST=mysql-shipping
DB_PORT=3306
DB_DATABASE=shipping_db
DB_USERNAME=root
DB_PASSWORD=root

# JWT Configuration (Same as Stock Service for verification)
JWT_SECRET=supersecretkey123

# API Key for External Integration
API_SECRET_KEY=shared_secret_with_toko_12345

# Stock Service URL (untuk HTTP call)
STOCK_SERVICE_URL=http://stock-service:8000/graphql

# GraphQL
LIGHTHOUSE_CACHE_ENABLE=false
```

#### 6.3. Buat Postman Collection
**File Baru**: `docs/postman/Gudang-API.postman_collection.json`

**Content**: Export dari Postman dengan:
- Auth endpoints (login, register)
- Stock endpoints (checkStock, increaseStock, decreaseStock)
- Shipping endpoints (requestRestock, trackOrder)
- Warehouse internal endpoints

---

### FASE 7: DATABASE SEEDING
**Target**: 10 produk elektronik + sample users

#### 7.1. Buat Seeder untuk Stock Service
**File**: Update `services/stock-service/database/seeders/DatabaseSeeder.php`

**Data**:
- 2 Users: 1 Admin Gudang, 1 Staff Gudang
- 10 Inventory items (produk elektronik)

**Contoh Data**:
```php
// Users
User::create([
    'username' => 'admin_gudang',
    'email' => 'admin@gudang.com',
    'password' => bcrypt('password123'),
    'role' => 'ADMIN_GUDANG',
]);

// Inventory (10 produk)
Inventory::create([
    'product_code' => 'ELEC001',
    'product_name' => 'Samsung Galaxy S24 Ultra',
    'stock' => 150,
]);
Inventory::create([
    'product_code' => 'ELEC002',
    'product_name' => 'iPhone 15 Pro Max',
    'stock' => 120,
]);
// ... 8 produk lagi
```

#### 7.2. Buat Seeder untuk Shipping Service
**File**: Update `services/shipping-service/database/seeders/DatabaseSeeder.php`

**Data**:
- 5 Warehouse Orders (sample dari Toko)
- 3 Shipments (sample pengiriman)

---

### FASE 8: TESTING & VALIDASI
**Target**: Semua endpoint berfungsi sesuai skenario

#### 8.1. Skenario Testing Manual
**File Baru**: `docs/TESTING_SCENARIOS.md`

**Skenario 1: Login & Get Token**
```
1. Login via Stock Service
2. Simpan JWT token
3. Gunakan token untuk akses Shipping Service
```

**Skenario 2: Cek Stok & Request Restock (External)**
```
1. Toko cek stock via Stock Service (public)
2. Toko request restock via Shipping Service (dengan API Key)
3. Staff Gudang approve warehouse order (dengan JWT)
4. Staff Gudang create shipment (dengan JWT)
5. Toko track order via Shipping Service (dengan API Key)
```

**Skenario 3: Internal Warehouse Flow**
```
1. Admin login
2. Lihat daftar warehouse orders
3. Approve order
4. Create shipment
5. Update shipment status
```

#### 8.2. Buat Automated Test (Optional)
**File**: `tests/Feature/AuthTest.php`, `tests/Feature/RestockFlowTest.php`

---

### FASE 9: FINAL DOCUMENTATION & CLEANUP
**Target**: Repository siap jadi template untuk tugas lain

#### 9.1. Update README.md dengan Architecture Diagram
**Section**:
```markdown
## 🏗️ ARSITEKTUR SISTEM

### Domain Separation
```
┌─────────────────────────────────────────────────────────────┐
│                        TOKO DOMAIN                          │
│  ┌──────────────────┐          ┌──────────────────┐         │
│  │ Product Service  │          │  Order Service   │         │
│  │   (Port 8001)    │          │   (Port 8002)    │         │
│  └──────────────────┘          └──────────────────┘         │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ GraphQL + API Key
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       GUDANG DOMAIN                         │
│  ┌──────────────────┐          ┌──────────────────┐         │
│  │  Stock Service   │◄────────►│ Shipping Service │         │
│  │  (Port 8003)     │   JWT    │   (Port 8004)    │         │
│  │  + Auth Module   │          │                  │         │
│  └──────────────────┘          └──────────────────┘         │
│         │                              │                    │
│         ▼                              ▼                    │
│  ┌─────────────┐              ┌─────────────────┐           │
│  │  stock_db   │              │   shipping_db   │           │
│  │  - users    │              │ - warehouse_orders         │
│  │  - inventory│              │ - shipments     │           │
│  └─────────────┘              └─────────────────┘           │
└─────────────────────────────────────────────────────────────┘
```

### Security Flow
```
┌─────────┐                 ┌──────────────┐                 ┌──────────────┐
│  TOKO   │                 │Stock Service │                 │Shipping Svc  │
└────┬────┘                 └──────┬───────┘                 └──────┬───────┘
     │                             │                                │
     │  1. Login (email, pass)     │                                │
     ├────────────────────────────►│                                │
     │                             │                                │
     │  2. Return JWT Token        │                                │
     │◄────────────────────────────┤                                │
     │                             │                                │
     │  3. Request Restock (API Key + HMAC)                         │
     ├──────────────────────────────────────────────────────────────►
     │                             │                                │
     │                             │  4. Verify API Key             │
     │                             │  5. Check Stock (HTTP GraphQL) │
     │                             │◄───────────────────────────────┤
     │                             │                                │
     │                             │  6. Return Stock Data          │
     │                             ├───────────────────────────────►│
     │                             │                                │
     │  7. Return Restock Response (orderCode, ETA)                 │
     │◄──────────────────────────────────────────────────────────────┤
```
```

#### 9.2. Buat Sequence Diagram untuk Alur Bisnis
**File Baru**: `docs/SEQUENCE_DIAGRAMS.md`

**Diagram**:
- Login Flow
- Request Restock Flow (External)
- Approve & Ship Flow (Internal)
- Track Order Flow

#### 9.3. Cleanup & Validation Checklist
**File Baru**: `docs/DEPLOYMENT_CHECKLIST.md`

```markdown
# ✅ Deployment Checklist

## Pre-Deployment
- [ ] All migrations tested
- [ ] Seeders run successfully
- [ ] All .env.example files updated
- [ ] JWT secrets configured
- [ ] API keys documented

## Testing
- [ ] Login endpoint works
- [ ] JWT verification works
- [ ] requestRestock (external) works with API Key
- [ ] trackOrder works
- [ ] Internal warehouse flow works
- [ ] Cross-service communication works

## Documentation
- [ ] README updated with all endpoints
- [ ] .env.example files complete
- [ ] Postman collection exported
- [ ] Architecture diagram added
- [ ] Integration guide for Toko written

## Security
- [ ] JWT secret is strong
- [ ] API Key mechanism implemented
- [ ] HMAC validation works
- [ ] No hardcoded secrets in repository

## Final Push
- [ ] All code committed
- [ ] .gitignore properly configured
- [ ] LICENSE file exists
- [ ] Repository tagged with version
```

---

## 📋 SUMMARY PERBAIKAN

### Yang Tidak Perlu Diubah
1. ✅ Struktur 4 services (Product, Order, Stock, Shipping)
2. ✅ Docker Compose configuration
3. ✅ Database isolation (4 MySQL containers)
4. ✅ Basic GraphQL schemas
5. ✅ Migration structures (inventory, warehouse_orders, shipments)

### Yang Perlu Ditambahkan
1. 🔧 Auth module di Stock Service (login, register, JWT)
2. 🔧 JWT verification middleware di Shipping Service
3. 🔧 API Key + HMAC middleware untuk external endpoints
4. 🔧 GraphQL resolvers untuk `requestRestock` dan `trackOrder`
5. 🔧 HTTP client di Shipping Service untuk call Stock Service
6. 🔧 Database seeders (users + inventory)
7. 🔧 Dokumentasi lengkap (README, Postman, diagrams)
8. 🔧 .env.example files yang lengkap
9. 🔧 Testing scenarios documentation

### Yang Perlu Dimodifikasi
1. 🔄 Migration `users` di Stock Service (tambah username, role)
2. 🔄 Migration `users` di Shipping Service (HAPUS foreign key constraint)
3. 🔄 GraphQL schema Stock Service (tambah Auth types)
4. 🔄 GraphQL schema Shipping Service (pisah internal vs external)
5. 🔄 README.md (tambah architecture, endpoints, integration guide)

---

## 🚀 ESTIMASI WAKTU

| Fase | Estimasi | Prioritas |
|------|----------|-----------|
| Fase 1: Database | 1 jam | CRITICAL |
| Fase 2: Auth Stock | 2 jam | CRITICAL |
| Fase 3: JWT Shipping | 1 jam | CRITICAL |
| Fase 4: API Key | 1.5 jam | HIGH |
| Fase 5: GraphQL Update | 2 jam | HIGH |
| Fase 6: Dokumentasi | 2 jam | MEDIUM |
| Fase 7: Seeding | 1 jam | MEDIUM |
| Fase 8: Testing | 2 jam | HIGH |
| Fase 9: Final Docs | 1.5 jam | MEDIUM |
| **TOTAL** | **14 jam** | - |

---

## 📌 CATATAN PENTING

1. **Foreign Key Cross-Database**
   - MySQL tidak support foreign key constraint antar database
   - Solusi: Validasi relasi di application layer, bukan database layer
   - Tetap simpan `user_id` di `warehouse_orders`, tapi tanpa CONSTRAINT

2. **JWT Secret**
   - HARUS SAMA antara Stock Service (issuer) dan Shipping Service (verifier)
   - Simpan di `.env` kedua service: `JWT_SECRET=supersecretkey123`

3. **API Key untuk Toko**
   - Setiap Toko punya API Key unik
   - Signature dihitung dengan HMAC untuk prevent tampering
   - Timestamp untuk prevent replay attack

4. **Endpoint Separation**
   - Internal endpoints: Perlu JWT (untuk staff gudang)
   - External endpoints: Perlu API Key (untuk Toko)
   - Bisa pakai route terpisah: `/graphql` (JWT) vs `/graphql/public` (API Key)

5. **Testing**
   - Manual testing WAJIB untuk semua skenario
   - Automated testing OPTIONAL (tapi recommended)

---

## 🎯 DELIVERABLES AKHIR

1. ✅ Repository GitHub dengan struktur lengkap
2. ✅ README.md dengan architecture diagram & integration guide
3. ✅ File .env.example lengkap untuk semua services
4. ✅ Database seeders dengan 10 produk elektronik
5. ✅ Postman collection untuk testing
6. ✅ Documentation folder dengan:
   - Testing scenarios
   - Sequence diagrams
   - Deployment checklist
7. ✅ Working endpoints:
   - Auth (login, register)
   - Stock management (check, increase, decrease)
   - Restock request (external)
   - Order tracking (external)
   - Warehouse order management (internal)
   - Shipment management (internal)

---

**STATUS**: 📝 READY TO IMPLEMENT
**LAST UPDATED**: 2025-12-30
