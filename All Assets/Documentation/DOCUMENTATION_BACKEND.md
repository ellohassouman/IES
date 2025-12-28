# IES - DOCUMENTATION BACKEND

**Backend : API Laravel, MySQL, Procédures Stockées**  
**Date:** 28 Décembre 2025

---

## 💻 API Endpoints

**Base:** `http://localhost:8000/api` (Laravel 8+, PHP 7.4+)

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
| `CalculateProformaAmount` | Calcule HT/TVA/TTC |
| `CreateProformaInvoice` | Crée facture (draft) |
| `GetAllCustomUsers` | Récupère utilisateurs |
| `UpdateCustomUserStatus` | Change statut user |
| `DeleteCustomUser` | Soft delete (Status=5) |

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
