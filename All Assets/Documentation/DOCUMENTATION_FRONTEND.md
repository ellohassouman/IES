# IES - DOCUMENTATION FRONTEND

**Frontend : Application Angular, Services et Composants**  
**Date:** 28 Décembre 2025

---

## 🔧 Services

### RequesterService - HTTP Communication
**Méthodes:**
- `AsyncPostResponse(endpoint, data)` : Promise
- `AsyncPostObservable(endpoint, data)` : Observable

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

### user-list.component
Tableau utilisateurs, modification, soft delete

### bill-of-lading-pending-invoicing.component
Sélection articles → Proforma → Facture

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
