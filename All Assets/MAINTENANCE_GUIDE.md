# 📚 MAINTENANCE GUIDE - Guide Complet de Maintenance

## 🔧 Scripts de Maintenance

### Core Operations
Les 2 scripts principaux centralisant toutes les opérations:

#### 1. **maintenance_core.php**
Consolidation de toutes les opérations critiques de maintenance.

```bash
# Menu d'aide
php maintenance_core.php

# Opérations individuelles:
php maintenance_core.php sync-eventtype      # Sync EventType depuis Excel
php maintenance_core.php dedup-blitem        # Déduplique BLItem-JobFile
php maintenance_core.php fix-dateclose       # Corrige DateClose
php maintenance_core.php optimize-procedures # Optimise procédures

# Exécuter tout
php maintenance_core.php all
```

**Inclut:**
- ✅ Synchronisation table EventType avec Excel (68 types)
- ✅ Déduplications BLItem-JobFile (1 jobfile par item)
- ✅ Correction DateClose (NULL pour en cours, timestamp pour complétés)
- ✅ Optimisation procédure GetYardItemTrackingMovements (INNER JOINs)

---

#### 2. **verify_integrity.php**
Vérification complète de l'intégrité des données.

```bash
# Menu d'aide
php verify_integrity.php

# Checks individuels:
php verify_integrity.php eventtype       # Vérifier EventType
php verify_integrity.php dateclose       # Vérifier DateClose
php verify_integrity.php cycles          # Vérifier cycles IN→OUT
php verify_integrity.php bl-items        # Vérifier relations BL-Items
php verify_integrity.php invoices        # Vérifier factures
php verify_integrity.php access-control  # Vérifier contrôle d'accès

# Tous les checks
php verify_integrity.php all
```

**Vérifie:**
- EventType: 68 types avec codes valides
- DateClose: Cohérence avec événements OUT
- Cycles: Tous les items ont IN→OUT
- Relations: BL-Items valides, pas d'orphelins
- Factures: Chaque BL a des factures
- Contrôle d'accès: 11 users × 15 tiers

---

### Scripts Spécialisés (Backups/Standalone)

Ces 4 scripts sont inclus dans `maintenance_core.php` mais conservés comme doubles de sécurité:

- **cleanup_blitem_jobfile.php** - Déduplique BLItem-JobFile
- **update_eventtype_from_excel.php** - Sync EventType
- **update_procedure_inner_join.php** - Optimise procédures
- **verify_dateclose.php** - Corrige DateClose

---

## 📊 État Actuel des Données

### Base de Données:
- **Événements**: 1,193 enregistrements
- **Jobfiles**: 229 cycles de vie (≤4 événements chacun)
- **BLItems**: 50 articles (1 jobfile par item)
- **Factures**: 48 BL × 5+ factures
- **Utilisateurs**: 11 (tous @yopmail.com)
- **Tiers**: 15 non-ShippingLines

### Contrôle d'Accès:
- 11 utilisateurs × 15 tiers = 165 relations
- Tous les utilisateurs ont accès à tous les tiers

---

## 🚀 Workflow Recommandé

### Après un changement de données:

```bash
# 1. Vérifier l'intégrité
php verify_integrity.php all

# 2. Si corrections nécessaires:
php maintenance_core.php all

# 3. Vérifier à nouveau
php verify_integrity.php all
```

### Synchronisation avec Excel:
```bash
php maintenance_core.php sync-eventtype
php verify_integrity.php eventtype
```

---

## 📁 Structure des Fichiers

### Scripts PHP (8 fichiers)
```
/All Assets/
├── maintenance_core.php              ⭐ PRINCIPAL
├── verify_integrity.php              ⭐ DIAGNOSTIC
├── cleanup_blitem_jobfile.php        (backup)
├── update_eventtype_from_excel.php   (backup)
├── update_procedure_inner_join.php   (backup)
├── verify_dateclose.php              (backup)
├── config.php                        (config)
└── generate_realistic_data_final.php (utilitaire)
```

### Fichiers de Données
- `IPAKI SAMPLE DATA.xlsx` - Données de référence (master data)
- `*.sql` - Scripts SQL (ies.sql, SAMPLE_DATA.sql, etc.)

---

## 📋 Historique des Corrections (14 Phases)

| Phase | Action | Status |
|-------|--------|--------|
| 1-8 | Normalisation, standardisation | ✅ |
| 9 | Restructure ConsigneeIds | ✅ |
| 10 | Max 4 événements/cycle | ✅ |
| 11 | Corrige DateClose | ✅ |
| 12 | Dédup BLItem-JobFile | ✅ |
| 13 | Optimise procedures | ✅ |
| 14 | Sync EventType Excel | ✅ |

---

## 🧹 Nettoyage Effectué

### Fichiers PHP (Phase Nettoyage)
- **Avant**: 35 fichiers PHP
- **Après**: 8 fichiers PHP (-77%)
- **Supprimés**: 28 fichiers redondants

### Fichiers de Documentation
- **Avant**: 30 fichiers .md/.txt
- **Après**: ~8 fichiers (conservés ou fusionnés)
- **Fusion**: 5 fichiers cleanup → 1 fichier maintenance

### Dépendances
- **Supprimés**: vendor/, composer.json, composer.lock

---

## ✅ Checklist de Maintenance

- [ ] Exécuter `php verify_integrity.php all`
- [ ] Si erreurs, exécuter `php maintenance_core.php all`
- [ ] Synchroniser Excel: `php maintenance_core.php sync-eventtype`
- [ ] Vérifier: `php verify_integrity.php eventtype`
- [ ] Documenter les changements

---

## 💡 Éléments Clés

### Données Critiques
- **EventType**: 68 types synchronisés avec Excel
- **Jobfiles**: Cycles de vie 1-4 événements (max 4)
- **DateClose**: NULL en cours, timestamp complétés
- **ConsigneeIds**: 15 types (non-ShippingLines)
- **Access Control**: 11 users × 15 tiers

### Optimisations
- Procédures optimisées avec INNER JOINs
- Pas d'orphelins (tous les items liés)
- Relations dédupliquées (1 jobfile par item)
- DateClose cohérent avec événements

---

**Dernière mise à jour**: 14 décembre 2025  
**Status**: ✅ Opérationnel et optimisé
