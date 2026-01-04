# 🚀 Quick Start Guide - Stock Service

## Langkah-langkah Setup Cepat

### 1️⃣ Persiapan Environment

```bash
# Masuk ke direktori stock-service
cd services/stock-service

# Copy environment file
cp .env.example .env

# Generate Laravel key
php artisan key:generate
```

### 2️⃣ Update File .env

Pastikan konfigurasi ini ada di `.env`:

```env
# Database
DB_HOST=stock-db
DB_PORT=3306
DB_DATABASE=stock_db
DB_USERNAME=stock_user
DB_PASSWORD=stock_password

# JWT Secret (PENTING: harus sama dengan Shipping Service!)
JWT_SECRET=shared_secret_stock_shipping_2024
JWT_EXPIRATION=1800

# API Key untuk akses eksternal
EXTERNAL_API_KEY=stock_api_key_external_2024

# Service Info
APP_NAME="Stock Service"
APP_URL=http://localhost:8003
SERVICE_PORT=8003
```

### 3️⃣ Install Dependencies

```bash
composer install
```

### 4️⃣ Jalankan dengan Docker

```bash
# Dari root project (bukan dari services/stock-service)
cd ../..

# Build dan jalankan stock service
docker-compose up -d stock-service

# Lihat logs (pastikan tidak ada error)
docker-compose logs -f stock-service
```

### 5️⃣ Verifikasi Service Berjalan

```bash
# Check container status
docker ps | grep stock-service

# Test GraphQL endpoint
curl http://localhost:8003/graphql

# Atau buka di browser
# http://localhost:8003/graphql
```

---

## ✅ Testing Sederhana

### Test 1: Login
Buka GraphQL Playground: `http://localhost:8003/graphql`

```graphql
mutation {
  login(username: "admin", password: "admin123") {
    token
    user {
      username
      role
    }
  }
}
```

**Expected Result:**
```json
{
  "data": {
    "login": {
      "token": "eyJ0eXAiOiJKV1Qi...",
      "user": {
        "username": "admin",
        "role": "admin"
      }
    }
  }
}
```

### Test 2: Check Stock
```graphql
query {
  checkStock(productCode: "ELECT-001", quantity: 1) {
    available
    currentStock
    productName
  }
}
```

**Expected Result:**
```json
{
  "data": {
    "checkStock": {
      "available": true,
      "currentStock": 50,
      "productName": "Laptop ASUS ROG Strix G15"
    }
  }
}
```

### Test 3: List Inventory
```graphql
query {
  inventoryList {
    productCode
    productName
    currentStock
  }
}
```

**Expected Result:** List 10 produk dari seeder

---

## 🔑 Default Accounts

Gunakan credentials berikut untuk testing:

| Username | Password | Role | Department |
|----------|----------|------|------------|
| admin | admin123 | admin | both |
| manager_inv | manager123 | manager | inventory |
| staff_ship | staff123 | staff | shipping |
| staff_inv | staff123 | staff | inventory |

---

## 🧪 Test Integration

### Test dari Shipping Service (Internal)

```bash
# Dari container shipping-service
curl -X POST http://stock-service:8003/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -d '{"query": "query { checkStock(productCode: \"ELECT-001\", quantity: 1) { available } }"}'
```

### Test dari Product Service (External - dengan API Key)

```bash
# Dari luar Docker network
curl -X POST http://localhost:8003/graphql \
  -H "Content-Type: application/json" \
  -H "X-API-Key: stock_api_key_external_2024" \
  -d '{"query": "query { checkStock(productCode: \"ELECT-001\", quantity: 1) { available currentStock } }"}'
```

---

## 🔧 Troubleshooting Cepat

### Problem: Container tidak start

```bash
# Lihat error logs
docker-compose logs stock-service

# Restart
docker-compose restart stock-service

# Rebuild jika perlu
docker-compose build --no-cache stock-service
docker-compose up -d stock-service
```

### Problem: Database tidak terkoneksi

```bash
# Check database container
docker ps | grep stock-db

# Masuk ke database
docker exec -it stock-db mysql -u stock_user -pstock_password stock_db

# Verify tables
SHOW TABLES;

# Exit
exit
```

### Problem: Migration belum jalan

```bash
# Run migration manual
docker exec -it stock-service php artisan migrate:fresh --seed

# Check migration status
docker exec -it stock-service php artisan migrate:status
```

### Problem: JWT Token tidak valid

**Cek:**
1. JWT_SECRET di `.env` sama dengan Shipping Service
2. Token belum expired (30 menit)
3. Token tidak di-blacklist

**Solution:**
```bash
# Login ulang untuk dapat token baru
# Gunakan mutation login di GraphQL Playground
```

---

## 📊 Database Check

```bash
# Masuk ke database container
docker exec -it stock-db mysql -u stock_user -pstock_password stock_db

# Check data
SELECT * FROM warehouse_staff;
SELECT * FROM inventory;
SELECT * FROM stock_transactions;
SELECT * FROM stock_alerts;

# Exit
exit
```

---

## 🎯 Endpoint Summary

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/graphql` | POST | JWT/API Key | GraphQL API |
| `/graphql` | GET | - | GraphQL Playground |

---

## 📝 Checklist Sebelum Demo

- [ ] Container stock-service running (`docker ps`)
- [ ] Database stock-db running (`docker ps`)
- [ ] GraphQL Playground accessible (`http://localhost:8003/graphql`)
- [ ] Login berhasil (mutation login)
- [ ] Data seeder ter-load (10 produk, 4 staff)
- [ ] checkStock query works
- [ ] Low stock alerts ada (ELECT-008, ELECT-010)
- [ ] Stock transaction bisa dilakukan
- [ ] API Key authentication works

---

## 🚨 Important Notes

1. **JWT_SECRET** harus SAMA dengan Shipping Service
2. Port **8003** harus available
3. Database port **3308** harus available
4. `.env` file harus ada (copy dari `.env.example`)
5. Composer dependencies harus ter-install

---

## 📚 Next Steps

1. ✅ Service sudah running
2. 📖 Baca [DOCUMENTATION.md](DOCUMENTATION.md) untuk detail lengkap
3. 🧪 Test semua endpoint di [examples.graphql](docs/queries/examples.graphql)
4. 🔗 Setup integrasi dengan Shipping Service
5. 📸 Ambil screenshots untuk laporan

---

## 💡 Tips

- Gunakan GraphQL Playground untuk testing interaktif
- Check logs dengan `docker-compose logs -f stock-service`
- Database persisten di volume `stock-db-data`
- Untuk reset database: `docker-compose down -v` (hati-hati, menghapus data!)

---

**🎉 Selamat! Stock Service siap digunakan! 🎉**
