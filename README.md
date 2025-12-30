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

---


## 🔧 Tech Stack
- Laravel
- GraphQL (Lighthouse)
- MySQL
- Docker Compose

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
