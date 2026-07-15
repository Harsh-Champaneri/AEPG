<?php

session_start();
session_unset();
session_destroy();

header("location:Sign_In_Form.php");
exit();

?>