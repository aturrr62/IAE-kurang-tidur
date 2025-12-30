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

**🔨 Yang PERLU DIKERJAKAN (Opsional - Enhancement):**
- [ ] Frontend dashboard untuk staff gudang
- [ ] Testing semua endpoint (ikuti `docs/TESTING_SCENARIOS.md`)
- [ ] Monitoring & logging
- [ ] Rate limiting untuk API eksternal

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
- Product: [http://localhost:8001/graphql](http://localhost:8001/graphql)
- Order: [http://localhost:8002/graphql](http://localhost:8002/graphql)
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
