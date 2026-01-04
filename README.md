# Backend Monorepo – Microservices Gudang Elektronik

> 📌 **Tim**: IAE - Kurang Tidur  
> 📅 **Repository**: https://github.com/aturrr62/IAE-kurang-tidur

---

## 👥 PEMBAGIAN TUGAS TIM

### 🔵 **DOMAIN TOKO** (Kelompok Toko)
**Tanggung Jawab**: Sistem manajemen toko & order pelanggan

| Service | Port | Tanggung Jawab | Status |
|---------|------|----------------|--------|
| **Product Service** | 8001 | Manajemen produk toko | ✅ READY |
| **Order Service** | 8002 | Manajemen order pelanggan | ✅ READY |

**Yang Perlu Dikerjakan oleh Tim Toko:**
- [ ] Implementasi frontend untuk tampilan produk
- [ ] Implementasi frontend untuk order pelanggan
- [ ] Integrasi dengan Gudang Service untuk cek stock
- [ ] Implementasi `requestRestock` ketika stock toko menipis
- [ ] Testing API dengan Postman/GraphQL Playground

**Endpoint Yang Harus Digunakan:**
```graphql
# Cek stock dari Gudang
query {
  checkStock(productCode: "ELEC001") {
    stock
  }
}

# Request restock dari Gudang (gunakan API Key)
mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC001"
    quantity: 50
    storeAddress: "Jl. Toko No. 1"
  }) {
    success
    orderCode
    estimatedDelivery
  }
}
```

---

### 🟢 **DOMAIN GUDANG** (Kelompok Gudang - KITA)
**Tanggung Jawab**: Sistem manajemen gudang & pengiriman

| Service | Port | Tanggung Jawab | Status |
|---------|------|----------------|--------|
| **Stock Service** | 8003 | Auth + Manajemen inventory | ✅ COMPLETE |
| **Shipping Service** | 8004 | Order gudang + Pengiriman | ✅ COMPLETE |

**✅ Yang SUDAH SELESAI:**
- ✅ Database migrations (users, inventory, warehouse_orders, shipments)
- ✅ JWT Authentication (login, register)
- ✅ API Key + HMAC untuk endpoint eksternal
- ✅ GraphQL schemas lengkap (auth, stock, shipping)
- ✅ Seeder (3 users, 10 produk elektronik)
- ✅ `requestRestock` endpoint untuk Toko
- ✅ `trackOrder` endpoint untuk Toko
- ✅ Internal endpoints (approve order, create shipment, dll)



---

## 🔗 INTEGRASI LINTAS KELOMPOK

### **Toko → Gudang (WAJIB)**

**1. Request Restock** (Toko ke Gudang)
```graphql
# Endpoint: http://localhost:8004/graphql
# Auth: API Key + HMAC Signature

mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC001"
    quantity: 50
    storeAddress: "Alamat Toko"
  }) {
    success
    orderCode
    estimatedDelivery
    message
  }
}
```

**Headers Required:**
```
X-API-Key: TOKO_API_KEY_001
X-Signature: <HMAC-SHA256 signature>
X-Timestamp: 2025-12-31T10:00:00Z
```

**2. Track Order** (Toko cek status pengiriman)
```graphql
query {
  trackOrder(orderCode: "WH-20251231-ABC123") {
    status
    estimatedDelivery
    events {
      timestamp
      description
      status
    }
  }
}
```

**Cara Hitung HMAC Signature** (untuk Tim Toko):
```python
import hmac
import hashlib
from datetime import datetime

api_key = "TOKO_API_KEY_001"
secret = "shared_secret_with_toko_12345"
timestamp = datetime.utcnow().isoformat() + "Z"
body = '{"query":"...", "variables":{...}}'

message = api_key + timestamp + body
signature = hmac.new(secret.encode(), message.encode(), hashlib.sha256).hexdigest()
```

---

## 🚀 Quick Start (Untuk Teman-Teman)

### **1. Clone Repository**
```bash
git clone https://github.com/aturrr62/IAE-kurang-tidur.git
cd IAE-kurang-tidur
```

### **2. Setup Environment**
```bash
# Copy .env untuk semua services
cp services/product-service/.env.example services/product-service/.env
cp services/order-service/.env.example services/order-service/.env
cp services/stock-service/.env.example services/stock-service/.env
cp services/shipping-service/.env.example services/shipping-service/.env
```

