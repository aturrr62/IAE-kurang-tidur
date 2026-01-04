# 🔗 Integration Guide - Stock Service dengan Shipping Service

## Overview

Stock Service sebagai **Auth Provider** dan **Inventory Provider** untuk Shipping Service.

---

## 🔑 Shared Configuration

### JWT Secret (CRITICAL!)

**Stock Service `.env`:**
```env
JWT_SECRET=shared_secret_stock_shipping_2024
JWT_EXPIRATION=1800
```

**Shipping Service `.env`:**
```env
JWT_SECRET=shared_secret_stock_shipping_2024
```

⚠️ **PENTING:** JWT_SECRET HARUS SAMA di kedua service!

---

## 🌐 Network Communication

### Endpoints

**Internal (dari Shipping Service):**
```
http://stock-service:8003/graphql
```

**External (dari luar Docker):**
```
http://localhost:8003/graphql
```

---

## 🔐 Authentication Flow

### 1. Staff Login di Stock Service

```graphql
# Request
mutation {
  login(username: "staff_ship", password: "staff123") {
    token
    expiresIn
    user {
      id
      username
      role
      department
    }
  }
}

# Response
{
  "data": {
    "login": {
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "expiresIn": 1800,
      "user": {
        "id": "3",
        "username": "staff_ship",
        "role": "staff",
        "department": "shipping"
      }
    }
  }
}
```

### 2. Shipping Service Verify Token

Token yang diterbitkan Stock Service bisa diverifikasi di Shipping Service karena menggunakan shared secret yang sama.

**Shipping Service Code (PHP/Laravel):**
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = $request->bearerToken();
$secret = env('JWT_SECRET'); // shared_secret_stock_shipping_2024

