<?php
session_start();
session_destroy();
header("Location: anasayfa.php");
exit();
?>