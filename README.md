# Backend Monorepo – Product, Order, Stock & Shipping Services

## Tech Stack
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
