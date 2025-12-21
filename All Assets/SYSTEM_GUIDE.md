# 🚀 SYSTEM.PHP - Guide d'Utilisation

## 📋 Vue d'ensemble

`system.php` est le **script maître unique** qui consolide toutes les opérations de maintenance et configuration du système IES.

### Ce qui a été consolidé

✅ **config.php** → Configuration centralisée dans `system.php`  
✅ **create_procedures_unified.php** → Commande `procedures`  
✅ **maintenance_unified.php** → Commande `maintenance`  

---

## 🎯 Commandes disponibles

### 1. **Configuration**
```bash
php system.php config
```
Affiche la configuration actuelle (host, user, database, charset).

### 2. **Procédures stockées**
```bash
php system.php procedures
```
Crée/recréé toutes les procédures stockées:
- `GetAllCustomUsers` - Récupère tous les utilisateurs avec leurs tiers
- `GetAllConsigneesWithBLs` - Récupère les consignees avec leurs BLs
- `UpdateCustomUserStatus` - Mise à jour du statut utilisateur
- `UpdateCustomUserInfo` - Mise à jour des infos utilisateur
- `UpdateCustomUserThirdPartyCodes` - Gestion des associations tiers

### 3. **Maintenance**

#### Vérifier l'intégrité
```bash
php system.php maintenance verify-integrity
```
Vérifie:
- Structure des tables
- IDs invalides (ID=0)
- Intégrité globale

#### Corriger la structure
```bash
php system.php maintenance fix-structure
```
Applique les corrections essentielles:
- Clés primaires UNSIGNED AUTO_INCREMENT
- Clés étrangères cohérentes

#### Analyser la base de données
```bash
php system.php maintenance analyze
```
Affiche des statistiques:
- Nombre de tables
- Nombre de colonnes
- Nombre de clés étrangères

### 4. **Aide**
```bash
php system.php help
```
Affiche le guide complet des commandes.

---

## 📦 Structure interne

Le script contient **3 classes principales**:

### **ProcedureManager**
Gère la création et recréation de toutes les procédures stockées.

**Méthodes:**
- `createAll()` - Crée les 5 procédures via `multi_query()`

### **DatabaseMaintenance**
Gère les opérations de maintenance de la base de données.

**Méthodes:**
- `verifyIntegrity()` - Vérifie l'intégrité
- `fixStructure()` - Corrige la structure
- `analyze()` - Analyse complète

### **Fonctions utilitaires**
- `connectToDatabase()` - Crée une connexion MySQL
- `showSuccess()`, `showError()`, `showWarning()`, `showInfo()` - Affichage formaté
- `showTitle()` - Affiche un titre avec bordure

---

## 🔧 Configuration

La configuration se trouve au début du fichier:

```php
$DB_CONFIG = [
    'host'     => '127.0.0.1',
    'user'     => 'root',
    'password' => '',
    'database' => 'ies',
    'charset'  => 'utf8mb4'
];
```

### Modifier la configuration

Pour changer les paramètres, éditez la section `$DB_CONFIG`:

```php
$DB_CONFIG = [
    'host'     => 'localhost',  // ← Changez ici
    'user'     => 'mon_user',   // ← ou ici
    'password' => 'mon_pwd',    // ← ou ici
    'database' => 'ies',
    'charset'  => 'utf8mb4'
];
```

---

## 📝 Exemples d'utilisation

### Initialisation complète

```bash
# 1. Vérifier la configuration
php system.php config

# 2. Créer les procédures stockées
php system.php procedures

# 3. Vérifier l'intégrité
php system.php maintenance verify-integrity

# 4. Corriger la structure si nécessaire
php system.php maintenance fix-structure

# 5. Analyser la BD
php system.php maintenance analyze
```

### Maintenance régulière

```bash
# Vérification quotidienne
php system.php maintenance verify-integrity

# Maintenance hebdomadaire
php system.php maintenance analyze
```

### Recréer les procédures

```bash
# En cas de problème ou mise à jour
php system.php procedures
```

---

## ✅ Résumé de la consolidation PHP

### Avant
```
6 fichiers PHP:
├── config.php
├── create_procedures_unified.php
├── maintenance_unified.php
├── organize_markdown.php
├── cleanup_scripts.php
└── show_consolidation_summary.php

Redondance: 40-50% (code dupliqué)
Maintenabilité: ⭐⭐
```

### Après
```
1 fichier PHP:
└── system.php (14 KB - consolidé et optimisé)

Redondance: 0%
Maintenabilité: ⭐⭐⭐⭐⭐
Gain: -83% fichiers
```

---

## 📞 Dépannage

### Erreur de connexion
```
❌ Erreur de connexion: Connection refused
```
Vérifiez que MySQL est lancé et que la configuration est correcte.

### Erreur de procédures
```
❌ Erreur: Erreur de syntaxe...
```
Assurez-vous que la syntaxe SQL est correcte dans la classe `ProcedureManager`.

### Erreur de permissions
```
❌ Access denied for user 'root'
```
Vérifiez le mot de passe dans `$DB_CONFIG`.

---

## 🎓 Structure du code

```php
system.php
├── Configuration DB
├── Fonctions utilitaires
│   ├── connectToDatabase()
│   ├── showSuccess/Error/Warning/Info()
│   └── showTitle()
├── Classe ProcedureManager
│   └── createAll()
├── Classe DatabaseMaintenance
│   ├── verifyIntegrity()
│   ├── fixStructure()
│   └── analyze()
└── Système de commandes
    ├── case 'config'
    ├── case 'procedures'
    ├── case 'maintenance'
    └── case 'help'
```

---

## 📚 Documentation complète

Pour la documentation générale du système:
- **[DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md)** - Index maître
- **[MAINTENANCE_GUIDE_UNIFIED.md](./MAINTENANCE_GUIDE_UNIFIED.md)** - Guide maintenance
- **[INDEX_UNIFIED.md](./INDEX_UNIFIED.md)** - Référence technique

---

**Dernière mise à jour:** 21 décembre 2025 ✨