### **3. Jalankan Docker**
```bash
docker-compose up --build -d
```

### **4. Install JWT Library**
```bash
# Stock Service (Gudang)
docker-compose exec stock-service composer require firebase/php-jwt

# Shipping Service (Gudang)
docker-compose exec shipping-service composer require firebase/php-jwt
```

### **5. Jalankan Migrations & Seeders**
```bash
# Product Service (Toko)
docker-compose exec product-service php artisan migrate --seed

# Order Service (Toko)
docker-compose exec order-service php artisan migrate

# Stock Service (Gudang) - PENTING: Ada seeder users & inventory!
docker-compose exec stock-service php artisan migrate --seed

# Shipping Service (Gudang)
docker-compose exec shipping-service php artisan migrate
```

### **6. Test Endpoints**
Buka GraphQL Playground:
- Product: http://localhost:8001/graphql
- Order: http://localhost:8002/graphql
- Stock: http://localhost:8003/graphql
- Shipping: http://localhost:8004/graphql

---

## 🧪 TESTING GRAPHQL - PANDUAN LENGKAP

### 📘 **Product Service (Port 8001)** - 🔵 TIM TOKO

**Total Endpoints: 4**

#### **Queries (3)**

1️⃣ **Get All Products**
```graphql
query {
  products {
    id
    code
    name
    category
    price
    stock
    minStockThreshold
  }
}
```
**Test:** Cek apakah semua produk toko muncul

---

2️⃣ **Get Single Product**
```graphql
query {
  product(id: 1) {
    id
    code
    name
    price
    stock
  }
}
```
**Test:** Cek detail 1 produk

---

3️⃣ **Check Stock from Warehouse** (Integrasi ke Gudang)
```graphql
query {
  checkStock(productCode: "ELEC001") {
    productCode
    available
    warehouseStock
  }
}
```
**Test:** Cek stock dari gudang (integrasi lintas kelompok)

---

#### **Mutations (1)**

4️⃣ **Decrease Stock** (Ketika ada pembelian)
```graphql
mutation {
  decreaseStock(productId: 1, quantity: 5) {
    id
    code
    stock
  }
}
```
**Test:** Kurangi stock ketika ada order pelanggan

---

### 📗 **Order Service (Port 8002)** - 🔵 TIM TOKO

**Total Endpoints: 3**

#### **Queries (1)**

1️⃣ **Track Order Status**
```graphql
query {
  trackOrder(orderCode: "ORD-001") {
    orderCode
    status
    events
  }
}
```
**Test:** Tracking order pelanggan

---

#### **Mutations (2)**

2️⃣ **Create Order** (Pelanggan beli produk)
```graphql
mutation {
  createOrder(productId: 1, quantity: 3) {
    success
    orderId
    message
  }
}
```
**Test:** Buat order baru dari pelanggan

---

