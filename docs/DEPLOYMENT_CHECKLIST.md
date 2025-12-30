# ✅ DEPLOYMENT CHECKLIST - Gudang Elektronik Microservices

## 📋 OVERVIEW
Document ini adalah checklist final sebelum deployment/submission repository Microservices Gudang Elektronik.

---

## 🚀 PRE-DEPLOYMENT CHECKLIST

### 1. DOCKER & INFRASTRUCTURE
- [ ] Docker Compose file valid (`docker-compose config`)
- [ ] All 4 MySQL containers configured (product, order, stock, shipping)
- [ ] All 4 service containers configured (product, order, stock, shipping)
- [ ] Port mapping correct:
  - [ ] Product Service: 8001
  - [ ] Order Service: 8002
  - [ ] Stock Service: 8003
  - [ ] Shipping Service: 8004
  - [ ] MySQL Product: 3306
  - [ ] MySQL Order: 3307
  - [ ] MySQL Stock: 3308
  - [ ] MySQL Shipping: 3309
- [ ] Volume persistence configured for all databases
- [ ] Network isolation configured (`iae-network`)

### 2. DATABASE MIGRATIONS
- [ ] Stock Service migrations:
  - [ ] `users` table (with username, role columns)
  - [ ] `inventory` table
  - [ ] All default Laravel migrations
- [ ] Shipping Service migrations:
  - [ ] `warehouse_orders` table (user_id nullable, no FK constraint)
  - [ ] `shipments` table
  - [ ] All default Laravel migrations
- [ ] Product & Order Service migrations intact
- [ ] All migrations tested with `php artisan migrate`
- [ ] All migrations can rollback without error

### 3. DATABASE SEEDING
- [ ] Stock Service seeders:
  - [ ] `UserSeeder` - 3 users (admin, staff, supervisor)
  - [ ] `InventorySeeder` - 10 electronic products
  - [ ] `DatabaseSeeder` calls both seeders in correct order
- [ ] All seeders tested with `php artisan db:seed`
- [ ] Sample data realistic and complete
- [ ] Timestamps (`created_at`, `updated_at`) populated

### 4. GRAPHQL SCHEMAS
- [ ] Stock Service schema:
  - [ ] User type defined
  - [ ] AuthResponse type defined
  - [ ] RegisterInput input defined
  - [ ] login mutation with resolver
  - [ ] register mutation with resolver
  - [ ] me query for current user
  - [ ] Inventory queries (checkStock, inventories)
  - [ ] Stock mutations (increaseStock, decreaseStock)
- [ ] Shipping Service schema:
  - [ ] External types (RestockRequestInput, RestockResponse, OrderTrackingStatus)
  - [ ] Internal types (WarehouseOrder, Shipment)
  - [ ] requestRestock mutation with resolver
  - [ ] trackOrder query with resolver
  - [ ] Internal warehouse mutations (approve, reject, create shipment)
- [ ] Product & Order Service schemas intact
- [ ] All schemas valid (no syntax errors)

### 5. AUTHENTICATION & SECURITY
- [ ] JWT Helper class created in Stock Service
- [ ] JWT Helper class created in Shipping Service
- [ ] AuthMutation class created in Stock Service
- [ ] JwtAuthMiddleware created in Shipping Service
- [ ] ApiKeyMiddleware created in Shipping Service
- [ ] JWT_SECRET same in Stock & Shipping .env
- [ ] API_SECRET_KEY configured in Shipping .env
- [ ] HMAC signature validation working
- [ ] Timestamp replay protection (5 min max) working
- [ ] No hardcoded secrets in code

### 6. EXTERNAL API ENDPOINTS (TOKO)
- [ ] `requestRestock` mutation functional:
  - [ ] Accepts API Key authentication
  - [ ] Validates HMAC signature
  - [ ] Checks stock via HTTP to Stock Service
  - [ ] Creates warehouse order
  - [ ] Returns orderCode and estimatedDelivery
- [ ] `trackOrder` query functional:
  - [ ] Accepts API Key authentication
  - [ ] Returns tracking events
  - [ ] Shows complete timeline

### 7. INTERNAL API ENDPOINTS (STAFF GUDANG)
- [ ] All endpoints require JWT authentication
- [ ] `warehouseOrders` query works
- [ ] `approveWarehouseOrder` mutation works
- [ ] `rejectWarehouseOrder` mutation works
- [ ] `createShipment` mutation works
- [ ] `updateShipmentStatus` mutation works
- [ ] Unauthorized requests return 401

### 8. ENVIRONMENT VARIABLES
- [ ] Stock Service `.env.example` complete:
  - [ ] JWT_SECRET
  - [ ] JWT_EXPIRATION
  - [ ] DB configuration
  - [ ] LIGHTHOUSE_CACHE_ENABLE
- [ ] Shipping Service `.env.example` complete:
  - [ ] JWT_SECRET (same as Stock)
  - [ ] API_SECRET_KEY
  - [ ] STOCK_SERVICE_URL
  - [ ] DB configuration
  - [ ] LIGHTHOUSE_CACHE_ENABLE
