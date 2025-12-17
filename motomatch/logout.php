<?php
// logout.php (New file)
session_start();
session_destroy();
header("Location: home.html");
?>