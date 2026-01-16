<?php
// Recréer les contraintes de clés étrangères

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'ies');
    
    if ($mysqli->connect_error) {
        die("Erreur de connexion: " . $mysqli->connect_error);
    }
    
    echo "=== CRÉATION DES CONTRAINTES DE CLÉS ÉTRANGÈRES ===\n\n";
    
    // Désactiver temporairement les contraintes
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
    
    // Supprimer les contraintes FK existantes
    echo "Suppression des contraintes FK existantes...\n";
    $mysqli->query('ALTER TABLE area DROP FOREIGN KEY FK_area_TerminalId');
    
    // Liste des contraintes FK à créer (avec les vrais noms de colonnes)
    $constraints = [
        // area -> terminal
        "ALTER TABLE area ADD CONSTRAINT FK_area_TerminalId FOREIGN KEY (TerminalId) REFERENCES terminal(Id)",
        
        // blitem -> bl
        "ALTER TABLE blitem ADD CONSTRAINT FK_blitem_BlId FOREIGN KEY (BlId) REFERENCES bl(Id)",
        
        // blitem_jobfile -> blitem
        "ALTER TABLE blitem_jobfile ADD CONSTRAINT FK_blitem_jobfile_BLItem_Id FOREIGN KEY (BLItem_Id) REFERENCES blitem(Id)",
        
        // blitem_jobfile -> jobfile
        "ALTER TABLE blitem_jobfile ADD CONSTRAINT FK_blitem_jobfile_JobFile_Id FOREIGN KEY (JobFile_Id) REFERENCES jobfile(Id)",
        
        // cart -> customerusers
        "ALTER TABLE cart ADD CONSTRAINT FK_cart_CustomerUserId FOREIGN KEY (CustomerUserId) REFERENCES customerusers(Id)",
        
        // cartitem -> cart
        "ALTER TABLE cartitem ADD CONSTRAINT FK_cartitem_CartId FOREIGN KEY (CartId) REFERENCES cart(Id)",
        
        // cartitem -> invoice
        "ALTER TABLE cartitem ADD CONSTRAINT FK_cartitem_InvoiceId FOREIGN KEY (InvoiceId) REFERENCES invoice(Id)",
        
        // commodityitem -> commodity
        "ALTER TABLE commodityitem ADD CONSTRAINT FK_commodityitem_CommodityId FOREIGN KEY (CommodityId) REFERENCES commodity(Id)",
        
        // contract -> thirdparty
        "ALTER TABLE contract ADD CONSTRAINT FK_contract_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        
        // contract_eventtype -> contract
        "ALTER TABLE contract_eventtype ADD CONSTRAINT FK_contract_eventtype_ContractId FOREIGN KEY (ContractId) REFERENCES contract(Id)",
        
        // contract_eventtype -> eventtype
        "ALTER TABLE contract_eventtype ADD CONSTRAINT FK_contract_eventtype_EventTypeId FOREIGN KEY (EventTypeId) REFERENCES eventtype(Id)",
        
        // customerusers_thirdparty -> customerusers
        "ALTER TABLE customerusers_thirdparty ADD CONSTRAINT FK_customerusers_thirdparty_UserId FOREIGN KEY (UserId) REFERENCES customerusers(Id)",
        
        // customerusers_thirdparty -> thirdparty
        "ALTER TABLE customerusers_thirdparty ADD CONSTRAINT FK_customerusers_thirdparty_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        
        // customerusers -> customeruserstype
        "ALTER TABLE customerusers ADD CONSTRAINT FK_customerusers_UserTypeId FOREIGN KEY (UserTypeId) REFERENCES customeruserstype(Id)",
        
        // customerusers -> customerusersstatus
        "ALTER TABLE customerusers ADD CONSTRAINT FK_customerusers_UserStatusId FOREIGN KEY (UserStatusId) REFERENCES customerusersstatus(Id)",
        
        // customeruserblsearchhistory -> customerusers
        "ALTER TABLE customeruserblsearchhistory ADD CONSTRAINT FK_customeruserblsearchhistory_CustomerUserId FOREIGN KEY (CustomerUserId) REFERENCES customerusers(Id)",
        
        // document -> documenttype
        "ALTER TABLE document ADD CONSTRAINT FK_document_DocumentTypeId FOREIGN KEY (DocumentTypeId) REFERENCES documenttype(Id)",
        
        // event -> jobfile
        "ALTER TABLE event ADD CONSTRAINT FK_event_JobFileId FOREIGN KEY (JobFileId) REFERENCES jobfile(Id)",
        
        // event -> eventtype
        "ALTER TABLE event ADD CONSTRAINT FK_event_EventTypeId FOREIGN KEY (EventTypeId) REFERENCES eventtype(Id)",
        
        // eventtype -> family
        "ALTER TABLE eventtype ADD CONSTRAINT FK_eventtype_FamilyId FOREIGN KEY (FamilyId) REFERENCES family(Id)",
        
        // invoice -> invoicestatus
        "ALTER TABLE invoice ADD CONSTRAINT FK_invoice_InvoiceStatusId FOREIGN KEY (InvoiceStatusId) REFERENCES invoicestatus(Id)",
        
        // invoiceitem -> invoice
        "ALTER TABLE invoiceitem ADD CONSTRAINT FK_invoiceitem_InvoiceId FOREIGN KEY (InvoiceId) REFERENCES invoice(Id)",
        
        // jobfile -> position
        "ALTER TABLE jobfile ADD CONSTRAINT FK_jobfile_PositionId FOREIGN KEY (PositionId) REFERENCES position(Id)",
        
        // jobfile -> thirdparty (ShippingLineId)
        "ALTER TABLE jobfile ADD CONSTRAINT FK_jobfile_ShippingLineId FOREIGN KEY (ShippingLineId) REFERENCES thirdparty(Id)",
        
        // payment -> paymenttype
        "ALTER TABLE payment ADD CONSTRAINT FK_payment_PaymentTypeId FOREIGN KEY (PaymentTypeId) REFERENCES paymenttype(Id)",
        
        // payment_invoice -> payment
        "ALTER TABLE payment_invoice ADD CONSTRAINT FK_payment_invoice_Payment_Id FOREIGN KEY (Payment_Id) REFERENCES payment(Id)",
        
        // payment_invoice -> invoice
        "ALTER TABLE payment_invoice ADD CONSTRAINT FK_payment_invoice_Invoice_Id FOREIGN KEY (Invoice_Id) REFERENCES invoice(Id)",
        
        // position -> row
        "ALTER TABLE position ADD CONSTRAINT FK_position_RowId FOREIGN KEY (RowId) REFERENCES `row`(Id)",
        
        // position -> yarditemtype
        "ALTER TABLE position ADD CONSTRAINT FK_position_YardItemTypeId FOREIGN KEY (YardItemTypeId) REFERENCES yarditemtype(Id)",
        
        // rate -> rateperiod
        "ALTER TABLE rate ADD CONSTRAINT FK_rate_RatePeriodId FOREIGN KEY (RatePeriodId) REFERENCES rateperiod(Id)",
        
        // row -> area
        "ALTER TABLE `row` ADD CONSTRAINT FK_row_AreaId FOREIGN KEY (AreaId) REFERENCES area(Id)",
        
        // thirdparty_thirdpartytype -> thirdparty
        "ALTER TABLE thirdparty_thirdpartytype ADD CONSTRAINT FK_thirdparty_thirdpartytype_ThirdPartyId FOREIGN KEY (ThirdPartyId) REFERENCES thirdparty(Id)",
        
        // thirdparty_thirdpartytype -> thirdpartytype
        "ALTER TABLE thirdparty_thirdpartytype ADD CONSTRAINT FK_thirdparty_thirdpartytype_ThirdPartyTypeId FOREIGN KEY (ThirdPartyTypeId) REFERENCES thirdpartytype(Id)",
    ];
    
    $success = 0;
    $errors = 0;
    
    echo "\nCréation des contraintes...\n\n";
    foreach ($constraints as $constraint) {
        if ($mysqli->query($constraint)) {
            $success++;
            echo "✓ " . substr($constraint, 0, 70) . "...\n";
        } else {
            $errors++;
            echo "✗ " . substr($constraint, 0, 70) . "...\n";
            echo "  Erreur: " . $mysqli->error . "\n";
        }
    }
    
    // Réactiver les contraintes
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "✓ Contraintes créées avec succès: $success\n";
    echo "✗ Erreurs: $errors\n";
    
    if ($errors === 0) {
        echo "✅ Toutes les contraintes de clés étrangères ont été recréées avec succès\n";
    } else {
        echo "⚠️  Certaines contraintes n'ont pas pu être créées (peut-être déjà existantes)\n";
    }
    
    $mysqli->close();
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
}
?>
