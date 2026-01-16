<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

$result = mysqli_query($conn, "SHOW CREATE PROCEDURE GetInvoiceDetails");
$row = mysqli_fetch_assoc($result);
echo $row['Create Procedure'];

mysqli_close($conn);
?>
