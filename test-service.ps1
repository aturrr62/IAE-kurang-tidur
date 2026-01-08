$baseUrl = "http://localhost:8000"
$graphqlUrl = "$baseUrl/graphql"

Write-Host "Testing Shipping Service GraphQL Endpoint"
Write-Host "==========================================="
Write-Host ""

# Test 1: Introspection Query
Write-Host "Test 1 - Schema Introspection"
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
        Write-Host "PASS - GraphQL endpoint is responding" -ForegroundColor Green
    } else {
        Write-Host "FAIL - Unexpected response" -ForegroundColor Red
    }
} catch {
    Write-Host "FAIL - Cannot connect to GraphQL endpoint" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

Write-Host ""
Write-Host "Test complete!"
