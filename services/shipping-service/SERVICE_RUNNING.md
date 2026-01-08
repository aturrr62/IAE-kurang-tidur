# 🚀 Shipping Service - RUNNING ✅

## Status: Live and Ready

The Shipping Service is currently running and accessible at:

```
http://localhost:8000/graphql
```

### What's Running

✅ **Shipping Service**

-   URL: http://localhost:8000/graphql
-   Framework: Laravel 10 with Nuwave Lighthouse GraphQL
-   Database: SQLite (in-memory)
-   PHP Version: 8.2.12
-   Status: RUNNING

### Test the Service

#### Option 1: GraphQL Playground (Browser)

Open: http://localhost:8000/graphql

You can test queries directly in the browser's GraphQL interface.

#### Option 2: Test via Command Line

```powershell
# Simple introspection query
Invoke-WebRequest -Uri "http://localhost:8000/graphql" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{
    "query": "{ __schema { types { name } } }"
  }' | Select-Object -ExpandProperty Content
```

### Available Queries

```graphql
# External API (Requires API Key Authentication)

query {
    trackOrder(tokoOrderCode: "ORDER-001") {
        status
        currentLocation
        estimatedDelivery
        events {
            eventType
            timestamp
            description
        }
    }
}

query {
    getOrderDetails(orderId: 1) {
        id
        tokoOrderCode
        storeCode
        status
        items {
            productCode
            quantity
            unitPrice
        }
    }
}

query {
    getStoreOrders(storeCode: "STORE-001") {
        id
        tokoOrderCode
        status
        createdAt
    }
}
```

### Available Mutations

```graphql
# External API (Requires API Key + HMAC Signature)

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

# Internal API (Requires JWT Bearer Token)

mutation {
    approveWarehouseOrder(orderId: 1) {
        success
        message
        order {
            id
            status
        }
    }
}
```

### Database Schema

All migrations have been successfully applied:

-   ✅ users table
-   ✅ warehouse_orders table
-   ✅ order_items table
-   ✅ shipments table
-   ✅ shipment_tracking table
-   ✅ external_api_keys table

### Authentication Test Data

The following API keys are available for testing:

```
1. Toko ElectroMart
   API Key: electromart_api_key_2024
   Secret Key: shared_secret_with_toko_12345

2. Toko Elektronik Central
   API Key: central_api_key_2024
   Secret Key: shared_secret_central_67890

3. Test Store Dev
   API Key: test_key_dev_123
   Secret Key: test_secret_dev_456
```

### How to Calculate HMAC Signature for Testing

```python
import hashlib
import hmac
import json
import time

api_key = "electromart_api_key_2024"
secret_key = "shared_secret_with_toko_12345"
timestamp = int(time.time())

query = "mutation { requestRestock(input: { storeId: \"STORE-001\" items: [{productCode: \"PROD-001\" quantity: 10}] }) { success } }"
variables = '{"input":{"storeId":"STORE-001","items":[{"productCode":"PROD-001","quantity":10}]}}'

# Create signature
message = query + variables + str(timestamp)
signature = hmac.new(
    secret_key.encode(),
    message.encode(),
    hashlib.sha256
).hexdigest()

print(f"X-API-Key: {api_key}")
print(f"X-Timestamp: {timestamp}")
print(f"X-Signature: {signature}")
```

### Stopping the Service

Press `Ctrl+C` in the terminal where the service is running.

### Production Notes

⚠️ **Important**: This is running with SQLite in-memory database. For production:

1. Switch to MySQL database (modify .env)
2. Use the Docker Compose setup (when Docker registry is accessible)
3. Add proper logging and monitoring
4. Implement error handling and validation
5. Set up proper security headers
6. Enable HTTPS/TLS

### Next Steps

1. **Test Endpoints**: Visit http://localhost:8000/graphql to test queries
2. **Check Logs**: Watch terminal for request logs
3. **Create Orders**: Use requestRestock mutation to test order creation
4. **Track Orders**: Use trackOrder query to verify tracking
5. **Approve Orders**: Use internal mutations to process orders

---

**Service Status: ✅ READY TO TEST**

Date Started: January 7, 2026
Server: Laravel Development Server
Port: 8000