- [ ] Product & Order `.env.example` files exist
- [ ] No sensitive data in `.env.example` files
- [ ] All `.env` files in `.gitignore`

### 9. DEPENDENCIES
- [ ] `firebase/php-jwt` in Stock Service composer.json
- [ ] `firebase/php-jwt` in Shipping Service composer.json
- [ ] All Laravel dependencies up to date
- [ ] `composer.lock` files committed

---

## 📚 DOCUMENTATION CHECKLIST

### 10. README.md
- [ ] Architecture diagram included
- [ ] Security flow diagram included
- [ ] Tech stack documented
- [ ] Services overview complete
- [ ] Installation instructions step-by-step
- [ ] Database access info clear
- [ ] API endpoints documented:
  - [ ] Authentication endpoints
  - [ ] Stock management endpoints
  - [ ] External API (Toko) endpoints
  - [ ] Internal API (Staff) endpoints
- [ ] Request/Response examples provided
- [ ] Security implementation explained
- [ ] Database schema documented
- [ ] Testing scenarios overview
- [ ] Environment variables documented
- [ ] Academic narrative included
- [ ] Status template checklist complete

### 11. TESTING_SCENARIOS.md
- [ ] All test scenarios documented
- [ ] Authentication tests included
- [ ] Stock management tests included
- [ ] External API tests included
- [ ] Internal API tests included
- [ ] Integration flow tests included
- [ ] HMAC signature calculation example provided
- [ ] Common issues & solutions section
- [ ] Test report template provided

### 12. IMPLEMENTATION_PLAN.md
- [ ] Detailed phases documented
- [ ] Current vs proposal analysis
- [ ] Database fixes documented
- [ ] Auth implementation steps
- [ ] JWT verification steps
- [ ] API Key implementation steps
- [ ] GraphQL schema updates
- [ ] Seeding strategy
- [ ] Deliverables list complete

### 13. ADDITIONAL DOCUMENTATION
- [ ] GraphQL query examples in `docs/queries/examples.graphql`
- [ ] Postman collection prepared (optional)
- [ ] Sequence diagrams (optional)
- [ ] API versioning strategy (optional)

---

## 🧪 TESTING CHECKLIST

### 14. UNIT & INTEGRATION TESTS
- [ ] Can build all containers without error:
  ```bash
  docker-compose up --build -d
  ```
- [ ] All containers running:
  ```bash
  docker-compose ps
  ```
- [ ] Migrations successful:
  ```bash
  docker-compose exec stock-service php artisan migrate
  docker-compose exec shipping-service php artisan migrate
  ```
- [ ] Seeders successful:
  ```bash
  docker-compose exec stock-service php artisan migrate:fresh --seed
  ```
- [ ] GraphQL endpoints accessible:
  - [ ] http://localhost:8001/graphql (Product)
  - [ ] http://localhost:8002/graphql (Order)
  - [ ] http://localhost:8003/graphql (Stock)
  - [ ] http://localhost:8004/graphql (Shipping)

### 15. AUTHENTICATION TESTS
- [ ] Login with valid credentials ✅
- [ ] Login with invalid credentials ❌ (should fail)
- [ ] Register new user ✅
- [ ] Register duplicate email ❌ (should fail)
- [ ] Get current user with JWT ✅
- [ ] Access with invalid JWT ❌ (should fail)

### 16. STOCK TESTS
- [ ] Check stock by product code ✅
- [ ] Get all inventory (10 products) ✅
- [ ] Increase stock ✅
- [ ] Decrease stock ✅

### 17. EXTERNAL API TESTS (TOKO)
- [ ] Request restock with valid stock ✅
- [ ] Request restock - insufficient stock ❌ (should return success=false)
- [ ] Request restock - invalid HMAC ❌ (should 401)
- [ ] Request restock - expired timestamp ❌ (should 401)
- [ ] Track order - found ✅
- [ ] Track order - not found ❌ (should error)

### 18. INTERNAL API TESTS (STAFF)
- [ ] Get warehouse orders with JWT ✅
- [ ] Approve warehouse order ✅
- [ ] Reject warehouse order ✅
- [ ] Create shipment ✅
- [ ] Update shipment status ✅
- [ ] Access without JWT ❌ (should 401)

### 19. INTEGRATION FLOW TEST
- [ ] Complete flow works:
  1. Toko request restock (API Key)
  2. Staff login (JWT)
  3. Staff view orders
  4. Staff approve order
  5. Staff create shipment
  6. Toko track order (shows all events)

---

## 🧹 CODE QUALITY CHECKLIST

### 20. CODE ORGANIZATION
- [ ] All resolvers in `app/GraphQL/` directory
- [ ] All helpers in `app/Helpers/` directory
- [ ] All middlewares in `app/Http/Middleware/` directory
- [ ] All models in `app/Models/` directory
- [ ] No unused files or code
- [ ] No commented-out code blocks
- [ ] Consistent naming conventions

### 21. ERROR HANDLING
- [ ] All GraphQL errors return meaningful messages
- [ ] HTTP status codes correct (200, 401, 500)
- [ ] Validation errors descriptive
- [ ] No sensitive data in error messages
- [ ] All try-catch blocks in place

