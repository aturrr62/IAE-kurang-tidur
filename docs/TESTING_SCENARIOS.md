# 🧪 TESTING SCENARIOS - Gudang Elektronik Microservices

## 📋 OVERVIEW
Document ini berisi skenario testing lengkap untuk memvalidasi semua fitur system microservices Gudang Elektronik.

---

## ✅ PRE-REQUISITES CHECKLIST

Sebelum melakukan testing, pastikan:
- [ ] Docker containers sudah running (`docker-compose ps`)
- [ ] Migrations sudah dijalankan untuk semua services
- [ ] Seeders sudah dijalankan untuk Stock Service
- [ ] firebase/php-jwt sudah diinstall di Stock & Shipping Service
- [ ] GraphQL Playground accessible di semua ports

---

## 🔐 SCENARIO 1: AUTHENTICATION FLOW

### Test 1.1: User Login (Stock Service)
**Endpoint**: `http://localhost:8003/graphql`

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

**Expected Result**:
- ✅ Status 200 OK
- ✅ Token JWT valid (string panjang)
- ✅ User data sesuai dengan seeder

**Validation**:
- Copy token untuk digunakan di test selanjutnya
- Decode token di [jwt.io](https://jwt.io) → harus ada `data.id`, `data.username`, dll

---

### Test 1.2: Login dengan Password Salah
**Mutation**:
```graphql
mutation {
  login(email: "admin@gudang.com", password: "wrongpassword") {
    token
    user { id }
  }
}
```

**Expected Result**:
- ✅ Error: "Invalid email or password"
- ❌ Tidak ada token yang dikembalikan

---

### Test 1.3: Register User Baru
**Mutation**:
```graphql
mutation {
  register(input: {
    username: "staff_test"
    name: "Staff Testing"
    email: "staff_test@gudang.com"
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

**Expected Result**:
- ✅ User baru berhasil dibuat
- ✅ Role = "STAFF_GUDANG"
- ✅ ID auto-increment

---

### Test 1.4: Register dengan Email Duplicate
**Mutation**:
```graphql
mutation {
  register(input: {
    username: "admin_gudang2"
    name: "Admin Test"
    email: "admin@gudang.com"  # Email sudah ada
    password: "password123"
  }) {
    id
  }
}
```

**Expected Result**:
- ✅ Error: "Email already registered"

---

### Test 1.5: Get Current User dengan JWT
**Endpoint**: `http://localhost:8003/graphql`  
**Headers**: `Authorization: Bearer <token-from-test-1.1>`

**Query**:
```graphql
query {
  me {
    id
    username
    email
    role
  }
}
```

**Expected Result**:
- ✅ Data user sesuai dengan token
- ✅ Tidak memerlukan parameter email/password

---

## 📦 SCENARIO 2: STOCK MANAGEMENT

### Test 2.1: Check Stock (Public Access)
**Endpoint**: `http://localhost:8003/graphql`

**Query**:
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

**Expected Result**:
- ✅ Product found: Samsung Galaxy S24 Ultra 256GB
- ✅ Stock = 150 (sesuai seeder)

---

### Test 2.2: Get All Inventory
**Query**:
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

**Expected Result**:
- ✅ Return 10 products (ELEC001 - ELEC010)
- ✅ Semua stock > 0

---

### Test 2.3: Increase Stock
**Mutation**:
```graphql
mutation {
  increaseStock(productCode: "ELEC001", quantity: 100) {
    productCode
    productName
    stock
  }
}
```

**Expected Result**:
- ✅ Stock bertambah 100 (dari 150 menjadi 250)

**Validation**:
- Run test 2.1 lagi → stock harus 250

---

### Test 2.4: Decrease Stock
**Mutation**:
```graphql
mutation {
  decreaseStock(productCode: "ELEC001", quantity: 50) {
    productCode
    stock
  }
}
```

**Expected Result**:
- ✅ Stock berkurang 50 (dari 250 menjadi 200)

---

## 🚚 SCENARIO 3: EXTERNAL API - REQUEST RESTOCK (TOKO)

### Setup: Calculate HMAC Signature
Gunakan script berikut untuk generate signature (Python):
```python
import hmac
import hashlib
from datetime import datetime
import json

api_key = "TOKO_API_KEY_001"
secret = "shared_secret_with_toko_12345"
timestamp = datetime.utcnow().isoformat() + "Z"

# GraphQL request body
body = json.dumps({
    "query": """
    mutation {
      requestRestock(input: {
        storeId: "STORE_01"
        productCode: "ELEC001"
        quantity: 50
        storeAddress: "Jl. Sudirman No. 123, Jakarta"
      }) {
        success
        orderCode
        estimatedDelivery
        message
      }
    }
    """
})

message = api_key + timestamp + body
signature = hmac.new(secret.encode(), message.encode(), hashlib.sha256).hexdigest()

print(f"X-API-Key: {api_key}")
print(f"X-Timestamp: {timestamp}")
print(f"X-Signature: {signature}")
print(f"Body: {body}")
```

### Test 3.1: Request Restock - Success
**Endpoint**: `http://localhost:8004/graphql`  
**Headers**:
```
X-API-Key: TOKO_API_KEY_001
X-Signature: <signature from script above>
X-Timestamp: <timestamp from script above>
Content-Type: application/json
```

**Mutation**:
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC001"
    quantity: 50
    storeAddress: "Jl. Sudirman No. 123, Jakarta"
  }) {
    success
    orderCode
    estimatedDelivery
    message
  }
}
```

**Expected Result**:
- ✅ success = true
- ✅ orderCode format: "WH-YYYYMMDD-XXXXXX"
- ✅ estimatedDelivery = 3 days from now
- ✅ message = "Restock request created successfully..."

---

### Test 3.2: Request Restock - Insufficient Stock
**Mutation** (quantity > available stock):
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC010"  # PS5 only has 50 units
    quantity: 100
    storeAddress: "Jl. Sudirman No. 123, Jakarta"
  }) {
    success
    message
  }
}
```

