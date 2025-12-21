╔════════════════════════════════════════════════════════════════════════════╗
║                    MAINTENANCE DATABASE - GUIDE RAPIDE                      ║
║                                                                            ║
║                 Boîte à outils pour maintenance de la BD IES              ║
╚════════════════════════════════════════════════════════════════════════════╝


DÉMARRAGE RAPIDE
════════════════════════════════════════════════════════════════════════════

Ouvrez un terminal dans ce dossier et exécutez:

  1. Pour l'aide:
     php maintenance.php help

  2. Pour un rapport complet:
     php shortcuts.php report

  3. Pour vérifier:
     php shortcuts.php verify


COMMANDES PRINCIPALES
════════════════════════════════════════════════════════════════════════════

Utilisation simple avec les RACCOURCIS:

  php shortcuts.php fix              Corriger la structure BD
  php shortcuts.php verify           Vérifier l'intégrité
  php shortcuts.php report           Rapport complet
  php shortcuts.php analyze          Analyser la structure
  php shortcuts.php clean-bl         Nettoyer les doublons
  php shortcuts.php sync-event       Synchroniser EventType


Utilisation complète avec MAINTENANCE:

  php maintenance.php structure fix-structure
  php maintenance.php structure verify-integrity
  php maintenance.php structure report
  php maintenance.php core cleanup-blitem
  php maintenance.php core sync-eventtype


SITUATIONS COURANTES
════════════════════════════════════════════════════════════════════════════

La base de données semble instable:
  → php shortcuts.php report
  → php shortcuts.php fix
  → php shortcuts.php verify

Vous avez des erreurs de clé primaire:
  → php shortcuts.php fix
  → php shortcuts.php verify

Vous avez des doublons dans BLItem:
  → php shortcuts.php clean-bl
  → php shortcuts.php report

Vous avez besoin de synchroniser les données:
  → php shortcuts.php sync-event
  → php shortcuts.php verify


DOCUMENTATION
════════════════════════════════════════════════════════════════════════════

Pour plus d'informations, consultez:

  - MAINTENANCE_README.md          Guide complet
  - INDEX.md                       Index des scripts
  - FUSION_SUMMARY.md              Résumé des changements
  - DATABASE_CORRECTIONS_REPORT.md Rapport technique


FICHIERS PRINCIPAUX
════════════════════════════════════════════════════════════════════════════

maintenance.php                  ← Point d'entrée principal
├── shortcuts.php               ← Raccourcis rapides
├── maintenance_unified.php      ← Corrections structure
└── maintenance_core.php         ← Opérations critiques


EN CAS DE DOUTE
════════════════════════════════════════════════════════════════════════════

Exécutez d'abord:
  php maintenance.php help

Puis consultez la section pertinente dans MAINTENANCE_README.md


CONTRÔLE DE SANTÉ RAPIDE
════════════════════════════════════════════════════════════════════════════

Exécutez ces commandes en séquence pour un diagnostic complet:

  1. php shortcuts.php report      (Générer un rapport)
  2. php shortcuts.php verify      (Vérifier l'intégrité)
  3. php shortcuts.php analyze     (Analyser la structure)

Si tout est OK, vous verrez ✅ partout.


BESOIN D'AIDE?
════════════════════════════════════════════════════════════════════════════

Commandes d'aide:

  php maintenance.php help         (Aide complète)
  php shortcuts.php                (Afficher les raccourcis)
  php maintenance.php -h           (Aide courte)


─ Dernière mise à jour: 20 décembre 2025
─ Scripts consolidés et optimisés
─ Prêt pour la production ✅
