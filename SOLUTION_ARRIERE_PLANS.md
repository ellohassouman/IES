# Solution : Arrière-plans manquants sur les pages Login/Register

## 🎯 Problème identifié

Les images d'arrière-plan n'apparaissaient pas sur les pages de login et registration car :

1. **Structure Angular incorrecte** : Les balises `<body>` étaient directement dans les composants Angular (`register.component.html` et `login.component.html`), ce qui n'est pas correct en Angular
2. **Classes CSS non appliquées au bon endroit** : Les classes `bgPpal` et `bgPpal1` définies dans le CSS ne s'appliquaient pas correctement car elles devaient être sur le vrai `<body>` du document (dans `index.html`), pas sur les composants
3. **Pas de logique dynamique** : Il n'y avait aucun moyen de changer la classe du body selon la page active

## ✅ Solution implémentée

### 1. **Service BodyClassService** 
   - **Fichier** : `Frontend/src/app/Services/body-class.service.ts`
   - **Rôle** : Gère dynamiquement la classe CSS du `<body>` du document
   - **Fonctionnalités** :
     - `setBodyClass(className)` : Applique une classe au body
     - `clearBodyClass()` : Enlève la classe actuelle
     - `addBodyClass(className)` : Ajoute une classe supplémentaire
     - `removeBodyClass(className)` : Enlève une classe spécifique
   - **Nettoyage automatique** : Nettoie la classe quand on change de route

### 2. **Composant Login**
   - **Fichier** : `Frontend/src/app/login/login.component.ts`
   - **Changements** :
     - Injection du `BodyClassService`
     - Ajout de `OnDestroy` pour le nettoyage
     - Dans `ngOnInit()` : `this.bodyClassService.setBodyClass('bgPpal');`
     - Dans `ngOnDestroy()` : `this.bodyClassService.clearBodyClass();`

### 3. **Composant Register**
   - **Fichier** : `Frontend/src/app/register/register.component.ts`
   - **Changements** :
     - Injection du `BodyClassService`
     - Ajout de `OnDestroy` pour le nettoyage
     - Dans `ngOnInit()` : `this.bodyClassService.setBodyClass('bgPpal1');`
     - Dans `ngOnDestroy()` : `this.bodyClassService.clearBodyClass();`

### 4. **Templates HTML**
   - **Fichiers** : `login.component.html` et `register.component.html`
   - **Changements** :
     - Suppression des balises `<body>` erronées
     - Garder la structure interne (divs, formulaires, etc.)

### 5. **CSS existant**
   - **Fichier** : `Frontend/src/assets/css/sassStyle.css` (lignes 4299-4314)
   - Reste inchangé et référence correctement les images :
     ```css
     .bgPpal {
       background-image: url(../images/img_fondo1.jpg);
       padding-top: 0px;
     }
     
     .bgPpal1 {
       background-image: url(../images/img_fondo2.jpg);
       padding-top: 0px;
       background-attachment: fixed;
     }
     ```

## 📁 Images utilisées

- **Login** : `/assets/images/img_fondo1.jpg` (271 KB)
- **Register** : `/assets/images/img_fondo2.jpg` (328 KB)

## 🔄 Flux d'exécution

```
Navigation → Composant charge
    ↓
ngOnInit() déclenché
    ↓
BodyClassService.setBodyClass('bgPpal' ou 'bgPpal1')
    ↓
Service applique la classe au document.body
    ↓
CSS appelle background-image
    ↓
Arrière-plan s'affiche ✓

Navigation vers autre page
    ↓
ngOnDestroy() déclenché
    ↓
BodyClassService.clearBodyClass()
    ↓
Classe enlevée du body
```

## 🧪 Test

Pour vérifier que la solution fonctionne :

1. Naviguez vers `/login` → L'arrière-plan `img_fondo1.jpg` doit s'afficher
2. Naviguez vers `/register` → L'arrière-plan `img_fondo2.jpg` doit s'afficher avec fixed attachment
3. Naviguez vers une autre page → L'arrière-plan doit disparaître

## 💡 Avantages de cette approche

- ✅ Scalable : Facile d'ajouter d'autres classes pour d'autres pages
- ✅ Propre : Respecte l'architecture Angular
- ✅ Automatique : Gestion automatique du cleanup
- ✅ Centralisé : Logique regroupée dans un service
- ✅ Flexible : Permet de mixer plusieurs classes si besoin
