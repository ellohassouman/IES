<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
$result = mysqli_query($conn, "SHOW COLUMNS FROM jobfile");
echo "Colonnes de jobfile:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
mysqli_close($conn);
?>
