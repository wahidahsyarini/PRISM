
<?php
require_once 'config.php';

if ($link && mysqli_ping($link)) {
    echo "SUCCESS: Database PRISM connected!";
} else {
    echo "Database connection failed.";
}
?>
