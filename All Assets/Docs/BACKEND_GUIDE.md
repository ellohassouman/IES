# IES - DOCUMENTATION BACKEND

**Backend : API Laravel, MySQL, Procédures Stockées**  
**Date:** 28 Décembre 2025

---

## 💻 API Endpoints

**Base:** `http://localhost:8000/api` (Laravel 8+, PHP 7.4+)

### Authentication & Registration

#### Login
**POST** `/api/Login`
```json
{
  "email": "user@example.com",
  "password": "YourPassword123!"
}
→ {UserId, FullName, Email, PasswordHash}
```

**Description:** Authenticate user with email/password. Password verification uses bcrypt Hash::check().
**Stored Procedure:** `AuthenticateUser(p_Email)` - Retrieves user record by email

---

#### Register
**POST** `/api/Register`
```json
{
  "email": "newuser@example.com",
  "password": "NewPassword123!",
  "firstName": "John",
  "lastName": "Doe",
  "companyName": "Acme Corp",
  "companyAddress": "123 Business St",
  "phoneNumber": "+1234567890",
  "roleId": "113"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Enregistrement réussi. Un email de confirmation a été envoyé.",
  "userId": 42
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Cet email est déjà enregistré"
}
```

**Validation Rules:**
- Email must be unique and valid format
- Password minimum 12 characters
- Password must contain 3 types: lowercase, uppercase, numbers, special chars
- All text fields (firstName, lastName, companyName, companyAddress) required
- PhoneNumber optional
- RoleId: 113=Client, 114=Client TMS

**Stored Procedure:** `SP_RegisterUser(...)` - Validates data & creates new user with bcrypt hashed password

---

### GenerateProforma
**POST** `/api/GenerateProforma`
```json
{"billOfLadingId": 792416, "yardItemIds": [1,2,3], "taxRate": 20}
→ {amountHT, taxAmount, amountTTC}
```

### GenerateProformaWithBillingDate
**POST** `/api/GenerateProformaWithBillingDate`
```json
{...previous + "billingDate": "2025-12-27"}
→ {invoiceId, invoiceLabel, status: 'draft'}
```

### AddYardItemEvent
**POST** `/api/AddYardItemEvent`
```json
{"invoiceId": 12345, "yardItemIds": [1,2], "amount": 250}
```

### User Endpoints
`GetAllCustomUsers`, `UpdateCustomUserStatus`, `DeleteCustomUser`, etc.

---

## 🗄️ Procédures Stockées MySQL

| Procédure | Description |
|-----------|-------------|
| `AuthenticateUser(p_Email)` | Récupère user pour login |
| `SP_RegisterUser(...)` | Enregistre nouvel utilisateur |
| `CalculateProformaAmount` | Calcule HT/TVA/TTC |
| `CreateProformaInvoice` | Crée facture (draft) |
| `GetAllCustomUsers` | Récupère utilisateurs |
| `UpdateCustomUserStatus` | Change statut user |
| `DeleteCustomUser` | Soft delete (Status=5) |

### SP_RegisterUser Details
**Signature:**
```sql
PROCEDURE SP_RegisterUser (
    IN p_UserName VARCHAR(512),          -- Email
    IN p_PasswordHash VARCHAR(2000),     -- Bcrypt hash
    IN p_FirstName VARCHAR(2000),
    IN p_LastName VARCHAR(2000),
    IN p_CompanyName VARCHAR(2000),
    IN p_CompanyAddress VARCHAR(2000),
    IN p_PhoneNumber VARCHAR(100),
    IN p_CustomerUsersTypeId INT         -- 3=Client, 4=Client TMS
)
```

**Logic:**
1. Validate email uniqueness
2. Validate all required fields present
3. Insert into `customerusers` table
4. Set status=1 (Pending User Confirmation)
5. Return success with new UserID

**Database Table:** `customerusers`
- Id (auto-increment primary key)
- UserName (unique email)
- PasswordHash (bcrypt 2000 chars)
- FirstName, LastName, CompanyName, CompanyAddress, PhoneNumber
- EmailConfirmed (0=pending, 1=confirmed)
- CustomerUsersStatusId (1=Pending, 2=Approved, 3=Active, 4=Disabled, 5=Deleted)
- CustomerUsersTypeId (3=Client, 4=Client TMS)

---

## 💾 Base de Données

**Config:** MySQL 8.0.27 @ 127.0.0.1:3306
- User: `root` (password: vide)
- Database: `ies` (UTF8MB4)
- Tables: 45 | FK: 41 | Procédures: 8

**Installation ordre:**
```bash
mysql -u root -p ies < database.sql
mysql -u root -p ies < procedures.sql
mysql -u root -p ies < data-import.sql
```

---

## 🛠️ Maintenance

```bash
# Backend
cd Backend && php artisan serve

# Maintenance system
cd "All Assets/PhpScript"
php UNIFIED_SYSTEM.php config                    # Voir config
php UNIFIED_SYSTEM.php procedures list           # Lister procédures
php UNIFIED_SYSTEM.php relationships             # Créer FK
php UNIFIED_SYSTEM.php diagnostic integrity      # Vérifier intégrité
php UNIFIED_SYSTEM.php menu                      # Mode interactif
```

---

## 🚀 Déploiement

**Backup BD:**
```bash
mysqldump -u root -p ies > backup_ies_$(date +%Y%m%d_%H%M%S).sql
```

**Test API:**
```bash
curl -X POST http://localhost:8000/api/GenerateProforma \
  -H 'Content-Type: application/json' \
  -d '{"billOfLadingId": 792416, ...}'
```

**Post-deploy:**
```bash
php artisan route:list | grep api
php artisan cache:clear
tail -f storage/logs/laravel.log
```

---

## ⚠️ Troubleshooting

| Problème | Solution |
|----------|----------|
| MySQL refused | Vérifier MySQL lancé + config |
| Route 404 | `php artisan route:list \| grep api` |
| FK constraint | `php UNIFIED_SYSTEM.php validate-relationships` |
| Procedure failed | `php UNIFIED_SYSTEM.php procedures list` |
| CORS blocked | Vérifier config/cors.php Backend |
| Slow API | Vérifier indexes + EXPLAIN query |

---

## ✅ Essentials

- **Backup toujours avant corrections**
- Charset UTF-8 MB4 (ne pas changer)
- FK mode ON obligatoire
- User MySQL doit créer/modifier procédures

---

**IES Backend - 28 Décembre 2025**