**Expected Result**:
- ✅ success = false
- ✅ message = "Insufficient stock. Available: 50, Requested: 100"

---

### Test 3.3: Request Restock - Product Not Found
**Mutation**:
```graphql
mutation {
  requestRestock(input: {
    storeId: "STORE_01"
    productCode: "ELEC999"  # Non-existent
    quantity: 10
    storeAddress: "Jl. Sudirman No. 123, Jakarta"
  }) {
    success
    message
  }
}
```

**Expected Result**:
- ✅ success = false
- ✅ message = "Product not found in warehouse inventory"

---

### Test 3.4: Request Restock - Invalid HMAC Signature
**Headers** (wrong signature):
```
X-API-Key: TOKO_API_KEY_001
X-Signature: wrong_signature_12345
X-Timestamp: 2025-12-30T10:00:00Z
```

**Expected Result**:
- ✅ HTTP 401 Unauthorized
- ✅ Error: "HMAC signature verification failed"

---

### Test 3.5: Request Restock - Expired Timestamp
**Headers** (timestamp > 5 minutes old):
```
X-API-Key: TOKO_API_KEY_001
X-Signature: <valid signature>
X-Timestamp: 2025-12-30T09:00:00Z  # 1 hour ago
```

**Expected Result**:
- ✅ HTTP 401 Unauthorized
- ✅ Error: "Request timestamp is too old or invalid"

---

## 📍 SCENARIO 4: EXTERNAL API - TRACK ORDER (TOKO)

### Test 4.1: Track Existing Order
**Endpoint**: `http://localhost:8004/graphql`  
**Headers**: Same HMAC headers as Test 3.1

**Query**:
```graphql
query {
  trackOrder(orderCode: "WH-20251230-ABC123") {
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

**Expected Result**:
- ✅ Order found with status "MENUNGGU"
- ✅ events array has at least 1 event: "Restock request received"

---

### Test 4.2: Track Non-Existent Order
**Query**:
```graphql
query {
  trackOrder(orderCode: "WH-99999999-XXXX") {
    orderCode
  }
}
```

**Expected Result**:
- ✅ Error: "Order not found"

---

## 🔒 SCENARIO 5: INTERNAL API - WAREHOUSE MANAGEMENT (JWT)

### Test 5.1: Get All Warehouse Orders (Staff)
**Endpoint**: `http://localhost:8004/graphql`  
**Headers**: `Authorization: Bearer <JWT from Stock Service>`

**Query**:
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

**Expected Result**:
- ✅ List of warehouse orders dari Test 3.1
- ✅ Status = "MENUNGGU"

---

### Test 5.2: Approve Warehouse Order
**Mutation**:
```graphql
mutation {
  approveWarehouseOrder(id: 1) {
    id
    tokoOrderCode
    status
  }
}
```

**Expected Result**:
- ✅ Status berubah menjadi "DITERIMA"

**Validation**:
- Run Test 5.1 lagi → status order #1 = "DITERIMA"

---

### Test 5.3: Reject Warehouse Order
**Mutation**:
```graphql
mutation {
  rejectWarehouseOrder(id: 2) {
    status
  }
}
```

**Expected Result**:
- ✅ Status = "DITOLAK"

---

### Test 5.4: Access Internal API Without JWT
**Headers**: None (no Authorization header)

**Query**:
```graphql
query {
  warehouseOrders { id }
}
```

**Expected Result**:
- ✅ HTTP 401 Unauthorized
- ✅ Error: "No token provided"

---

### Test 5.5: Access Internal API with Invalid JWT
**Headers**: `Authorization: Bearer invalid_token_12345`

**Expected Result**:
- ✅ HTTP 401 Unauthorized
- ✅ Error: "Token is invalid, expired, or malformed"

---

## 📦 SCENARIO 6: SHIPMENT MANAGEMENT

