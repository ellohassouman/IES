# Documentation des Endpoints - Génération de Proforma

## Vue d'ensemble
Ce document décrit les endpoints API nécessaires pour la fonctionnalité de génération de proforma dans le composant `BillOfLadingPendingInvoicingComponent`.

## Endpoints Requis

### 1. GenerateProforma
**Endpoint:** `POST /api/GenerateProforma`

**Description:** Génère une prévisualisation de proforma basée sur les items de yard sélectionnés.

**Structure de requête:**
```json
{
  "billOfLadingId": 792416,
  "billOfLadingNumber": "MEDUDM992142",
  "yardItems": [
    {
      "yardItemNumber": "MSDU8245231",
      "yardItemId": "1488473"
    },
    {
      "yardItemNumber": "MSDU8908078",
      "yardItemId": "1488884"
    }
  ]
}
```

**Structure de réponse:**
```json
{
  "id": "PRF_1702547200000",
  "proformaNumber": "PF_1702547200000",
  "billOfLadingNumber": "MEDUDM992142",
  "totalAmount": 450.75,
  "currency": "USD",
  "items": [
    {
      "yardItemNumber": "MSDU8245231",
      "description": "Service de manutention - MSDU8245231",
      "quantity": 1,
      "unitPrice": 250.50,
      "totalPrice": 250.50
    },
    {
      "yardItemNumber": "MSDU8908078",
      "description": "Service de manutention - MSDU8908078",
      "quantity": 1,
      "unitPrice": 200.25,
      "totalPrice": 200.25
    }
  ],
  "generatedDate": "2025-12-14T10:30:00.000Z"
}
```

**Codes HTTP:**
- 200: Succès
- 400: Requête invalide
- 404: BL introuvable
- 500: Erreur serveur

---

### 2. GenerateProformaWithBillingDate
**Endpoint:** `POST /api/GenerateProformaWithBillingDate`

**Description:** Génère une proforma définitive avec la date d'enlèvement et crée les documents facturables.

**Structure de requête:**
```json
{
  "billOfLadingId": 792416,
  "billOfLadingNumber": "MEDUDM992142",
  "yardItemsJson": "[{\"yardItemNumber\":\"MSDU8245231\",\"yardItemId\":\"1488473\"},{\"yardItemNumber\":\"MSDU8908078\",\"yardItemId\":\"1488884\"}]",
  "isCash": "true",
  "allowClearingAgentMode": true,
  "forceOverridenClientName": false,
  "journalType": "STI",
  "isTransitFileCustomer": false,
  "billingDate": "2025-12-14"
}
```

**Structure de réponse:**
```json
{
  "id": "PRF_1702547300000",
  "proformaNumber": "PRF_000001234",
  "billOfLadingNumber": "MEDUDM992142",
  "totalAmount": 450.75,
  "currency": "USD",
  "items": [
    {
      "yardItemNumber": "MSDU8245231",
      "description": "Service de manutention - MSDU8245231",
      "quantity": 1,
      "unitPrice": 250.50,
      "totalPrice": 250.50
    },
    {
      "yardItemNumber": "MSDU8908078",
      "description": "Service de manutention - MSDU8908078",
      "quantity": 1,
      "unitPrice": 200.25,
      "totalPrice": 200.25
    }
  ],
  "generatedDate": "2025-12-14T10:35:00.000Z"
}
```

**Codes HTTP:**
- 200: Succès
- 400: Requête invalide
- 404: BL introuvable
- 409: Conflit (proforma déjà générée)
- 500: Erreur serveur

---

### 3. AddYardItemEvent
**Endpoint:** `POST /api/AddYardItemEvent`

**Description:** Ajoute un événement à un ou plusieurs yard items.

**Structure de requête:**
```json
{
  "yardItemIds": ["1488473", "1488884"],
  "blNumber": "MEDUDM992142",
  "eventType": "chargement",
  "description": "Conteneur chargé avec succès",
  "date": "2025-12-14"
}
```

**Structure de réponse:**
```json
{
  "success": true,
  "message": "Événement créé avec succès pour 2 élément(s)",
  "eventIds": ["EVT_1702547200000_0", "EVT_1702547200000_1"]
}
```

**Codes HTTP:**
- 200: Succès
- 400: Requête invalide
- 404: Yard item(s) introuvable(s)
- 500: Erreur serveur

---

## Implémentation Côté Frontend

### Enum EndPoint
Les endpoints sont définis dans `Frontend/src/app/Enum/enum-end-point.ts`:

