# GraphQL API Test Script for Stock Service
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  STOCK SERVICE GRAPHQL API TEST" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# 1. Test Login
Write-Host "`n[1] Testing LOGIN..." -ForegroundColor Yellow
$loginQuery = '{"query":"mutation { login(username: \"admin\", password: \"admin123\") { token user { username role } expiresIn } }"}'
$loginResponse = Invoke-RestMethod -Uri "http://localhost:8003/graphql" -Method Post -Body $loginQuery -ContentType "application/json"

if ($loginResponse.data.login.token) {
    Write-Host "✓ Login SUCCESS" -ForegroundColor Green
    $token = $loginResponse.data.login.token
    Write-Host "  User: $($loginResponse.data.login.user.username)" -ForegroundColor Cyan
    Write-Host "  Role: $($loginResponse.data.login.user.role)" -ForegroundColor Cyan
    Write-Host "  Token: $($token.Substring(0,30))..." -ForegroundColor Gray
} else {
    Write-Host "✗ Login FAILED" -ForegroundColor Red
    $loginResponse.errors | ConvertTo-Json
    exit
}

# 2. Test Get Current User
Write-Host "`n[2] Testing GET CURRENT USER..." -ForegroundColor Yellow
$meQuery = '{"query":"query { me { id username name email role department } }"}'
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}
$meResponse = Invoke-RestMethod -Uri "http://localhost:8003/graphql" -Method Post -Body $meQuery -Headers $headers

if ($meResponse.data.me) {
    Write-Host "✓ Get Current User SUCCESS" -ForegroundColor Green
    Write-Host "  Name: $($meResponse.data.me.name)" -ForegroundColor Cyan
    Write-Host "  Email: $($meResponse.data.me.email)" -ForegroundColor Cyan
} else {
    Write-Host "✗ Get Current User FAILED" -ForegroundColor Red
}

# 3. Test Check Stock
Write-Host "`n[3] Testing CHECK STOCK..." -ForegroundColor Yellow
$checkQuery = @'
{"query":"query { checkStock(productCode: \"PROD001\", quantity: 5) { productCode available currentStock message } }"}
'@
$checkResponse = Invoke-RestMethod -Uri "http://localhost:8003/graphql" -Method Post -Body $checkQuery -Headers $headers

if ($checkResponse.data.checkStock) {
    Write-Host "✓ Check Stock SUCCESS" -ForegroundColor Green
    $stock = $checkResponse.data.checkStock
    Write-Host "  Product: $($stock.productCode)" -ForegroundColor Cyan
    Write-Host "  Available: $($stock.available)" -ForegroundColor Cyan
    Write-Host "  Current Stock: $($stock.currentStock)" -ForegroundColor Cyan
    Write-Host "  Message: $($stock.message)" -ForegroundColor Cyan
} else {
    Write-Host "✗ Check Stock FAILED" -ForegroundColor Red
    if ($checkResponse.errors) {
        $checkResponse.errors[0].extensions.debugMessage
    }
}

Write-Host "`n=====================================" -ForegroundColor Cyan
Write-Host "  ALL TESTS COMPLETED!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan

Write-Host "`n📝 CREDENTIALS FOR TESTING:" -ForegroundColor Yellow
Write-Host "  Username: admin | Password: admin123 (role: admin)" -ForegroundColor White
Write-Host "  Username: manager_inv | Password: manager123 (role: manager)" -ForegroundColor White
Write-Host "  Username: staff_ship | Password: staff123 (role: staff)" -ForegroundColor White
Write-Host "  Username: staff_inv | Password: staff123 (role: staff)" -ForegroundColor White

Write-Host "`n🌐 GraphQL Endpoints:" -ForegroundColor Yellow
Write-Host "  API: http://localhost:8003/graphql" -ForegroundColor White
Write-Host "  Playground: http://localhost:8003/graphql-playground" -ForegroundColor White