3️⃣ **Request Restock to Warehouse** (Integrasi ke Gudang - WAJIB)
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01",
    items: [
      { productCode: "ELEC001", quantity: 50 }
    ]
  }) {
    success
    orderId
    estimatedDelivery
    message
  }
}
```
**Headers Required (HMAC):**
```
X-API-Key: TOKO_API_KEY_001
X-Signature: <HMAC-SHA256 signature>
X-Timestamp: 2025-12-31T10:00:00Z
```
**Test:** Request restock dari gudang (integrasi lintas kelompok)

---

### 📙 **Stock Service (Port 8003)** - 🟢 TIM GUDANG (KITA)

**Total Endpoints: 7**

#### **Authentication (3)**

1️⃣ **Login (Dapat JWT Token)**
```graphql
mutation {
  login(email: "admin@gudang.com", password: "password123") {
    token
    user {
      id
      username
      name
      email
      role
    }
  }
}
```
**Test:** Login staff gudang → dapat JWT token

---

2️⃣ **Register User Baru**
```graphql
mutation {
  register(input: {
    username: "staff001"
    name: "Staff Gudang 1"
    email: "staff001@gudang.com"
    password: "password123"
    role: "STAFF_GUDANG"
  }) {
    id
    username
    email
    role
  }
}
```
**Test:** Daftar user baru

---

3️⃣ **Get Current User (Me)**
```graphql
query {
  me {
    id
    username
    name
    email
    role
  }
}
```
**Headers Required:**
```
Authorization: Bearer <JWT_TOKEN>
```
**Test:** Cek user yang login (pakai token dari login)

---

#### **Inventory Management (4)**

4️⃣ **Check Stock 1 Produk**
```graphql
query {
  checkStock(productCode: "ELEC001") {
    id
    productCode
    productName
    stock
  }
}
```
**Test:** Cek stock 1 produk di gudang

---

5️⃣ **Get All Inventories**
```graphql
query {
  inventories {
    id
    productCode
    productName
    stock
  }
}
```
**Test:** Lihat semua stock gudang

---

6️⃣ **Increase Stock (Tambah Stock)**
```graphql
mutation {
  increaseStock(productCode: "ELEC001", quantity: 100) {
    id
    productCode
    productName
    stock
  }
}
```
**Test:** Tambah stock produk di gudang

---

7️⃣ **Decrease Stock (Kurangi Stock)**
```graphql
mutation {
  decreaseStock(productCode: "ELEC001", quantity: 50) {
    id
    productCode
    productName
    stock
  }
}
```
**Test:** Kurangi stock (ketika dikirim ke toko)

---

### 📕 **Shipping Service (Port 8004)** - 🟢 TIM GUDANG (KITA)

**Total Endpoints: 8**

#### **External API (Untuk Toko - WAJIB)**

1️⃣ **Request Restock** (Endpoint untuk Toko)
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC001"
    quantity: 50
    storeAddress: "Jl. Toko No. 1"
  }) {
    success
    orderCode
    estimatedDelivery
    message
  }
}
```
**Headers Required (API Key + HMAC):**
```
X-API-Key: TOKO_API_KEY_001
X-Signature: <HMAC-SHA256 signature>
X-Timestamp: 2025-12-31T10:00:00Z
```
**Test:** Terima request restock dari Toko

---

2️⃣ **Track Order** (Toko tracking pengiriman)
```graphql
query {
  trackOrder(orderCode: "WH-20251231-ABC123") {
    orderCode
    status
    estimatedDelivery
    events {
      timestamp
      description
      status
    }
  }
}
```
**Headers Required (API Key + HMAC):** Same as above
**Test:** Toko tracking status pengiriman

---

#### **Internal API (Untuk Staff Gudang - JWT)**

3️⃣ **Get All Warehouse Orders**
```graphql
query {
  warehouseOrders {
    id
    tokoOrderCode
    productCode
    quantity
    status
    createdAt
  }
}
```
**Headers Required:**
```
Authorization: Bearer <JWT_TOKEN>
```
**Test:** Lihat semua order dari toko

---

4️⃣ **Get Single Warehouse Order**
```graphql
query {
  warehouseOrder(id: 1) {
    id
    tokoOrderCode
    productCode
    quantity
    status
    shipment {
      shippingCode
      status
    }
  }
}
```
**Test:** Detail 1 order warehouse

---

5️⃣ **Create Warehouse Order** (Internal)
```graphql
mutation {
  createWarehouseOrder(input: {
    tokoOrderCode: "TOKO-001"
    productCode: "ELEC001"
    quantity: 50
    userId: 1
  }) {
    id
    tokoOrderCode
    status
  }
}
```
**Test:** Buat order warehouse manual

---

6️⃣ **Approve Warehouse Order**
```graphql
mutation {
  approveWarehouseOrder(id: 1, status: "DITERIMA") {
    id
    tokoOrderCode
    status
  }
}
```
**Test:** Staff gudang approve order

---

7️⃣ **Create Shipment**
```graphql
mutation {
  createShipment(input: {
    warehouseOrderId: 1
    storeAddress: "Jl. Toko No. 1"
    shippingCode: "SHIP-001"
  }) {
    id
    shippingCode
    status
    storeAddress
  }
}
```
**Test:** Buat pengiriman setelah order di-approve

---

8️⃣ **Update Shipment Status**
```graphql
mutation {
  updateShipmentStatus(id: 1, status: "DIKIRIM") {
    id
    shippingCode
    status
    shippedAt
  }
}
```
**Test:** Update status pengiriman (DIKIRIM, DITERIMA_TOKO)

---

## 📊 SUMMARY TESTING

| Service | Total Endpoints | Queries | Mutations | Auth Required |
|---------|-----------------|---------|-----------|---------------|
| **Product** (Toko) | 4 | 3 | 1 | ❌ No |
| **Order** (Toko) | 3 | 1 | 2 | ❌ No |
| **Stock** (Gudang) | 7 | 3 | 4 | ⚠️ Some (JWT) |
| **Shipping** (Gudang) | 8 | 3 | 5 | ⚠️ Mixed (JWT + API Key) |
| **TOTAL** | **22** | **10** | **12** | - |

