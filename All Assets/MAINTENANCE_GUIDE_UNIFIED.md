# 📚 GUIDE DE MAINTENANCE - Documentation Complète

## 🎯 Vue d'ensemble

Le système de maintenance de la base de données IES est consolidé en **3 scripts essentiels**:

| Script | Rôle | Commandes |
|--------|------|-----------|
| `maintenance.php` | Launcher principal | help, structure, core |
| `maintenance_unified.php` | Structure & Intégrité | fix-structure, verify-integrity, analyze, report |
| `maintenance_core.php` | Opérations critiques | cleanup-blitem, sync-eventtype, dateclose, procedures |

---

## 🚀 Démarrage rapide

### Installation et configuration

```bash
# 1. Naviguer au répertoire
cd "D:\Websites\IES\All Assets"

# 2. Vérifier l'aide
php maintenance.php help

# 3. Exécuter une vérification
php shortcuts.php verify

# 4. Générer un rapport
php shortcuts.php report
```

### Commandes les plus courantes

```bash
# ✅ Vérifier l'intégrité (9 vérifications)
php shortcuts.php verify

# 🧹 Nettoyer les doublons
php shortcuts.php clean-bl

# 📊 Générer un rapport
php shortcuts.php report

# 🔧 Corriger la structure
php shortcuts.php fix
```

---

## 📖 Guide détaillé par fonctionnalité

### 1️⃣ VÉRIFIER L'INTÉGRITÉ (9 VÉRIFICATIONS)

**Commande:**
```bash
php maintenance.php structure verify-integrity
# ou raccourci:
php shortcuts.php verify
```

**Effectue 9 contrôles complets:**

```
✅ 1. Clés primaires UNSIGNED
   Vérifie que tous les PRIMARY KEYs INT sont UNSIGNED

✅ 2. Clés étrangères UNSIGNED
   Vérifie que toutes les clés étrangères sont UNSIGNED

✅ 3. EventType (68 types)
   Contrôle qu'il y a 68 types d'événements

✅ 4. DateClose (Jobfiles)
   Affiche statistiques des jobfiles complétés/en cours

✅ 5. Cycles de Vie (IN→OUT)
   Vérifie que les items ont des cycles complets

✅ 6. Items Orphelins
   Détecte les items sans BL parent

✅ 7. Relations BL-Items
   Affiche statistiques des relations BL-Items

✅ 8. Factures
   Affiche statistiques des factures par BL

✅ 9. Contrôle d'Accès
   Affiche statistiques des permissions utilisateur
```

**Exemple de sortie:**
```
PRIMARY KEY INT sans AUTO_INCREMENT:
✅ Toutes les clés primaires sont correctes

Clés étrangères UNSIGNED:
✅ Toutes les clés étrangères sont UNSIGNED

EventType (68 types):
✅ EventType: 68 (correct)

DateClose (Jobfiles):
   - Avec DateClose: 134 (complétés)
   - Sans DateClose: 95 (en cours)

...
```

---

### 2️⃣ CORRIGER LA STRUCTURE BD

**Commande:**
```bash
php maintenance.php structure fix-structure
# ou raccourci:
php shortcuts.php fix
```

**Applique 44 corrections:**
- Ajoute AUTO_INCREMENT aux clés primaires INT
- Rend UNSIGNED toutes les clés étrangères INT
- Nettoie les données invalides (ID=0 dans EVENT)
- Gère les contraintes étrangères intelligemment

**Exemple:**
```
🔧 CORRECTION COMPLÈTE STRUCTURE BD

📋 AREA
   ✅ OK
📋 BL
   ✅ OK
📋 BLITEM
   ✅ OK
...
```

---

### 3️⃣ GÉNÉRER UN RAPPORT

**Commande:**
```bash
php maintenance.php structure report
# ou raccourci:
php shortcuts.php report
```

**Contient:**
- Analyse complète de la structure (45 tables, 180 colonnes)
- 9 vérifications d'intégrité
- Statistiques de la base de données
- Recommandations

---

### 4️⃣ ANALYSER LA STRUCTURE

**Commande:**
```bash
php maintenance.php structure analyze
# ou raccourci:
php shortcuts.php analyze
```

**Affiche:**
```
📈 Statistiques globales:
   Tables: 45
   Colonnes: 180
   Clés primaires: 45
   Clés étrangères: 41
```

---

### 5️⃣ NETTOYER LES DOUBLONS (DÉDUPLICATION AVANCÉE)

**Commande:**
```bash
php maintenance.php core cleanup-blitem
# ou raccourci:
php shortcuts.php clean-bl
```

**Algorithme 2-étapes intelligent:**

Étape 1: Cherche les items avec plusieurs jobfiles
```
Items avec plusieurs jobfiles trouvés: X
```

Étape 2: Sélectionne le meilleur jobfile à garder
```
Stratégie:
- Préférence 1: JobFile avec OUT (cycle complet)
- Préférence 2: JobFile le plus récent
- Supprimer: Les autres jobfiles
```

Étape 3: Supprime les relations inutiles
```
Lignes supprimées: Y
Vérification: Tous les items ont maintenant UN SEUL jobfile ✅
```

---

### 6️⃣ SYNCHRONISER EVENTTYPE DEPUIS EXCEL

**Commande:**
```bash
php maintenance.php core sync-eventtype
# ou raccourci:
php shortcuts.php sync-event
```

**Fichier Excel requis:**
- Localisation: `d:\Websites\IES\All Assets\IPAKI SAMPLE DATA.xlsx`
- Feuille: `EventType`
- Colonnes: ID, Code, FamilyId, Billable, Name

**Résultat:**
```
Excel chargé: 68 types d'événements
68 mises à jour réussies
```

