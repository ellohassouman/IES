# IES - DOCUMENTATION FRONTEND

**Frontend : Application Angular, Services et Composants**  
**Date:** 28 Décembre 2025

---

## 🔧 Services

### RequesterService - HTTP Communication
**Méthodes:**
- `AsyncPostResponse(endpoint, data, ShowLoader, AutoCloseLoader, ShowSuccessMessage, message, headers, FromExternalApi, ShowErrorMessage)` : Promise<[success: 0|1, response: any]>
- `AsyncPostObservable(endpoint, data)` : Observable

**Base URL:** `http://127.0.0.1:8000/api/`

**Example:**
```typescript
const [isOk, response] = await this.requesterService.AsyncPostResponse(
  EnumEndPoint.Register,
  registrationData,
  true,        // Show loader
  true,        // Auto close loader
  false        // Show success message
);
```

### UserService - Gestion Utilisateurs
**Méthodes:**
- `getAllUsers()`, `updateUser(user)`, `deleteUser(id)`

### ProformaService - Facturation
**Méthodes:**
- `generateProforma(data)` : Aperçu
- `generateProformaWithBillingDate(data)` : Facture
- `addYardItemEvent(data)` : Événement

---

## 📱 Composants

### register.component
**Path:** `src/app/register/register.component.ts`

**Functionality:**
- User registration form with role selection
- Real-time validation (email format, password complexity, phone number, company name)
- File size validation (max 105 MB per file)
- Password strength verification:
  - Minimum 12 characters
  - Must contain 3 types: lowercase, uppercase, numbers, special characters

**Key Methods:**
- `onSubmit()` : Validates form & calls API Register endpoint
- `validatePasswordComplexity()` : Ensures password meets security requirements
- `initializePhoneNumberValidation()` : Restricts phone input to numbers & parentheses
- `initializeCompanyNameValidation()` : Allows alphanumeric & accented chars only

**Data Model:**
```typescript
{
  email: "user@example.com",
  password: "SecurePass123!",
  firstName: "John",
  lastName: "Doe",
  companyName: "Acme Corp",
  companyAddress: "123 Street",
  phoneNumber: "+1234567890",  // optional
  roleId: "113"  // 113=Client, 114=Client TMS
}
```

**Success Flow:**
1. Form validation passes
2. POST to `/api/Register` endpoint
3. SweetAlert shows success message with userId
4. User redirected to login page after confirmation

**Error Handling:**
- Duplicate email → "Cet email est déjà enregistré"
- Missing fields → "Veuillez remplir tous les champs obligatoires"
- Weak password → "Le mot de passe ne respecte pas les règles de complexité"
- Password mismatch → "Les mots de passe ne correspondent pas"
- Server error → "Erreur lors de la communication avec le serveur"

---

### user-list.component
Tableau utilisateurs, modification, soft delete

### bill-of-lading-pending-invoicing.component
Sélection articles → Proforma → Facture

---

## 👥 Workflow Enregistrement (Registration)

1. Click "Nouveau compte" button on login page
2. Select role (Client or Client TMS)
3. Fill registration form:
   - Email (unique, validated format)
   - Password (12+ chars, 3+ types)
   - Confirm Password (must match)
   - First Name
   - Last Name
   - Company Name
   - Company Address
   - Phone Number (optional)
4. Client-side validation:
   - Real-time field validation
   - Password complexity check
   - File size check (if docs required)
5. Click "Envoyer" button
6. Form submits to POST `/api/Register` endpoint
7. Backend validates & creates user record:
   - Hash password with bcrypt
   - Check email uniqueness
   - Insert into customerusers table
   - Set initial status = "Pending User Confirmation"
8. Success response redirects to login
9. User receives confirmation email (future feature)

---

## 👥 Workflow Facturation

1. Menu → Facturation → Facturation en Attente
2. Cocher articles → Clic "Générer Proforma"
3. POST `/api/GenerateProforma` → Backend calcule montants
4. Modal affiche HT/TVA/TTC
5. Saisir date (YYYY-MM-DD)
6. Clic "Générer" → POST `/api/GenerateProformaWithBillingDate`
7. Facture créée (status='draft')

---

## ⚙️ Installation & Configuration

```bash
cd Frontend
npm install
ng serve
# → http://localhost:4200
```

**environment.ts:**
```typescript
apiUrl: 'http://localhost:8000/api'
```

**environment.prod.ts:**
```typescript
apiUrl: 'https://api.ies.com/api'
```

---

## 🚀 Déploiement

```bash
ng build --configuration production
cp -r dist/frontend/* /var/www/html/ies/frontend/
```

**nginx config:**
```nginx
root /var/www/html/ies/frontend;
index index.html;
location / { try_files $uri $uri/ /index.html; }
```

---

## ⚠️ Troubleshooting

| Problème | Solution |
|----------|----------|
| App ne charge pas | Vérifier DevTools (F12) → Console |
| Erreur 404 API | Vérifier Backend lancé + apiUrl correct |
| CORS bloqué | Vérifier config/cors.php Backend |
| Datepicker cassé | Vérifier FormsModule importé |

---

## ✅ Requirements

- Node.js 14+
- npm 6+
- Angular CLI: `npm install -g @angular/cli`

**Production:** Build production + HTTPS + CSRF protection

---

**IES Frontend - 28 Décembre 2025**
