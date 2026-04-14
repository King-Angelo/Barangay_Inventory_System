<?php
include ('head.php');

session_destroy();
header('location: login.php');
?>