### 22. SECURITY
- [ ] No hardcoded passwords
- [ ] No hardcoded API keys
- [ ] JWT secret in .env only
- [ ] API secret in .env only
- [ ] No exposed internal endpoints
- [ ] CORS configured properly

### 23. PERFORMANCE
- [ ] No N+1 query problems
- [ ] Eager loading used where appropriate
- [ ] Database indexes on frequently queried columns
- [ ] Cache disabled in development (LIGHTHOUSE_CACHE_ENABLE=false)

---

## 📦 REPOSITORY CHECKLIST

### 24. GIT & REPOSITORY
- [ ] `.gitignore` includes:
  - [ ] `/vendor/`
  - [ ] `.env`
  - [ ] `/node_modules/`
  - [ ] `/storage/*.log`
  - [ ] `.DS_Store`
- [ ] All commits have meaningful messages
- [ ] No merge conflicts
- [ ] Branch up to date with main/master
- [ ] LICENSE file exists
- [ ] README.md at root level
- [ ] Directory structure clean

### 25. FILES TO INCLUDE
- [ ] `docker-compose.yml`
- [ ] `.env.example` for all services
- [ ] `README.md`
- [ ] `docs/TESTING_SCENARIOS.md`
- [ ] `docs/IMPLEMENTATION_PLAN.md` (via .agent/workflows)
- [ ] `docs/queries/examples.graphql`
- [ ] All migration files
- [ ] All seeder files
- [ ] All GraphQL schema files
- [ ] All resolver files
- [ ] All helper files
- [ ] All middleware files

### 26. FILES TO EXCLUDE
- [ ] `.env` files (real secrets)
- [ ] `/vendor/` directories
- [ ] `/node_modules/` directories
- [ ] Database files (*.sql, *.db)
- [ ] Log files
- [ ] IDE config files (.vscode, .idea)
- [ ] OS files (.DS_Store)

---

## 🎯 FINAL VALIDATION

### 27. SMOKE TEST
Run this final smoke test before submission:

```bash
# 1. Clean start
docker-compose down -v
docker-compose up --build -d

# 2. Wait for MySQL to be ready
sleep 20

# 3. Run migrations
docker-compose exec stock-service php artisan migrate:fresh --seed
docker-compose exec shipping-service php artisan migrate
docker-compose exec product-service php artisan migrate:fresh --seed
docker-compose exec order-service php artisan migrate

# 4. Install JWT library
docker-compose exec stock-service composer require firebase/php-jwt
docker-compose exec shipping-service composer require firebase/php-jwt

# 5. Test login
curl -X POST http://localhost:8003/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { login(email: \"admin@gudang.com\", password: \"password123\") { token user { username } } }"}'

# Expected: Should return JWT token + user data

# 6. Verify all endpoints accessible
curl http://localhost:8001/graphql  # Should return GraphQL response
curl http://localhost:8002/graphql  # Should return GraphQL response
curl http://localhost:8003/graphql  # Should return GraphQL response
curl http://localhost:8004/graphql  # Should return GraphQL response
```

### 28. ACADEMIC REQUIREMENTS
- [ ] Narasi laporan terpenuhi (in README)
- [ ] Penjelasan arsitektur microservices
- [ ] Penjelasan security mechanism (JWT + HMAC)
- [ ] Dijelaskan isolasi database
- [ ] Cross-service communication explained
- [ ] ERD compliance documented

### 29. PROPOSAL ALIGNMENT
- [ ] Stock Service has Auth module ✅
- [ ] JWT issued by Stock Service ✅
- [ ] Shipping Service verifies JWT ✅
- [ ] External endpoints use API Key + HMAC ✅
- [ ] Database structure matches ERD ✅
- [ ] 10 electronic products seeded ✅
- [ ] requestRestock endpoint exists ✅
- [ ] trackOrder endpoint exists ✅

---

## 📊 COMPLETION SUMMARY

### Total Checklist Items: 140+

Fill in before submission:
- [ ] All CRITICAL items completed (marked ⚠️)
- [ ] All HIGH priority items completed
- [ ] Documentation 100% complete
- [ ] All tests passing
- [ ] Repository clean and organized

### Sign-off:
```
Developer: _________________
Date: _____/_____/_____
Review Status: ☐ APPROVED  ☐ NEEDS REVISION
Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

---

## 🚀 DEPLOYMENT COMMAND

When all checklist items are complete:

```bash
# Push to GitHub
git add .
git commit -m "feat: Complete microservices template with Auth, JWT, and HMAC security"
git push origin main

# Create release tag
git tag -a v1.0.0 -m "Release v1.0.0 - Production-ready microservices template"
git push origin v1.0.0
```

---

## 📞 SUPPORT & CONTACTS

- **Team**: IAE - Kurang Tidur
- **Repository**: https://github.com/aturrr62/IAE-kurang-tidur
- **Documentation**: See README.md and docs/ folder
- **Issues**: Create issue on GitHub

---

**Last Updated**: 2025-12-30  
**Version**: 1.0  
**Status**: READY FOR DEPLOYMENT ✅