---

## 🎯 URUTAN TESTING YANG DISARANKAN

### **Untuk Tim TOKO:**
1. **Product Service** → Test `products`, `product(id)`, `checkStock`
2. **Order Service** → Test `createOrder`
3. **Integrasi** → Test `requestRestock` ke Shipping Service (butuh API Key + HMAC)
4. **Tracking** → Test `trackOrder` dari Shipping Service

### **Untuk Tim GUDANG (Kita):**
1. **Stock Service** → `register` → `login` (dapat JWT)
2. **Stock Service** → `me` (pakai JWT) → `inventories` → `checkStock`
3. **Shipping Service** → Test `warehouseOrders` (pakai JWT)
4. **Workflow Lengkap:**
   - Toko request restock → `requestRestock`
   - Staff gudang approve → `approveWarehouseOrder`
   - Buat shipment → `createShipment`
   - Update status → `updateShipmentStatus` (DIKIRIM → DITERIMA_TOKO)
   - Toko tracking → `trackOrder`

---


## 🔧 Tech Stack
- Laravel 10
- GraphQL (Lighthouse)
- MySQL 8.0
- Docker Compose
- JWT (HS256) + API Key (HMAC-SHA256)

---

## 📂 Struktur Folder Project

```
iae-enterprise-integration/
├── services/                          # Semua microservices
│   ├── product-service/              # 🔵 TOKO - Manajemen Produk
│   │   ├── app/
│   │   │   ├── Models/               # Product model
│   │   │   └── GraphQL/              # Product resolvers
│   │   ├── database/
│   │   │   ├── migrations/           # Product DB schema
│   │   │   └── seeders/              # Product seeder
│   │   ├── graphql/
│   │   │   └── schema.graphql        # Product GraphQL schema
│   │   ├── .env.example              # Product environment config
│   │   └── Dockerfile
│   │
│   ├── order-service/                # 🔵 TOKO - Order Pelanggan
│   │   ├── app/
│   │   │   ├── Models/               # Order model
│   │   │   └── GraphQL/              # Order resolvers
│   │   ├── database/
│   │   │   └── migrations/           # Order DB schema
│   │   ├── graphql/
│   │   │   └── schema.graphql        # Order GraphQL schema
│   │   ├── .env.example              # Order environment config
│   │   └── Dockerfile
│   │
│   ├── stock-service/                # 🟢 GUDANG - Auth + Inventory
│   │   ├── app/
│   │   │   ├── Models/
│   │   │   │   └── User.php          # ⭐ User model (username, role)
│   │   │   ├── Helpers/
│   │   │   │   └── JwtHelper.php     # ⭐ JWT generation
│   │   │   └── GraphQL/
│   │   │       └── Mutations/
│   │   │           └── AuthMutation.php  # ⭐ login, register, me
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   │   ├── create_users_table.php       # ⭐ users table
│   │   │   │   └── create_inventory_table.php   # ⭐ inventory table
│   │   │   └── seeders/
│   │   │       ├── UserSeeder.php              # ⭐ 3 users
│   │   │       └── InventorySeeder.php         # ⭐ 10 products
│   │   ├── graphql/
│   │   │   └── schema.graphql        # ⭐ Auth + Stock schema
│   │   ├── .env.example              # ⭐ JWT_SECRET config
│   │   └── Dockerfile
│   │
│   └── shipping-service/             # 🟢 GUDANG - Warehouse Orders + Shipping
│       ├── app/
│       │   ├── Models/
│       │   │   ├── WarehouseOrder.php
│       │   │   └── Shipment.php
│       │   ├── Helpers/
│       │   │   └── JwtHelper.php     # ⭐ JWT verification
│       │   ├── Http/Middleware/
│       │   │   ├── JwtAuthMiddleware.php    # ⭐ Internal auth
│       │   │   └── ApiKeyMiddleware.php     # ⭐ External auth (HMAC)
│       │   └── GraphQL/
│       │       ├── Mutations/
│       │       │   └── RequestRestock.php   # ⭐ For Toko
│       │       └── Queries/
│       │           └── TrackOrder.php       # ⭐ For Toko
│       ├── database/
│       │   └── migrations/
│       │       ├── create_warehouse_orders_table.php  # ⭐ Orders table
│       │       └── create_shipments_table.php         # ⭐ Shipments table
│       ├── graphql/
│       │   └── schema.graphql        # ⭐ External + Internal API
│       ├── .env.example              # ⭐ JWT + API Key config
│       └── Dockerfile
│
├── docker-compose.yml                # ⭐ 4 services + 4 MySQL containers
├── README.md                         # 📖 Dokumentasi ini
├── LICENSE                           # MIT License
└── .gitignore
```

