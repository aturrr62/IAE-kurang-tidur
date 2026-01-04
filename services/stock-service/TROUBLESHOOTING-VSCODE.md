# 🔧 Troubleshooting VSCode Errors

## ⚠️ Folder Merah / Model Kuning di VSCode

Jika Anda melihat **folder mutations berwarna merah** atau **model Inventory berwarna kuning** di VSCode, ini biasanya karena PHP Language Server (Intelephense/PHPStan) belum mengenali class-class baru.

### ✅ Solusi Cepat

#### **1. Regenerate Composer Autoload**

**Windows (PowerShell/CMD):**
```powershell
cd services/stock-service
composer dump-autoload
```

**Linux/Mac:**
```bash
cd services/stock-service
composer dump-autoload
```

Atau jalankan script yang sudah disediakan:
```powershell
# Windows
.\regenerate-autoload.bat

# Linux/Mac
bash regenerate-autoload.sh
```

#### **2. Restart PHP Language Server**

Di VSCode:
1. Tekan `Ctrl+Shift+P` (Windows/Linux) atau `Cmd+Shift+P` (Mac)
2. Ketik: `PHP: Restart PHP Language Server`
3. Enter

Atau:
1. Tekan `Ctrl+Shift+P`
2. Ketik: `Developer: Reload Window`
3. Enter

#### **3. Clear Laravel Cache**

```bash
cd services/stock-service
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### **4. Restart VSCode**

Kadang cukup restart VSCode saja:
- Close VSCode completely
- Reopen workspace

---

## 🐛 Common VSCode PHP Errors

### Error: "Class not found" atau "Undefined type"

**Penyebab:**
- Composer autoload belum updated
- PHP extension (Intelephense) belum scan file baru

**Solusi:**
```bash
composer dump-autoload
# Kemudian restart PHP Language Server di VSCode
```

### Error: "Namespace not found"

**Penyebab:**
- Typo di namespace
- File di folder yang salah

**Cek:**
1. File `app/Models/Inventory.php` → namespace harus `App\Models`
2. File `app/GraphQL/Mutations/StockMutation.php` → namespace harus `App\GraphQL\Mutations`
3. File `app/Services/JwtService.php` → namespace harus `App\Services`

### Error: "Method not found on model"

**Penyebab:**
- Model belum extend `Illuminate\Database\Eloquent\Model`
- Method custom belum didefinisikan

**Cek:**
```php
// Di setiap model file, pastikan ada:
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    // ...
}
```

---

## 📝 Verifikasi File Structure

Pastikan struktur folder benar:

```
app/
├── GraphQL/
│   ├── Mutations/
│   │   ├── AuthMutation.php ✅
│   │   ├── StaffMutation.php ✅
│   │   └── StockMutation.php ✅
│   └── Queries/
│       ├── AuthQuery.php ✅
│       ├── StaffQuery.php ✅
│       └── StockQuery.php ✅
├── Http/
│   └── Middleware/
│       ├── JwtAuthentication.php ✅
│       └── ApiKeyAuthentication.php ✅
├── Models/
│   ├── Inventory.php ✅
│   ├── JwtBlacklist.php ✅
│   ├── StockAlert.php ✅
│   ├── StockTransaction.php ✅
│   ├── WarehouseStaff.php ✅
│   └── User.php (default Laravel)
└── Services/
    └── JwtService.php ✅
```

---

## 🔍 Check Namespace di Setiap File

### Models
```php
// app/Models/Inventory.php
namespace App\Models;

// app/Models/WarehouseStaff.php
namespace App\Models;

// dll...
```

### GraphQL Mutations
```php
// app/GraphQL/Mutations/AuthMutation.php
namespace App\GraphQL\Mutations;

// app/GraphQL/Mutations/StaffMutation.php
namespace App\GraphQL\Mutations;

// app/GraphQL/Mutations/StockMutation.php
namespace App\GraphQL\Mutations;
```

### GraphQL Queries
```php
// app/GraphQL/Queries/AuthQuery.php
namespace App\GraphQL\Queries;

