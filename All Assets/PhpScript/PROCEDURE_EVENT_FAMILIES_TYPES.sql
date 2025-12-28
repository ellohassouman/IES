-- ============================================
-- Stored Procedures for Event Families and Types
-- ============================================

-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS `GetAllEventFamilies`;
DROP PROCEDURE IF EXISTS `GetAllEventTypes`;

-- ============================================
-- GetAllEventFamilies: Retrieve all event families
-- Returns: Id, Label
-- ============================================
CREATE PROCEDURE `GetAllEventFamilies` ()  
BEGIN
    SELECT 
        f.`Id`,
        f.`Label`
    FROM `family` f
    ORDER BY f.`Label` ASC;
END;

-- ============================================
-- GetAllEventTypes: Retrieve all event types
-- Returns: Id, Code, Label, FamilyId
-- ============================================
CREATE PROCEDURE `GetAllEventTypes` ()  
BEGIN
    SELECT 
        et.`Id`,
        et.`Code`,
        et.`Label`,
        et.`FamilyId`
    FROM `eventtype` et
    ORDER BY et.`Label` ASC;
END;