### Test 6.1: Create Shipment for Approved Order
**Endpoint**: `http://localhost:8004/graphql`  
**Headers**: `Authorization: Bearer <JWT>`

**Mutation**:
```graphql
mutation {
  createShipment(input: {
    warehouseOrderId: 1
    shippingCode: "SHIP-20251230-001"
    storeAddress: "Jl. Sudirman No. 123, Jakarta"
  }) {
    id
    shippingCode
    status
    storeAddress
  }
}
```

**Expected Result**:
- ✅ Shipment created successfully
- ✅ status = "SIAP_DIKIRIM"

---

### Test 6.2: Update Shipment Status to DIKIRIM
**Mutation**:
```graphql
mutation {
  updateShipmentStatus(id: 1, status: "DIKIRIM") {
    shippingCode
    status
    shippedAt
  }
}
```

**Expected Result**:
- ✅ status = "DIKIRIM"
- ✅ shippedAt is set (timestamp)

---

### Test 6.3: Track Shipment (Internal)
**Query**:
```graphql
query {
  trackShipment(shippingCode: "SHIP-20251230-001") {
    shippingCode
    status
    warehouseOrder {
      tokoOrderCode
      productCode
      quantity
    }
  }
}
```

**Expected Result**:
- ✅ Shipment found
- ✅ warehouseOrder relation loaded

---

## 🔁 SCENARIO 7: COMPLETE INTEGRATION FLOW

### End-to-End Test (Toko → Gudang → Toko)

1. **Toko Request Restock** (External - API Key)
2. **Staff Login** (Stock Service - JWT)
3. **Staff View Orders** (Internal - JWT)
4. **Staff Approve Order** (Internal - JWT)
5. **Staff Create Shipment** (Internal - JWT)
6. **Staff Update to DIKIRIM** (Internal - JWT)
7. **Toko Track Order** (External - API Key) → Should show "DIKIRIM" with events

**Validation**:
- Order status timeline: MENUNGGU → DITERIMA → SIAP_DIKIRIM → DIKIRIM
- External tracking shows all events with timestamps

---

## 📊 TESTING CHECKLIST

### Authentication (Stock Service)
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Register new user
- [ ] Register with duplicate email
- [ ] Get current user with JWT

### Stock Management (Stock Service)
- [ ] Check stock by product code
- [ ] Get all inventory
- [ ] Increase stock
- [ ] Decrease stock

### External API - Toko (Shipping Service)
- [ ] Request restock - success
- [ ] Request restock - insufficient stock
- [ ] Request restock - product not found
- [ ] Request restock - invalid HMAC
- [ ] Request restock - expired timestamp
- [ ] Track order - found
- [ ] Track order - not found

### Internal API - Staff (Shipping Service)
- [ ] Get all warehouse orders (with JWT)
- [ ] Approve warehouse order
- [ ] Reject warehouse order
- [ ] Access without JWT (should fail)
- [ ] Access with invalid JWT (should fail)

### Shipment Management (Shipping Service)
- [ ] Create shipment
- [ ] Update shipment status
- [ ] Track shipment

### Integration Flow
- [ ] Complete flow: Toko request → Staff process → Toko track

---

## 🐛 COMMON ISSUES & SOLUTIONS

### Issue 1: "Class 'Firebase\JWT\JWT' not found"
**Solution**:
```bash
docker-compose exec stock-service composer require firebase/php-jwt
docker-compose exec shipping-service composer require firebase/php-jwt
```

### Issue 2: HMAC Signature Mismatch
**Solution**:
- Ensure body content is EXACTLY the same (no whitespace differences)
- Timestamp format must be ISO 8601 with 'Z' suffix
- Secret must match in .env: `API_SECRET_KEY=shared_secret_with_toko_12345`

### Issue 3: JWT Token Invalid
**Solution**:
- Ensure `JWT_SECRET` is SAME in Stock & Shipping .env
- Check token is not expired (24 hours default)
- Verify "Bearer " prefix in Authorization header

### Issue 4: CORS Errors
**Solution**: Add to `config/cors.php`:
```php
'paths' => ['api/*', 'graphql'],
'allowed_origins' => ['*'],
```

---

## 📝 TEST REPORT TEMPLATE

```
Date: YYYY-MM-DD
Tester: [Name]
Environment: Docker Compose

| Scenario | Test Case | Status | Notes |
|----------|-----------|--------|-------|
| 1.1      | Login     | ✅ PASS | Token generated |
| 1.2      | Wrong Pass| ✅ PASS | Error shown |
| 2.1      | Check Stock| ✅ PASS | 150 units |
| 3.1      | Request Restock| ✅ PASS | Order created |
| ...      | ...       | ...    | ... |

TOTAL TESTS: XX
PASSED: XX
FAILED: XX
COVERAGE: XX%
```

---

**Last Updated**: 2025-12-30  
**Version**: 1.0
