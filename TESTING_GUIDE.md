# GraphQL Testing Guide - Shipping Service

## Quick Test: Using curl or PowerShell

### 1. Generate HMAC Signature (PowerShell Script)

Save this as `generate-signature.ps1`:

```powershell
param(
    [string]$query,
    [string]$variables,
    [string]$secret
)

$timestamp = [Math]::Floor([double](Get-Date -UFormat %s))
$message = $query + $variables + $timestamp
$bytes = [System.Text.Encoding]::UTF8.GetBytes($message)
$secretBytes = [System.Text.Encoding]::UTF8.GetBytes($secret)

$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = $secretBytes
$signature = [System.Convert]::ToHexString($hmac.ComputeHash($bytes))

@{
    timestamp = $timestamp
    signature = $signature
    message = $message
}
```

### 2. Test TrackOrder Query

```powershell
# Test API Key
$apiKey = "test_key_dev_123"
$secret = "test_secret_dev_456"

# GraphQL Query
$query = '{ trackOrder(orderCode: "WH-001") { id orderCode status } }'
$variables = "{}"

# Generate signature
$timestamp = [Math]::Floor([double](Get-Date -UFormat %s))
$message = $query + $variables + $timestamp
$bytes = [System.Text.Encoding]::UTF8.GetBytes($message)
$secretBytes = [System.Text.Encoding]::UTF8.GetBytes($secret)

$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = $secretBytes
$signature = [System.Convert]::ToHexString($hmac.ComputeHash($bytes)).ToLower()

# Make request
$headers = @{
    "Content-Type" = "application/json"
    "X-API-Key" = $apiKey
    "X-Timestamp" = $timestamp
    "X-Signature" = $signature
}

$body = @{
    query = $query
    variables = $variables
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri "http://localhost:8000/graphql" `
    -Method POST `
    -Headers $headers `
    -Body $body

Write-Host $response.Content | ConvertFrom-Json | ConvertTo-Json
```

### 3. Test RequestRestock Mutation

```powershell
$apiKey = "electromart_api_key_2024"
$secret = "shared_secret_with_toko_12345"

# GraphQL Mutation
$query = 'mutation { requestRestock(input: {storeId: "STORE-001", items: [{productCode: "PROD-001", quantity: 5}]}) { success orderId message } }'
$variables = "{}"

# Generate signature
$timestamp = [Math]::Floor([double](Get-Date -UFormat %s))
$message = $query + $variables + $timestamp
$bytes = [System.Text.Encoding]::UTF8.GetBytes($message)
$secretBytes = [System.Text.Encoding]::UTF8.GetBytes($secret)

$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = $secretBytes
$signature = [System.Convert]::ToHexString($hmac.ComputeHash($bytes)).ToLower()

# Make request
$headers = @{
    "Content-Type" = "application/json"
    "X-API-Key" = $apiKey
    "X-Timestamp" = $timestamp
    "X-Signature" = $signature
}

$body = @{
    query = $query
    variables = $variables
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/graphql" `
    -Method POST `
    -Headers $headers `
    -Body $body | Select-Object -ExpandProperty Content
```

---

## GraphQL Playground Usage

1. Open browser to: **http://localhost:8000/graphql**
2. Click the "Docs" button on right side to see schema
3. In the headers area (bottom left), add:
   ```json
   {
     "X-API-Key": "test_key_dev_123",
     "X-Timestamp": "1704628800",
     "X-Signature": "[your-hmac-signature]"
   }
   ```
4. Try running queries/mutations

---

## Available Test API Keys

| Name                    | Key                        | Secret                          |
| ----------------------- | -------------------------- | ------------------------------- |
| Toko ElectroMart        | `electromart_api_key_2024` | `shared_secret_with_toko_12345` |
| Toko Elektronik Central | `central_api_key_2024`     | `shared_secret_central_67890`   |
| Test Store Dev          | `test_key_dev_123`         | `test_secret_dev_456`           |

---

## Sample Responses

### TrackOrder Response (Success)

```json
{
  "data": {
    "trackOrder": {
      "id": 1,
      "orderCode": "WH-20260107-ABC123",
      "status": "MENUNGGU",
      "events": [
        {
          "timestamp": "2026-01-07T22:30:00Z",
          "description": "Restock request received",
          "status": "MENUNGGU"
        }
      ],
      "estimatedDelivery": "2026-01-10"
    }
  }
}
```

### RequestRestock Response (Success)

```json
{
  "data": {
    "requestRestock": {
      "success": true,
      "orderId": "WH-20260107-XYZ789",
      "message": "Order created successfully",
      "processedItems": [
        {
          "productCode": "PROD-001",
          "quantity": 5,
          "status": "RESERVED"
        }
      ],
      "failedItems": []
    }
  }
}
```

---

## Error Examples

### Missing API Key

```json
{
  "errors": [
    {
      "message": "Missing authentication headers",
      "extensions": {
        "message": "X-API-Key, X-Signature, and X-Timestamp headers are required"
      }
    }
  ]
}
```

### Invalid Signature

```json
{
  "errors": [
    {
      "message": "Invalid signature",
      "extensions": {
        "message": "HMAC signature verification failed"
      }
    }
  ]
}
```

### Order Not Found

```json
{
  "errors": [
    {
      "message": "Order not found"
    }
  ]
}
```

---

## Troubleshooting

| Issue                      | Solution                                                   |
| -------------------------- | ---------------------------------------------------------- |
| "Cannot connect to server" | Make sure Laravel server is running on port 8000           |
| "Invalid signature"        | Check that timestamp is current (within 5 minutes)         |
| "Unknown API key"          | Use one of the test keys from the table above              |
| "Missing headers"          | Add all three headers: X-API-Key, X-Timestamp, X-Signature |
| "Order not found"          | Create an order first using requestRestock mutation        |

---

## Next Steps

1. ✅ Verify Laravel server is running
2. ✅ Test one of the sample requests above
3. ✅ Check database for created records
4. ✅ Integrate with Stock Service
5. ✅ Deploy to Docker when ready
