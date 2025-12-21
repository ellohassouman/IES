# 🎉 IES - Système d'Information Intégré

**Dernière mise à jour:** 21 décembre 2025

---

## 🚀 Démarrage rapide

### Frontend
```bash
cd Frontend
npm install
ng serve
```
Accédez à `http://localhost:4200`

### Backend
```bash
cd Backend
php artisan serve
```
Accédez à `http://localhost:8000`

### Maintenance système
```bash
cd "All Assets"
php system.php help
```

---

## 📚 Documentation

### 📖 Index maître
Consultez **[DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)** pour la navigation complète.

### 🔧 Système & Maintenance
- **[All Assets/README.md](All%20Assets/README.md)** - Index All Assets
- **[All Assets/SYSTEM_GUIDE.md](All%20Assets/SYSTEM_GUIDE.md)** - Guide system.php

### 💻 Frontend
- **[Frontend/README.md](Frontend/README.md)** - Guide Angular
- **[Frontend/docs/COMPONENTS.md](Frontend/docs/COMPONENTS.md)** - Documentation composants

### ⚙️ Backend
- **[Backend/README.md](Backend/README.md)** - Guide Laravel
- **[Backend/docs/API_ENDPOINTS.md](Backend/docs/API_ENDPOINTS.md)** - Documentation API

### 📊 Rapports
- **[CONSOLIDATION_REPORT_FINAL.md](CONSOLIDATION_REPORT_FINAL.md)** - Rapport final de consolidation

---

## 🗂️ Structure du projet

```
IES/
├── 📁 All Assets/          - Configuration, maintenance, SQL
├── 📁 Frontend/            - Application Angular
├── 📁 Backend/             - API Laravel
├── 📚 Documentation        - Fichiers principaux
└── 🔐 .git/                - Versioning
```

---

## 🎯 Fonctionnalités principales

### System (All Assets/)
```bash
php system.php config              # Configuration
php system.php procedures          # Créer procédures stockées
php system.php maintenance verify  # Vérifier l'intégrité
php system.php maintenance fix     # Corriger la structure
```

### Frontend
- ✅ Gestion des utilisateurs
- ✅ Liste de connaissements
- ✅ Factures et paiements
- ✅ Suivi des yard items

### Backend
- ✅ API REST complète
- ✅ Procédures stockées MySQL
- ✅ Authentification
- ✅ Gestion des données

---

## 📈 Statistiques

### Consolidation réalisée
```
Fichiers Markdown:  18 → 9  (-50%)
Fichiers PHP:        6 → 1  (-83%)
Redondance:       40% → 0% (-100%)
Maintenabilité:    ⭐⭐ → ⭐⭐⭐⭐⭐
```

---

## ✅ Checklist de démarrage

- [ ] Lire [DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)
- [ ] Installer les dépendances Frontend: `npm install`
- [ ] Installer les dépendances Backend: `composer install`
- [ ] Configurer la base de données
- [ ] Créer les procédures: `php system.php procedures`
- [ ] Vérifier l'intégrité: `php system.php maintenance verify-integrity`
- [ ] Lancer le Frontend: `ng serve`
- [ ] Lancer le Backend: `php artisan serve`

---

## 🔐 Configuration

### Database
À configurer dans `All Assets/system.php`:
```php
$DB_CONFIG = [
    'host'     => '127.0.0.1',
    'user'     => 'root',
    'password' => '',
    'database' => 'ies',
    'charset'  => 'utf8mb4'
];
```

### Environment (Backend)
À configurer dans `Backend/.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ies
DB_USERNAME=root
DB_PASSWORD=
```

### Environment (Frontend)
À configurer dans `Frontend/src/environments/environment.ts`:
```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api'
};
```

---

## 🛠️ Maintenance

### Vérifications régulières
```bash
# Quotidien
php system.php maintenance verify-integrity

# Hebdomadaire
php system.php maintenance analyze

# En cas de problème
php system.php maintenance fix-structure
```

---

## 📞 Support & Documentation

| Type | Fichier |
|------|---------|
| **Aide générale** | [DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md) |
| **Système & PHP** | [All Assets/SYSTEM_GUIDE.md](All%20Assets/SYSTEM_GUIDE.md) |
| **Frontend Angular** | [Frontend/README.md](Frontend/README.md) |
| **Backend Laravel** | [Backend/README.md](Backend/README.md) |
| **API** | [Backend/docs/API_ENDPOINTS.md](Backend/docs/API_ENDPOINTS.md) |
| **Composants** | [Frontend/docs/COMPONENTS.md](Frontend/docs/COMPONENTS.md) |
| **Rapport final** | [CONSOLIDATION_REPORT_FINAL.md](CONSOLIDATION_REPORT_FINAL.md) |

---

## 🎓 Ressources supplémentaires

- [Angular Documentation](https://angular.io/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [Bootstrap Documentation](https://getbootstrap.com/docs)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

## 📝 Licence & Informations

**Projet:** IES (Système d'Information Intégré)  
**Propriétaire:** EllasHassouman  
**Repository:** github.com/ellohassouman/IES  
**Branche:** main

---

**Bienvenue dans le système IES! 🚀**

Commencez par lire: **[DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)**