```typescript
export enum EnumEndPoint {
  GenerateProforma = "GenerateProforma",
  GenerateProformaWithBillingDate = "GenerateProformaWithBillingDate",
  AddYardItemEvent = "AddYardItemEvent",
  // ... autres endpoints
}
```

### Service d'Invocation
Les appels API sont effectués via `BillOfLadingPendingInvoicingService`:

```typescript
// Génération de prévisualisation
generateProforma(request: ProformaRequest): Observable<ProformaResponse>

// Ajout d'événement
addEvent(request: AddEventRequest): Observable<AddEventResponse>
```

### Composant
Le composant `BillOfLadingPendingInvoicingComponent` utilise ces services pour:
1. Afficher une prévisualisation de proforma au clic sur "Générer proforma"
2. Soumettre le formulaire avec la date d'enlèvement
3. Ajouter des événements aux items sélectionnés

---

## Notes d'Implémentation

### Format de Date
- Input HTML5: Format YYYY-MM-DD (ISO 8601)
- Affichage: Format localisé selon la région (dd/MM/yyyy en France)
- Transmission API: YYYY-MM-DD

### Validation
- La date d'enlèvement est obligatoire avant la soumission finale
- Au minimum un yard item doit être sélectionné
- Les événements requirent un type et une description

### Gestion des Erreurs
- Les erreurs sont affichées via des alertes JavaScript
- Le spinner de chargement s'affiche pendant les opérations
- Les erreurs 401/403 redirigent vers la page de login

---

## Flux Utilisateur

1. **Sélection des items** → Cocher les yard items à facturer
2. **Clic "Générer proforma"** → Appel GenerateProforma pour prévisualisation
3. **Modal s'affiche** → Affichage de la prévisualisation
4. **Saisie de la date** → Utilisation du datepicker HTML5
5. **Clic "Générer"** → Appel GenerateProformaWithBillingDate pour validation
6. **Confirmation** → Message de succès et fermeture du modal

---

## Exemple d'Implémentation Backend (Node.js/Express)

```javascript
// POST /api/GenerateProforma
app.post('/api/GenerateProforma', async (req, res) => {
  try {
    const { billOfLadingId, billOfLadingNumber, yardItems } = req.body;
    
    // Récupérer les détails des items
    const itemsDetails = await getYardItemsDetails(yardItems);
    
    // Calculer les frais
    const items = itemsDetails.map(item => ({
      yardItemNumber: item.number,
      description: `Service de manutention - ${item.number}`,
      quantity: 1,
      unitPrice: calculatePrice(item),
      totalPrice: calculatePrice(item)
    }));
    
    const totalAmount = items.reduce((sum, item) => sum + item.totalPrice, 0);
    
    res.json({
      id: `PRF_${Date.now()}`,
      proformaNumber: `PF_${Date.now()}`,
      billOfLadingNumber,
      totalAmount,
      currency: 'USD',
      items,
      generatedDate: new Date().toISOString()
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// POST /api/GenerateProformaWithBillingDate
app.post('/api/GenerateProformaWithBillingDate', async (req, res) => {
  try {
    const { 
      billOfLadingId, 
      billOfLadingNumber, 
      yardItemsJson, 
      billingDate,
      isCash,
      allowClearingAgentMode,
      journalType
    } = req.body;
    
    // Valider la date
    if (!billingDate) {
      return res.status(400).json({ error: 'Date d\'enlèvement requise' });
    }
    
    // Parser yardItemsJson
    const yardItems = JSON.parse(yardItemsJson);
    
    // Créer la proforma définitive
    // ... logique métier ...
    
    res.json({
      id: `PRF_${Date.now()}`,
      proformaNumber: `PRF_${generateProformaNumber()}`,
      billOfLadingNumber,
      totalAmount: 450.75,
      currency: 'USD',
      items: [],
      generatedDate: new Date().toISOString()
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// POST /api/AddYardItemEvent
app.post('/api/AddYardItemEvent', async (req, res) => {
  try {
    const { yardItemIds, blNumber, eventType, description, date } = req.body;
    
    // Créer l'événement pour chaque yard item
    const eventIds = [];
    for (const yardItemId of yardItemIds) {
      const eventId = await createEvent({
        yardItemId,
        blNumber,
        eventType,
        description,
        date
      });
      eventIds.push(eventId);
    }
    
    res.json({
      success: true,
      message: `Événement créé avec succès pour ${yardItemIds.length} élément(s)`,
      eventIds
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

