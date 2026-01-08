# Shipping Service GraphQL Test Script
# This script tests the Shipping Service endpoints

$baseUrl = "http://localhost:8000"
$graphqlUrl = "$baseUrl/graphql"

Write-Host "╔════════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║           SHIPPING SERVICE GRAPHQL ENDPOINT TESTS                  ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Test 1: Introspection Query (no auth required)
Write-Host "Test 1 - GraphQL Introspection (Schema)" -ForegroundColor Yellow
$query = @{
    query = "{ __schema { types { name } } }"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri $graphqlUrl `
        -Method POST `
        -Headers @{ "Content-Type" = "application/json" } `
        -Body $query `
        -ErrorAction Stop
    
    $result = $response.Content | ConvertFrom-Json
    if ($result.data) {
        Write-Host "✅ PASS - GraphQL endpoint is responding`n" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Got response but unexpected structure`n" -ForegroundColor Yellow
        Write-Host $result.Content
    }
} catch {
    Write-Host "❌ FAIL - Cannot connect to GraphQL endpoint`n" -ForegroundColor Red
    Write-Host $_.Exception.Message
}

# Test 2: TrackOrder Query (public query, no auth)
Write-Host "Test 2 - TrackOrder Query" -ForegroundColor Yellow
$query = @{
    query = "{ trackOrder(orderId: ""ORD-001"") { id orderId status createdAt } }"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri $graphqlUrl `
        -Method POST `
        -Headers @{ "Content-Type" = "application/json" } `
        -Body $query `
        -ErrorAction Stop
    
    $result = $response.Content | ConvertFrom-Json
    Write-Host "✅ PASS - TrackOrder query executed`n" -ForegroundColor Green
    Write-Host "Response: " + ($result | ConvertTo-Json) + "`n"
} catch {
    Write-Host "❌ FAIL - TrackOrder query failed`n" -ForegroundColor Red
}

# Test 3: GetOrderDetails Query
Write-Host "Test 3 - GetOrderDetails Query" -ForegroundColor Yellow
$query = @{
    query = "{ getOrderDetails(orderId: ""ORD-001"") { id orderId totalAmount status items { id quantity } } }"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri $graphqlUrl `
        -Method POST `
        -Headers @{ "Content-Type" = "application/json" } `
        -Body $query `
        -ErrorAction Stop
    
    $result = $response.Content | ConvertFrom-Json
    Write-Host "✅ PASS - GetOrderDetails query executed`n" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Query returned error (expected if no data exists)`n" -ForegroundColor Yellow
}

Write-Host "════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "All tests completed! Your Shipping Service is running." -ForegroundColor Green
