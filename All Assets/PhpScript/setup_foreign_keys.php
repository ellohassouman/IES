<?php
// Nettoyer et recréer toutes les contraintes FK

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'ies');
    
    if ($mysqli->connect_error) {
        die("Erreur de connexion: " . $mysqli->connect_error);
    }
    
    echo "=== NETTOYAGE ET CRÉATION DES CONTRAINTES ===\n\n";
    
    // Désactiver temporairement les contraintes
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
    
    // Récupérer toutes les contraintes FK
    echo "Suppression de toutes les contraintes FK existantes...\n";
    $result = $mysqli->query("SELECT 
                                CONSTRAINT_NAME,
                                TABLE_NAME
                            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                            WHERE TABLE_SCHEMA = 'ies' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL");
    
    $constraints_to_drop = [];
    while ($row = $result->fetch_assoc()) {
        $constraints_to_drop[] = [
            'table' => $row['TABLE_NAME'],
            'constraint' => $row['CONSTRAINT_NAME']
        ];
    }
    
    foreach ($constraints_to_drop as $constraint) {
        $sql = "ALTER TABLE " . $constraint['table'] . " DROP FOREIGN KEY " . $constraint['constraint'];
        if ($mysqli->query($sql)) {
            echo "  ✓ Supprimé: " . $constraint['table'] . " -> " . $constraint['constraint'] . "\n";
        } else {
            echo "  ⚠️  " . $mysqli->error . "\n";
        }
    }
    
    echo "\n";
    
    // Maintenant créer les nouvelles contraintes
    $constraints = [
        "ALTER TABLE area ADD CONSTRAINT FK_area_TerminalId FOREIGN KEY (TerminalId) REFERENCES terminal(Id)",
        "ALTER TABLE blitem ADD CONSTRAINT FK_blitem_BlId FOREIGN KEY (BlId) REFERENCES bl(Id)",
        "ALTER TABLE blitem_jobfile ADD CONSTRAINT FK_blitem_jobfile_BLItem_Id FOREIGN KEY (BLItem_Id) REFERENCES blitem(Id)",
        "ALTER TABLE blitem_jobfile ADD CONSTRAINT FK_blitem_jobfile_JobFile_Id FOREIGN KEY (JobFile_Id) REFERENCES jobfile(Id)",
        "ALTER TABLE cart ADD CONSTRAINT FK_cart_CustomerUserId FOREIGN KEY (CustomerUserId) REFERENCES customerusers(Id)",
        "ALTER TABLE cartitem ADD CONSTRAINT FK_cartitem_CartId FOREIGN KEY (CartId) REFERENCES cart(Id)",
        "ALTER TABLE cartitem ADD CONSTRAINT FK_cartitem_InvoiceId FOREIGN KEY (InvoiceId) REFERENCES invoice(Id)",
        "ALTER TABLE commodityitem ADD CONSTRAINT FK_commodityitem_CommodityId FOREIGN KEY (CommodityId) REFERENCES commodity(Id)",
        "ALTER TABLE contract ADD CONSTRAINT FK_contract_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        "ALTER TABLE contract_eventtype ADD CONSTRAINT FK_contract_eventtype_ContractId FOREIGN KEY (ContractId) REFERENCES contract(Id)",
        "ALTER TABLE contract_eventtype ADD CONSTRAINT FK_contract_eventtype_EventTypeId FOREIGN KEY (EventTypeId) REFERENCES eventtype(Id)",
        "ALTER TABLE customerusers_thirdparty ADD CONSTRAINT FK_customerusers_thirdparty_UserId FOREIGN KEY (UserId) REFERENCES customerusers(Id)",
        "ALTER TABLE customerusers_thirdparty ADD CONSTRAINT FK_customerusers_thirdparty_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        "ALTER TABLE customerusers ADD CONSTRAINT FK_customerusers_UserTypeId FOREIGN KEY (UserTypeId) REFERENCES customeruserstype(Id)",
        "ALTER TABLE customerusers ADD CONSTRAINT FK_customerusers_UserStatusId FOREIGN KEY (UserStatusId) REFERENCES customerusersstatus(Id)",
        "ALTER TABLE customeruserblsearchhistory ADD CONSTRAINT FK_customeruserblsearchhistory_CustomerUserId FOREIGN KEY (CustomerUserId) REFERENCES customerusers(Id)",
        "ALTER TABLE document ADD CONSTRAINT FK_document_DocumentTypeId FOREIGN KEY (DocumentTypeId) REFERENCES documenttype(Id)",
        "ALTER TABLE event ADD CONSTRAINT FK_event_JobFileId FOREIGN KEY (JobFileId) REFERENCES jobfile(Id)",
        "ALTER TABLE event ADD CONSTRAINT FK_event_EventTypeId FOREIGN KEY (EventTypeId) REFERENCES eventtype(Id)",
        "ALTER TABLE eventtype ADD CONSTRAINT FK_eventtype_FamilyId FOREIGN KEY (FamilyId) REFERENCES family(Id)",
        "ALTER TABLE invoice ADD CONSTRAINT FK_invoice_InvoiceStatusId FOREIGN KEY (InvoiceStatusId) REFERENCES invoicestatus(Id)",
        "ALTER TABLE invoiceitem ADD CONSTRAINT FK_invoiceitem_InvoiceId FOREIGN KEY (InvoiceId) REFERENCES invoice(Id)",
        "ALTER TABLE jobfile ADD CONSTRAINT FK_jobfile_PositionId FOREIGN KEY (PositionId) REFERENCES position(Id)",
        "ALTER TABLE jobfile ADD CONSTRAINT FK_jobfile_ShippingLineId FOREIGN KEY (ShippingLineId) REFERENCES thirdparty(Id)",
        "ALTER TABLE payment ADD CONSTRAINT FK_payment_PaymentTypeId FOREIGN KEY (PaymentTypeId) REFERENCES paymenttype(Id)",
        "ALTER TABLE payment_invoice ADD CONSTRAINT FK_payment_invoice_Payment_Id FOREIGN KEY (Payment_Id) REFERENCES payment(Id)",
        "ALTER TABLE payment_invoice ADD CONSTRAINT FK_payment_invoice_Invoice_Id FOREIGN KEY (Invoice_Id) REFERENCES invoice(Id)",
        "ALTER TABLE position ADD CONSTRAINT FK_position_RowId FOREIGN KEY (RowId) REFERENCES `row`(Id)",
        "ALTER TABLE position ADD CONSTRAINT FK_position_YardItemTypeId FOREIGN KEY (YardItemTypeId) REFERENCES yarditemtype(Id)",
        "ALTER TABLE rate ADD CONSTRAINT FK_rate_RatePeriodId FOREIGN KEY (RatePeriodId) REFERENCES rateperiod(Id)",
        "ALTER TABLE `row` ADD CONSTRAINT FK_row_AreaId FOREIGN KEY (AreaId) REFERENCES area(Id)",
        "ALTER TABLE thirdparty_thirdpartytype ADD CONSTRAINT FK_thirdparty_thirdpartytype_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        "ALTER TABLE thirdparty_thirdpartytype ADD CONSTRAINT FK_thirdparty_thirdpartytype_ThirdPartyTypeId FOREIGN KEY (ThirdPartyTypeId) REFERENCES thirdpartytype(Id)",
    ];
    
    $success = 0;
    $errors = 0;
    
    echo "Création des nouvelles contraintes FK...\n\n";
    foreach ($constraints as $constraint) {
        if ($mysqli->query($constraint)) {
            $success++;
            echo "✓ " . substr($constraint, 25, 50) . "...\n";
        } else {
            $errors++;
            echo "✗ " . substr($constraint, 25, 50) . "...\n";
            echo "  Erreur: " . $mysqli->error . "\n";
        }
    }
    
    // Réactiver les contraintes
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "✓ Contraintes créées: $success\n";
    echo "✗ Erreurs: $errors\n";
    
    if ($errors === 0) {
        echo "\n✅ Toutes les contraintes de clés étrangères ont été créées avec succès!\n";
        echo "Les relations doivent maintenant être visibles dans votre vue relationnelle.\n";
    }
    
    $mysqli->close();
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
}
?>
