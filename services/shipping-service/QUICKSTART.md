# QUICK START - Shipping Service

## ⚡ 5-Minute Deploy

```bash
# 1. Go to project root
cd "c:\Users\Akchmad Reza Zandri\Downloads\Tubes EAI\IAE-kurang-tidur"

# 2. Start with Docker
docker-compose up --build

# 3. Wait for: "Laravel development server started"
# 4. Open browser: http://localhost:8004/graphql

Done! ✅
```

---

## 🧪 Test External API (Toko)

### Option 1: Simple Query (No Auth)

```graphql
query {
    trackOrder(tokoOrderCode: "ORDER-001") {
        status
        estimatedDelivery
    }
}
```

### Option 2: With API Key (requestRestock)

**Step 1**: Calculate HMAC

-   Query: `query=requestRestock`
-   Vars: `{"input":{"storeId":"STORE-001","items":[{"productCode":"PROD-001","quantity":10}]}}`
-   Timestamp: `1735137600` (Unix timestamp)
-   HMAC: `hash_hmac('sha256', queryString + varsJson + timestamp, 'shared_secret_with_toko_12345')`

**Step 2**: Add Headers to GraphQL Request

```
X-API-Key: electromart_api_key_2024
X-Timestamp: 1735137600
X-Signature: [HMAC from step 1]
```

**Step 3**: Send Mutation

```graphql
mutation {
    requestRestock(
        input: {
            storeId: "STORE-001"
            items: [{ productCode: "PROD-001", quantity: 10 }]
        }
    ) {
        success
        message
        orderId
        estimatedDelivery
    }
}
```

---

## 🔐 Test Internal API (Warehouse)

**Header:**

```
Authorization: Bearer eyJhbGc... [JWT token]
```

**Query:**

```graphql
query {
    pendingOrders {
        id
        tokoOrderCode
        status
        priority
    }
}
```

**Mutation:**

```graphql
mutation {
    approveWarehouseOrder(orderId: 1) {
        success
        order {
            id
            status
        }
    }
}
```

---

## 📊 Test Credentials

| Field      | Value                             |
| ---------- | --------------------------------- |
| API Key    | electromart_api_key_2024          |
| Secret     | shared_secret_with_toko_12345     |
| JWT Secret | shared_secret_stock_shipping_2024 |

---

## 🔗 URLs

| Service                  | URL                               |
| ------------------------ | --------------------------------- |
| Shipping GraphQL         | http://localhost:8004/graphql     |
| MySQL (Internal)         | mysql-shipping:3306               |
| Stock Service (Internal) | http://stock-service:8003/graphql |

---

## 📁 Key Files

| File                                        | Purpose                            |
| ------------------------------------------- | ---------------------------------- |
| `.env`                                      | Environment config (DB, JWT, URLs) |
| `graphql/schema.graphql`                    | GraphQL schema definition          |
| `app/GraphQL/Mutations/*.php`               | Mutation resolvers (5 files)       |
| `app/GraphQL/Queries/*.php`                 | Query resolvers (4 files)          |
| `app/Http/Middleware/*.php`                 | Auth middleware (2 files)          |
| `database/migrations/*.php`                 | DB schema (5 migrations)           |
| `database/seeders/ExternalApiKeySeeder.php` | Test data                          |

---

## ✅ Checklist

-   [x] All resolvers created (9 total)
-   [x] Middleware implemented (API Key + JWT)
-   [x] Database migrations ready
-   [x] Dependencies installed
-   [x] No compile errors
-   [x] Docker configured
-   [x] Test data seeded
-   [x] Status reports generated

---

## 🚀 STATUS: READY TO DEPLOY

All components complete. Start with `docker-compose up --build`