// dll...
```

### Services
```php
// app/Services/JwtService.php
namespace App\Services;
```

---

## 🛠️ Fix Specific Errors

### "Class 'App\Models\Inventory' not found"

1. Cek file ada: `app/Models/Inventory.php`
2. Cek namespace di file tersebut: `namespace App\Models;`
3. Cek class name: `class Inventory extends Model`
4. Run: `composer dump-autoload`

### "Class 'App\Services\JwtService' not found"

1. Cek file ada: `app/Services/JwtService.php`
2. Cek namespace: `namespace App\Services;`
3. Pastikan folder `app/Services` exists
4. Run: `composer dump-autoload`

### "Class 'Firebase\JWT\JWT' not found"

```bash
composer require firebase/php-jwt
```

### Import statements kuning/warning

**Penyebab:** Class diimport tapi tidak digunakan, atau sebaliknya

**Solusi:**
- Jika kuning tapi tidak error: ignore saja (warning only)
- Atau gunakan extension "PHP Namespace Resolver" untuk auto-fix

---

## 🎯 Ultimate Fix (Nuclear Option)

Jika semua cara di atas tidak berhasil:

```bash
# 1. Clear everything
cd services/stock-service
rm -rf vendor/
rm composer.lock

# 2. Reinstall dependencies
composer install

# 3. Regenerate autoload
composer dump-autoload

# 4. Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 5. Restart VSCode
# Close VSCode → Reopen
```

---

## 📦 VSCode Extensions Recommended

Install extensions berikut untuk PHP development:

1. **PHP Intelephense** (bmewburn.vscode-intelephense-client)
   - Autocomplete, go to definition, dll
   
2. **PHP Namespace Resolver** (MehediDracula.php-namespace-resolver)
   - Auto import classes
   
3. **Laravel Extension Pack** (onecentlin.laravel-extension-pack)
   - Laravel snippets & helpers

4. **PHP DocBlocker** (neilbrayfield.php-docblocker)
   - Generate docblocks

---

## ⚙️ VSCode Settings untuk PHP

Buat file `.vscode/settings.json` di root project:

```json
{
  "php.suggest.basic": false,
  "intelephense.stubs": [
    "apache",
    "bcmath",
    "Core",
    "ctype",
    "curl",
    "date",
    "dom",
    "fileinfo",
    "filter",
    "ftp",
    "gd",
    "gettext",
    "hash",
    "iconv",
    "json",
    "libxml",
    "mbstring",
    "mysqli",
    "mysqlnd",
    "openssl",
    "pcre",
    "PDO",
    "pdo_mysql",
    "Phar",
    "readline",
    "Reflection",
    "session",
    "SimpleXML",
    "sodium",
    "SPL",
    "standard",
    "tokenizer",
    "xml",
    "xmlreader",
    "xmlwriter",
    "zip",
    "zlib"
  ],
  "intelephense.files.maxSize": 5000000,
  "files.associations": {
    "*.php": "php",
    ".env.example": "dotenv"
  },
  "php.validate.enable": true,
  "php.validate.run": "onType"
}
```

---

## ✅ Verifikasi Semua OK

Setelah melakukan fix, cek:

1. ✅ **No red squiggly lines** di file PHP
2. ✅ **Auto-complete works** saat ketik class name
3. ✅ **Go to definition works** (Ctrl+Click pada class name)
4. ✅ **No error di Problems panel** (Ctrl+Shift+M)

---

## 💡 Tips Tambahan

### Exclude vendor dari PHP analysis
Agar VSCode tidak lag scan semua vendor:

```json
// .vscode/settings.json
{
  "files.exclude": {
    "**/vendor": true,
    "**/node_modules": true
  },
  "search.exclude": {
    "**/vendor": true,
    "**/node_modules": true
  }
}
```

### Use PHP CS Fixer
Install untuk auto-format code:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

---

## 🆘 Masih Error?

Jika masih ada error setelah semua cara di atas:

1. Screenshot error message
2. Cek file mana yang error (path lengkap)
3. Cek line number berapa
4. Copy paste error message lengkap

Kemudian analisa:
- Apakah syntax error? (missing semicolon, bracket, dll)
- Apakah namespace salah?
- Apakah class tidak ditemukan?
- Apakah method tidak ada?

---

**🎯 99% masalah "folder merah/kuning" solved dengan: `composer dump-autoload` + Restart VSCode!**