try {
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    
    // Token valid, dapat user info
    $userId = $decoded->sub;
    $username = $decoded->username;
    $role = $decoded->role;
    $department = $decoded->department;
    
    // Gunakan user info untuk authorization
    
} catch (Exception $e) {
    // Token invalid atau expired
    return response()->json(['error' => 'Unauthorized'], 401);
}
```

---

## 📦 Stock Integration Use Cases

### Use Case 1: Check Stock Availability

**Scenario:** Shipping Service perlu validasi stok sebelum proses order

**Shipping Service Request:**
```graphql
query CheckStockAvailability {
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

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

**Response (Available):**
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

**Response (Not Available):**
```json
{
  "data": {
    "checkStock": {
      "productCode": "ELECT-010",
      "productName": "Webcam Logitech Brio 4K",
      "available": false,
      "currentStock": 0,
      "requestedQuantity": 5,
      "message": "Product out of stock"
    }
  }
}
```

### Use Case 2: Reserve Stock for Order

**Scenario:** Order dibuat, stok perlu di-reserve

**Shipping Service Request:**
```graphql
mutation ReserveStockForOrder {
  reserveStock(input: {
    productCode: "ELECT-001"
    quantity: 5
    orderId: "ORD-2024-001"
  }) {
    productCode
    available
    currentStock
    message
  }
}
```

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

**Response (Success):**
```json
{
  "data": {
    "reserveStock": {
      "productCode": "ELECT-001",
      "available": true,
      "currentStock": 45,
      "message": "Stock reserved successfully"
    }
  }
}
```

**What Happens:**
- Current stock: 50 → 45
- Transaction recorded: action=RESERVE, quantity=5, note="Reserved for order: ORD-2024-001"
- Staff attribution (dari JWT token)

### Use Case 3: Release Stock (Cancel Order)

**Scenario:** Order dibatalkan, stok perlu di-release kembali

**Shipping Service Request:**
```graphql
mutation ReleaseStockFromOrder {
  releaseStock(
    productCode: "ELECT-001"
    quantity: 5
    orderId: "ORD-2024-001"
  ) {
    productCode
    currentStock
  }
}
```

**Response:**
```json
{
  "data": {
    "releaseStock": {
      "productCode": "ELECT-001",
      "currentStock": 50
    }
  }
}
```

**What Happens:**
- Current stock: 45 → 50
- Transaction recorded: action=RELEASE, quantity=5, note="Released from cancelled order: ORD-2024-001"

### Use Case 4: Bulk Stock Check

**Scenario:** Check multiple products di satu request

**Shipping Service Request:**
```graphql
query BulkStockCheck {
  bulkCheckStock(items: [
    { productCode: "ELECT-001", quantity: 5 },
    { productCode: "ELECT-002", quantity: 10 },
    { productCode: "ELECT-010", quantity: 1 }
  ]) {
    allAvailable
    results {
      productCode
      available
      currentStock
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
          "productCode": "ELECT-002",
          "available": true,
          "currentStock": 75,
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

---

## 💻 Implementation Examples

### PHP/Laravel (Shipping Service)

**Setup GraphQL Client:**
```php
use GuzzleHttp\Client;

class StockServiceClient
{
    protected $client;
    protected $endpoint;

    public function __construct()
    {
        $this->client = new Client();
        // Internal Docker network endpoint
        $this->endpoint = 'http://stock-service:8003/graphql';
    }

    protected function getAuthToken()
    {
        // Get JWT token dari session atau authentication
        return session('jwt_token');
    }

    public function checkStock(string $productCode, int $quantity = 1)
    {
        $query = '
            query CheckStock($code: String!, $qty: Int!) {
                checkStock(productCode: $code, quantity: $qty) {
                    productCode
                    productName
                    available
                    currentStock
                    requestedQuantity
                    message
                }
            }
        ';

        $response = $this->client->post($this->endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'query' => $query,
                'variables' => [
                    'code' => $productCode,
                    'qty' => $quantity
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['data']['checkStock'];
    }

    public function reserveStock(string $productCode, int $quantity, string $orderId)
    {
        $mutation = '
            mutation ReserveStock($input: ReserveStockInput!) {
                reserveStock(input: $input) {
                    productCode
                    available
                    currentStock
                    message
                }
            }
        ';

        $response = $this->client->post($this->endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'query' => $mutation,
                'variables' => [
                    'input' => [
                        'productCode' => $productCode,
                        'quantity' => $quantity,
                        'orderId' => $orderId
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['data']['reserveStock'];
    }

    public function releaseStock(string $productCode, int $quantity, string $orderId)
    {
        $mutation = '
            mutation ReleaseStock($code: String!, $qty: Int!, $orderId: String) {
                releaseStock(productCode: $code, quantity: $qty, orderId: $orderId) {
                    productCode
                    currentStock
                }
            }
        ';

        $response = $this->client->post($this->endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'query' => $mutation,
                'variables' => [
                    'code' => $productCode,
                    'qty' => $quantity,
                    'orderId' => $orderId
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['data']['releaseStock'];
    }
}
```

**Usage in Shipping Service:**
```php
// In ShippingController atau OrderProcessor

use App\Services\StockServiceClient;

class OrderProcessor
{
    protected $stockService;

    public function __construct(StockServiceClient $stockService)
    {
        $this->stockService = $stockService;
    }

    public function processOrder($orderData)
    {
        // 1. Check stock availability
        $stockCheck = $this->stockService->checkStock(
            $orderData['product_code'],
            $orderData['quantity']
        );

        if (!$stockCheck['available']) {
            throw new \Exception($stockCheck['message']);
        }

        // 2. Reserve stock
        try {
            $reserve = $this->stockService->reserveStock(
                $orderData['product_code'],
                $orderData['quantity'],
                $orderData['order_id']
            );

            // 3. Process shipping
            // ... shipping logic ...

            return [
                'success' => true,
                'message' => 'Order processed successfully',
                'stock_reserved' => $reserve
            ];

        } catch (\Exception $e) {
            // Handle error
            return [
                'success' => false,
                'message' => 'Failed to process order: ' . $e->getMessage()
            ];
        }
    }

    public function cancelOrder($orderData)
    {
        // Release reserved stock
        $release = $this->stockService->releaseStock(
            $orderData['product_code'],
            $orderData['quantity'],
            $orderData['order_id']
        );

        return [
            'success' => true,
            'message' => 'Order cancelled, stock released',
            'current_stock' => $release['currentStock']
        ];
    }
}
```

---

## 🧪 Testing Integration

### Test dari Shipping Service Container

```bash
# Masuk ke shipping service container
docker exec -it shipping-service bash

# Test check stock
curl -X POST http://stock-service:8003/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -d '{"query": "query { checkStock(productCode: \"ELECT-001\", quantity: 1) { available currentStock } }"}'

# Should return:
# {"data":{"checkStock":{"available":true,"currentStock":50}}}
```

### Test Connection dari Shipping Service

**Test Script (test-stock-connection.php):**
```php
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();

try {
    $response = $client->post('http://stock-service:8003/graphql', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'query' => 'query { inventoryList { productCode productName currentStock } }'
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    
    echo "✅ Connection successful!\n";
    echo "Products found: " . count($data['data']['inventoryList']) . "\n";
    
    foreach ($data['data']['inventoryList'] as $product) {
        echo "- {$product['productCode']}: {$product['productName']} (Stock: {$product['currentStock']})\n";
    }

} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
```

---

## 🔍 Debugging Tips

### Check Network Connectivity

```bash
# From shipping-service container
docker exec -it shipping-service ping stock-service

# Should resolve to IP
```

### Check Service Status

```bash
# Check if stock-service is running
docker ps | grep stock-service

# Check logs
docker-compose logs -f stock-service
```

### Verify JWT Secret

```bash
# Stock Service
docker exec -it stock-service cat .env | grep JWT_SECRET

# Shipping Service  
docker exec -it shipping-service cat .env | grep JWT_SECRET

# Should be identical!
```

---

## 📋 Integration Checklist

### Shipping Service Side:
- [ ] JWT_SECRET sama dengan Stock Service
- [ ] GuzzleHttp client installed
- [ ] StockServiceClient class created
- [ ] Error handling implemented
- [ ] Logging for stock operations

### Stock Service Side:
- [ ] JWT authentication working
- [ ] checkStock endpoint tested
- [ ] reserveStock endpoint tested
- [ ] releaseStock endpoint tested
- [ ] Database transactions working
- [ ] Alerts generating correctly

### Network:
- [ ] Both services in same Docker network (iae-network)
- [ ] stock-service accessible from shipping-service
- [ ] Port 8003 exposed correctly

---

## 🚨 Common Issues & Solutions

### Issue 1: Connection Refused

**Error:** `Connection refused to stock-service:8003`

**Solution:**
```bash
# Check if stock-service is running
docker ps | grep stock-service

# Restart if needed
docker-compose restart stock-service

# Check network
docker network inspect iae-network
```

### Issue 2: Invalid Token

**Error:** `Unauthorized - Invalid token`

**Solution:**
- Verify JWT_SECRET is identical
- Check token expiration (30 minutes)
- Get fresh token via login

### Issue 3: Product Not Found

**Error:** `Product not found`

**Solution:**
- Verify product_code exists in inventory
- Check seeders ran successfully
- Query inventoryList to see available products

---

## 📊 Monitoring Integration

### Log Stock Operations in Shipping Service

```php
// In Shipping Service
Log::info('Stock check', [
    'product_code' => $productCode,
    'quantity' => $quantity,
    'result' => $stockCheck
]);

Log::info('Stock reserved', [
    'order_id' => $orderId,
    'product_code' => $productCode,
    'quantity' => $quantity,
    'new_stock' => $reserve['currentStock']
]);
```

### View Transaction History in Stock Service

```graphql
query ViewTransactions {
  stockTransactions(limit: 20) {
    id
    productCode
    action
    quantity
    note
    stockBefore
    stockAfter
    createdAt
    staff {
      username
    }
  }
}
```

---

## ✅ Success Criteria

Integration berhasil jika:

1. ✅ Shipping Service bisa login ke Stock Service
2. ✅ Token JWT valid di kedua service
3. ✅ checkStock query return data correct
4. ✅ reserveStock mutation berhasil
5. ✅ Stock berkurang setelah reserve
6. ✅ releaseStock mutation berhasil
7. ✅ Stock bertambah setelah release
8. ✅ Transaction history tercatat
9. ✅ Alert ter-generate untuk low stock
10. ✅ No errors di logs kedua service

---

**🎯 Integration Complete! Stock Service siap digunakan oleh Shipping Service! 🎯**
