# Endpoints Backend Requis - Génération de Proforma

## Routes à Implémenter

### 1. POST /api/GenerateProforma
Génère une prévisualisation de proforma pour affichage dans le modal.

**Paramètres d'entrée:**
- `billOfLadingId` (number): ID du BL
- `billOfLadingNumber` (string): Numéro du BL
- `yardItems` (array): Liste des yard items avec `yardItemNumber` et `yardItemId`

**Réponse attendue:**
```json
{
  "id": "string",
  "proformaNumber": "string",
  "billOfLadingNumber": "string",
  "totalAmount": "number",
  "currency": "string",
  "items": [
    {
      "yardItemNumber": "string",
      "description": "string",
      "quantity": "number",
      "unitPrice": "number",
      "totalPrice": "number"
    }
  ],
  "generatedDate": "ISO8601 datetime"
}
```

---

### 2. POST /api/GenerateProformaWithBillingDate
Génère la proforma définitive avec date d'enlèvement.

**Paramètres d'entrée:**
- `billOfLadingId` (number)
- `billOfLadingNumber` (string)
- `yardItemsJson` (string): JSON stringifié de la liste des items
- `isCash` (string)
- `allowClearingAgentMode` (boolean)
- `forceOverridenClientName` (boolean)
- `journalType` (string): "STI" par défaut
- `isTransitFileCustomer` (boolean)
- `billingDate` (string): Date au format YYYY-MM-DD

**Réponse attendue:**
Même structure que GenerateProforma

---

### 3. POST /api/AddYardItemEvent
Ajoute un événement à un ou plusieurs yard items.

**Paramètres d'entrée:**
- `yardItemIds` (array[string]): IDs des yard items
- `blNumber` (string): Numéro du BL
- `eventType` (string): Type d'événement
- `description` (string): Description de l'événement
- `date` (string): Date au format YYYY-MM-DD

**Réponse attendue:**
```json
{
  "success": true,
  "message": "string",
  "eventIds": ["string"]
}
```

---

## Fichiers Modifiés (Frontend)

### 1. `Frontend/src/app/Enum/enum-end-point.ts`
**Changement:** Ajout de 3 nouveaux endpoints
```typescript
GenerateProforma = "GenerateProforma",
GenerateProformaWithBillingDate = "GenerateProformaWithBillingDate",
AddYardItemEvent = "AddYardItemEvent",
```

### 2. `Frontend/src/app/Services/bill-of-lading-pending-invoicing.service.ts`
**Changements:**
- Import de `EnumEndPoint` et `RequesterService`
- Modification de `addEvent()` pour utiliser `AsyncPostObservable` au lieu de mock
- Modification de `generateProforma()` pour utiliser les endpoints réels
  - Utilise `GenerateProforma` pour la prévisualisation simple
  - Utilise `GenerateProformaWithBillingDate` quand tous les champs sont remplis

### 3. `Frontend/src/app/Services/requester.service.ts`
**Changement:** Ajout d'une nouvelle méthode publique `AsyncPostObservable()`
```typescript
AsyncPostObservable(
  targetapi: string,
  data: any,
  headers: HttpHeaders = new HttpHeaders(),
  ShowErrorMessage: boolean = true
): Observable<any>
```
Cette méthode effectue une requête POST et retourne directement un Observable (contrairement à `AsyncPostResponse` qui retourne une Promise).

### 4. `Frontend/src/app/bill-of-lading-pending-invoicing/bill-of-lading-pending-invoicing.component.html`
**Changement:** Remplacement du datepicker jQuery par un input HTML5 natif
- `type="text"` remplacé par `type="date"`
- Suppression de la structure `div id="datetimepickerProforma"` 
- Suppression de l'icône calendrier (navigateur gère automatiquement)

### 5. `Frontend/src/app/bill-of-lading-pending-invoicing/bill-of-lading-pending-invoicing.component.ts`
**Changements:**
- Conversion complète de la logique en TypeScript pur
- Suppression de l'initialisation jQuery du datepicker
- `ngAfterViewInit()` vide (le datepicker HTML5 ne nécessite pas d'initialisation)
- Amélioration du typage avec interfaces
- Séparation des responsabilités avec méthodes privées

---

## Flux d'Appels API

```
Utilisateur clic "Générer proforma"
    ↓
POST /api/GenerateProforma
    ↓
Affichage du modal avec prévisualisation
    ↓
Utilisateur saisit date + clic "Générer"
    ↓
POST /api/GenerateProformaWithBillingDate
    ↓
Confirmation de création
```

---

## Considérations Backend

1. **Authentification**: Les endpoints doivent vérifier l'authentification de l'utilisateur
2. **Autorisation**: L'utilisateur ne peut générer une proforma que pour ses propres BLs
3. **Validation**: Valider la date d'enlèvement (format et valeur)
4. **Idempotence**: Gérer les appels en doublons pour eviter la création de multiples proformas
5. **Calcul des montants**: Implémenter la logique de calcul des frais de manutention
6. **Journal**: Enregistrer la création de proforma pour audit

---

## Test Rapide

Pour tester la nouvelle implémentation:

1. Ouvrir la page BL
2. Aller à "Pending Invoicing"
3. Sélectionner des items
4. Cliquer "Générer proforma"
5. Vérifier les appels API dans F12 Network

Les requêtes doivent être:
- POST vers `http://127.0.0.1:8000/api/GenerateProforma` (ou votre URL)
- POST vers `http://127.0.0.1:8000/api/GenerateProformaWithBillingDate` avec la date

