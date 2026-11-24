<?php
session_start();

unset($_SESSION['cliente']);    
session_regenerate_id(true);

header('Location: login.php');
exit;
