-- ============================================================================
-- MISE À JOUR PROCÉDURE AuthenticateUser
-- Pour supporter le paramètre isAdmin et le filtrage par type d'utilisateur
-- ============================================================================

DROP PROCEDURE IF EXISTS `AuthenticateUser`;

CREATE DEFINER=`root`@`localhost` PROCEDURE `AuthenticateUser` (
    IN `p_Email` VARCHAR(255),
    IN `p_IsAdmin` BOOLEAN
)
BEGIN
    IF p_IsAdmin THEN
        -- Pour les admins, retourner seulement les utilisateurs avec le type = 3
        SELECT 
            Id AS UserId, 
            CONCAT(FirstName, ' ', LastName) AS FullName, 
            UserName AS Email, 
            PasswordHash, 
            CustomerUsersTypeId AS UserType
        FROM customerusers
        WHERE UserName = p_Email AND CustomerUsersTypeId = 3
        LIMIT 1;
    ELSE
        -- Pour les utilisateurs normaux, retourner les utilisateurs avec le type != 3
        SELECT 
            Id AS UserId, 
            CONCAT(FirstName, ' ', LastName) AS FullName, 
            UserName AS Email, 
            PasswordHash, 
            CustomerUsersTypeId AS UserType
        FROM customerusers
        WHERE UserName = p_Email AND (CustomerUsersTypeId != 3 OR CustomerUsersTypeId IS NULL)
        LIMIT 1;
    END IF;
END;
