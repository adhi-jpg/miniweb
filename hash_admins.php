<?php
// hash_admins.php
echo "Admin1: " . password_hash("admin123", PASSWORD_DEFAULT) . "<br>";
echo "Admin2: " . password_hash("admin456", PASSWORD_DEFAULT) . "<br>";
echo "Admin3: " . password_hash("admin789", PASSWORD_DEFAULT) . "<br>";
echo "Admin4: " . password_hash("mdcadmin", PASSWORD_DEFAULT) . "<br>";
?>
