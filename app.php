<?php

session_start();

require_once('init.php');

$SenFramework = new \SenFramework\SenFramework($senConfig);
$SenFramework->Handle();

?>