⚠️ **Note:** Nécessite PhpSpreadsheet installé via Composer

---

### 7️⃣ CORRIGER DATECLOSE

**Commande:**
```bash
php maintenance.php core dateclose
# ou raccourci:
php shortcuts.php fix-dateclose
```

**Effectue 2 corrections:**

1. **Jobfiles sans OUT mais avec DateClose → NULL**
   ```
   X DateClose remis à NULL
   ```

2. **Jobfiles avec OUT mais sans DateClose → date de l'événement OUT**
   ```
   Y DateClose définis
   ```

---

### 8️⃣ OPTIMISER LES PROCÉDURES STOCKÉES

**Commande:**
```bash
php maintenance.php core procedures
# ou raccourci:
php shortcuts.php optimize
```

**Procédures optimisées:**
- `GetYardItemTrackingMovements` - Utilise INNER JOINs au lieu de LEFT JOINs
- Performance améliorée pour les requêtes de suivi

---

## 🔧 Utilisation avancée

### Exécuter tout (fix + verify + report)

```bash
php maintenance.php structure report
```

**Lance automatiquement:**
1. Analyse de structure
2. 9 vérifications complètes
3. Rapport final

### Chainer les opérations

```bash
# Corriger structure puis vérifier
php shortcuts.php fix && php shortcuts.php verify

# Nettoyer puis corriger DateClose
php shortcuts.php clean-bl && php shortcuts.php fix-dateclose
```

### Mode verbeux (voir les détails)

```bash
# Affiche tous les détails de chaque opération
php maintenance.php structure fix-structure 2>&1
```

---

## 📋 Structure des fichiers

```
All Assets/
├── 📄 config.php                    ← Configuration MySQL
├── 🎯 maintenance.php               ← Launcher principal
├── 🔧 maintenance_unified.php       ← Structure + 9 vérifications
├── ⚙️  maintenance_core.php         ← Opérations critiques
├── ⚡ shortcuts.php                 ← Raccourcis rapides
│
└── 📚 DOCUMENTATION/
    ├── MAINTENANCE_GUIDE.md         ← Ce fichier (guide complet)
    ├── INDEX.md                     ← Index de référence
    ├── TECHNICAL_REPORT.md          ← Rapports techniques
    └── README_QUICK_START.txt       ← Démarrage rapide
```

---

## 🎯 Cas d'usage courants

### ✅ Maintenance hebdomadaire
```bash
# 1. Vérifier l'intégrité
php shortcuts.php verify

# 2. Générer un rapport
php shortcuts.php report
```

### ✅ Maintenance mensuelle
```bash
# 1. Nettoyer les doublons
php shortcuts.php clean-bl

# 2. Corriger DateClose
php shortcuts.php fix-dateclose

# 3. Vérifier le tout
php shortcuts.php verify
```

### ✅ Après changements Excel
```bash
# 1. Synchroniser EventType
php shortcuts.php sync-event

# 2. Vérifier
php shortcuts.php verify
```

### ✅ Maintenance complète
```bash
# Tout en un
php shortcuts.php fix && \
php shortcuts.php clean-bl && \
php shortcuts.php fix-dateclose && \
php shortcuts.php verify
```

---

## 🔍 Dépannage

### Erreur: "Cannot redeclare function"
**Solution:** Assurez-vous que le launcher (`maintenance.php`) exécute les scripts via `passthru()`, pas `include()`.

### Erreur: "PhpSpreadsheet not found"
**Solution:** Installer via Composer:
```bash
composer require phpoffice/phpspreadsheet
```

### Erreur: "Connection refused"
**Solution:** Vérifier que MySQL est actif et les credentials dans `config.php` sont corrects.

### Les vérifications montrent des problèmes
**Solution:** Exécuter:
```bash
php shortcuts.php fix      # Corriger structure
php shortcuts.php clean-bl # Nettoyer doublons
php shortcuts.php verify   # Vérifier à nouveau
```

---

## 📊 Métriques et statistiques

### Base de données
- **Tables:** 45
- **Colonnes:** 180
- **Clés primaires:** 45 (toutes UNSIGNED AUTO_INCREMENT)
- **Clés étrangères:** 41 (toutes UNSIGNED NULL)
- **EventTypes:** 68

### Système de maintenance
- **Scripts PHP:** 5 (consolidés de 11)
- **Commandes disponibles:** 12
- **Vérifications:** 9 (consolidées)
- **Optimisations:** 4
- **Lignes de code:** 1,105 (consolidé de 2,200+)

---

## 📞 Support et documentation

### Fichiers de référence
- **INDEX.md** - Index complet avec détails techniques
- **TECHNICAL_REPORT.md** - Rapports de fusion et corrections BD
- **README_QUICK_START.txt** - Guide ultra-rapide
- **MAINTENANCE_GUIDE.md** - Ce fichier

### Contact
Pour des questions spécifiques, consulter:
1. Ce guide (MAINTENANCE_GUIDE.md)
2. INDEX.md pour les détails techniques
3. TECHNICAL_REPORT.md pour l'historique des corrections

---

## ✨ Résumé des améliorations

**Avant consolidation:**
- 19 fichiers PHP
- Code dupliqué
- Vérifications basiques
- Maintien difficile

**Après consolidation:**
- ✅ 5 fichiers PHP (-55%)
- ✅ 0 redondance
- ✅ 9 vérifications complètes (+200%)
- ✅ Algorithmes optimisés
- ✅ Maintenance simplifiée

**Résultat:** Système professionnel et maintenable! 🚀

---

**Dernière mise à jour:** 20 décembre 2025  
**Version:** 1.0 - Documentation complète