### 🌟 File-File Penting untuk Tim

**Untuk SEMUA Tim:**
- `README.md` - Dokumentasi utama & pembagian tugas
- `docker-compose.yml` - Konfigurasi Docker untuk semua services

**Untuk Tim TOKO:**
- `services/product-service/graphql/schema.graphql` - GraphQL schema produk
- `services/order-service/graphql/schema.graphql` - GraphQL schema order
- `services/stock-service/graphql/schema.graphql` - **CEK STOCK** dari Gudang
- `services/shipping-service/graphql/schema.graphql` - **REQUEST RESTOCK** ke Gudang

**Untuk Tim GUDANG (Kita):**
- `services/stock-service/app/GraphQL/Mutations/AuthMutation.php` - Login/Register logic
- `services/stock-service/app/Helpers/JwtHelper.php` - JWT generation
- `services/shipping-service/app/GraphQL/Mutations/RequestRestock.php` - Handle request dari Toko
- `services/shipping-service/app/Http/Middleware/ApiKeyMiddleware.php` - HMAC validation

---

## Services
### Toko Domain
- Product Service → port 8001
- Order Service → port 8002

### Gudang Domain
- Stock Service → port 8003
- Shipping Service → port 8004

## Cara Menjalankan
```bash
# Salin konfigurasi environment
cp services/product-service/.env.example services/product-service/.env
cp services/order-service/.env.example services/order-service/.env
cp services/stock-service/.env.example services/stock-service/.env
cp services/shipping-service/.env.example services/shipping-service/.env

# Jalankan container
docker-compose up --build -d

# Jalankan migrasi dan seeder
docker-compose exec product-service php artisan migrate --seed
docker-compose exec order-service php artisan migrate
docker-compose exec stock-service php artisan migrate
docker-compose exec shipping-service php artisan migrate

## Akses Database (External/Host)
Jika ingin mengakses database via Navicat/DBeaver dari PC host:
- **Product DB**: localhost port `3306`
- **Order DB**: localhost port `3307`
- **Stock DB**: localhost port `3308`
- **Shipping DB**: localhost port `3309`
Password root: `root`
```

## Akses GraphQL Playground
- **Product**: http://localhost:8001/graphql
- **Order**: http://localhost:8002/graphql
- **Stock (Gudang)**: http://localhost:8003/graphql-playground ⭐
- **Shipping (Gudang)**: http://localhost:8004/graphql-playground ⭐

### 🔑 Login Credentials (Stock Service)
```
Admin      → Username: admin        | Password: admin123
Manager    → Username: manager_inv  | Password: manager123
Staff Ship → Username: staff_ship   | Password: staff123
Staff Inv  → Username: staff_inv    | Password: staff123
```
## 🧾 Narasi Laporan (Akademis)
> Sistem backend dikembangkan menggunakan arsitektur microservice dalam satu monorepo yang terdiri dari empat service, yaitu Product Service dan Order Service untuk domain Toko, serta Stock Service dan Shipping Service untuk domain Gudang. Seluruh service dikembangkan menggunakan Laravel 10 dan berkomunikasi secara eksternal maupun internal menggunakan GraphQL (Lighthouse). Setiap service memiliki database fisik yang terisolasi melalui container Docker terpisah untuk menjamin independensi data sesuai prinsip microservices.

## ✅ STATUS AKHIR TEMPLATE
✔ **TOKO** – LENGKAP (Product, Order)  
✔ **GUDANG** – LENGKAP (Inventory, Shipping, Warehouse Orders)  
✔ **GraphQL** – 100% Sesuai Kontrak  
✔ **Docker** – Isolasi Fisik Database (4 Container MySQL)  
✔ **Monorepo** – Terstruktur & Bersih  

---
🚀 **INI ADALAH VERSI FINAL. SIAP DI-PUSH KE GITHUB & DIKUMPULKAN.**
