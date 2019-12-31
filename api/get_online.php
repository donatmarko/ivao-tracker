<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
header("Content-Type: application/json");
date_default_timezone_set('Etc/UTC');

include('config-inc.php');
require 'classes/whazzup.php';

function add_airport_if_not_exists($icao)
{
	global $airports, $sqlnav;
	
	if (!array_key_exists($icao, $airports))
	{
		$querynav = $sqlnav->query("SELECT * FROM airports WHERE icao='" . $icao . "'");
		if ($rownav = $querynav->fetch_assoc())
		{
			$airports[$icao] = [
				'icao' => $icao,
				'name' => $rownav["name"],
				'lat' => $rownav["latitude"],
				'lon' => $rownav["longitude"],
			];
		}
	}
}

// Session ID
$id = isset($_GET['id']) ? $_GET['id'] : null;

$sql = new mysqli($sql_server, $sql_username, $sql_password, $sql_database);
if ($sql->connect_error)
	die('Connection failed: ' . $sql->connect_error);
$sql->query("SET time_zone = 'Etc/UTC'");

$sqlnav = new mysqli($sql_server, $sql_username, $sql_password, $sql_database_nav);
if ($sqlnav->connect_error)
	die('Connection failed: ' . $sqlnav->connect_error);



$query = $sql->query("SELECT *, 'PILOT' AS type FROM pilots WHERE online = 1");

$airports = [];
$rows = [];
while ($row = $query->fetch_assoc())
{
	add_airport_if_not_exists($row['fp_departure']);
	add_airport_if_not_exists($row['fp_destination']);
	$rows[] = $row;
}

$sql->close();
$sqlnav->close();

echo json_encode(['sessions' => $rows, 'airports' => $airports]);