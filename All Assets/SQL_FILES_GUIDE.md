# 📊 SQL FILES GUIDE - Organisation & Utilisation

**Date:** 21 décembre 2025  
**Statut:** ✅ Consolidés et organisés

---

## 🎯 Vue d'ensemble

Les fichiers SQL ont été consolidés et organisés pour clarté et maintenabilité.

### Résumé
```
AVANT: 8 fichiers SQL
APRÈS: 5 fichiers SQL (-38%)

Redondance: 25% → 0%
```

---

## 📁 Fichiers SQL finaux

### **1. database.sql** (246.7 KB)
**Rôle:** Schéma principal et source de données

**Contenu:**
- Schéma complet de la base de données
- Toutes les tables
- Index et clés étrangères
- Données de base

**Utilisation:**
```sql
mysql> source database.sql;
```

**Cas d'usage:**
- Installation initiale
- Restauration complète de la BD
- Sauvegarde/backup

---

### **2. procedures.sql** (26.2 KB) ✨ NOUVEAU
**Rôle:** Toutes les procédures stockées

**Contenu (fusionné):**
- Procédures personnalisées (gestion utilisateurs)
  - GetAllCustomUsers
  - GetAllConsigneesWithBLs
  - UpdateCustomUserStatus
  - UpdateCustomUserInfo
  - UpdateCustomUserThirdPartyCodes
- Procédures standards (historique, détails BL, etc.)
  - GetUserBLHistory
  - GetUserBLPerNumber
  - Et 10+ autres...

**Utilisation:**
```sql
mysql> source procedures.sql;
```

**Cas d'usage:**
- Créer/recréer les procédures
- Mettre à jour la logique métier
- Exécution après database.sql

---

### **3. data-sample.sql** (17.2 KB)
**Rôle:** Données de test et exemple

**Contenu:**
- Données fictives
- Basées sur les structures réelles
- Pour développement et test

**Utilisation:**
```sql
mysql> source data-sample.sql;
```

**Cas d'usage:**
- Environnement de développement
- Tests fonctionnels
- Démonstration

---

### **4. data-import.sql** (82.6 KB)
**Rôle:** Données de production

**Contenu:**
- Données d'importation depuis Excel
- IPAKI SAMPLE DATA
- Tables: yarditemtype, eventfamily, eventtype, etc.

**Utilisation:**
```sql
mysql> source data-import.sql;
```

**Cas d'usage:**
- Environnement de production
- Données réelles d'importation
- Initialisation avec données complètes

---

### **5. maintenance.sql** (4.7 KB)
**Rôle:** Scripts de correction et optimisation

**Contenu:**
- Correction de l'auto-incrémentation
- Optimisation des index
- Nettoyage des données

**Utilisation:**
```sql
mysql> source maintenance.sql;
```

**Cas d'usage:**
- En cas de problème
- Après suppression massive
- Optimisation régulière
- Correction des sauts d'ID

---

## 🚀 Guide d'installation complète

### Étape 1: Structure
```bash
# Créer la structure BD
mysql -u root -p ies < database.sql
```

### Étape 2: Procédures
```bash
# Créer les procédures
mysql -u root -p ies < procedures.sql
```

### Étape 3: Données (choisir une option)

**Option A - Production:**
```bash
mysql -u root -p ies < data-import.sql
```

**Option B - Test/Développement:**
```bash
mysql -u root -p ies < data-sample.sql
```

### Étape 4: Maintenance (optionnel)
```bash
# En cas de besoin
mysql -u root -p ies < maintenance.sql
```

---

## 📋 Ordre d'exécution recommandé

```
1. database.sql      ← D'abord (crée les tables)
2. procedures.sql    ← Après (ajoute les procédures)
3. data-*.sql        ← Ensuite (remplir les données)
4. maintenance.sql   ← Si besoin (correction)
```

---

## 📊 Statistiques des fichiers

| Fichier | Taille | Lignes | Type | Utilité |
|---------|--------|--------|------|---------|
| database.sql | 246.7 KB | 6966 | Schema | Essentiel |
| procedures.sql | 26.2 KB | 600 | Procedures | Essentiel |
| data-import.sql | 82.6 KB | 1651 | Data | Production |
| data-sample.sql | 17.2 KB | 454 | Data | Test |
| maintenance.sql | 4.7 KB | 117 | Utilities | Optional |

**Total:** 377.4 KB

---

## ❌ Fichiers supprimés

| Fichier | Raison |
|---------|--------|
| ies bkp.sql | Backup (redondant avec database.sql) |
| ALL_TABLES.sql | Schéma minimal (déjà dans database.sql) |
| STORED_PROCEDURES_CUSTOM.sql | Fusionné dans procedures.sql |
| STORED_PROCEDURES.sql | Fusionné dans procedures.sql |
| SAMPLE_DATA.sql | Renommé en data-sample.sql |
| import_data.sql | Renommé en data-import.sql |

---

## 🔄 Cas d'utilisation courants

### Installation neuve
```bash
1. database.sql
2. procedures.sql
3. data-import.sql (ou data-sample.sql)
```

### Recréer les procédures
```bash
mysql> source procedures.sql;
```

### Vérifier l'intégrité
```bash
mysql> source maintenance.sql;
```

### Restaurer depuis backup
```bash
1. database.sql
2. procedures.sql
```

### Tester avec données fictives
```bash
1. database.sql
2. procedures.sql
3. data-sample.sql
```

---

## 🎓 Structure de chaque fichier SQL

### database.sql
```sql
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- Configuration
-- Procédures existantes (pour compatibilité)
-- Tables
-- Index
-- Données historiques

COMMIT;
```

### procedures.sql
```sql
DELIMITER $$

-- SECTION 1: PROCÉDURES PERSONNALISÉES
-- GetAllCustomUsers, UpdateCustomUserStatus, etc.

-- SECTION 2: PROCÉDURES STANDARDS
-- GetUserBLHistory, GetUserBLPerNumber, etc.

DELIMITER ;
```

### data-*.sql
```sql
SET FOREIGN_KEY_CHECKS=0;

-- Nettoyage (DELETE)
-- Insertion de données

SET FOREIGN_KEY_CHECKS=1;
```

---

## ✅ Vérification post-consolidation

### Procédures créées
```sql
SHOW PROCEDURES;
```

### Tables créées
```sql
SHOW TABLES;
```

### Vérifier les données
```sql
SELECT COUNT(*) FROM cart;
SELECT COUNT(*) FROM thirdparty;
```

---

## 📝 Notes importantes

- **database.sql** contient la source complète de la BD
- **procedures.sql** fusionne toutes les procédures (gain de maintenabilité)
- **data-sample.sql** et **data-import.sql** sont séparés pour flexibilité
- **maintenance.sql** est optionnel et pour correction
- Tous les fichiers sont UTF-8 et compatible MySQL 5.7+

---

## 🔐 Sécurité

### Avant exécution:
- ✅ Sauvegarder la BD
- ✅ Vérifier les permissions
- ✅ Vérifier la connexion MySQL

### Pendant l'exécution:
- ✅ Pas d'interruption
- ✅ Vérifier les messages d'erreur
- ✅ Noter les avertissements

### Après l'exécution:
- ✅ Vérifier l'intégrité
- ✅ Tester les procédures
- ✅ Valider les données

---

## 📞 Support

Pour des questions sur:
- **Installation:** Consultez le guide ci-dessus
- **Erreurs MySQL:** Vérifiez la version MySQL (5.7+)
- **Procédures:** Vérifiez procedures.sql
- **Données:** Vérifiez data-*.sql

---

**Consolidation SQL terminée:** ✨ 21 décembre 2025
