<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
header("Content-Type: application/json");
date_default_timezone_set('Etc/UTC');

include('config-inc.php');
require 'classes/whazzup.php';

// Session ID
$id = isset($_GET['id']) ? $_GET['id'] : null;

$sql = new mysqli($sql_server, $sql_username, $sql_password, $sql_database);
if ($sql->connect_error)
	die('Connection failed: ' . $sql->connect_error);
$sql->query("SET time_zone = 'Etc/UTC'");

$query = $sql->query("SELECT *, 'PILOT' AS type FROM pilots WHERE online = 1");

$rows = [];
while ($row = $query->fetch_assoc())
{
	$rows[] = $row;
}
echo json_encode($rows);

$sql->close();
