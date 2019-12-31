<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
header("Content-Type: application/json");
date_default_timezone_set('Etc/UTC');

include('config-inc.php');
require 'classes/whazzup.php';

function get_airport_data($icao)
{
	global $sqlnav;
	
	$querynav = $sqlnav->query("SELECT * FROM airports WHERE icao='" . $icao . "'");
	if ($rownav = $querynav->fetch_assoc())
	{
		return [
			'icao' => $icao,
			'name' => $rownav["name"],
			'lat' => $rownav["latitude"],
			'lon' => $rownav["longitude"],
			'arrivals' => [],
			'departures' => [],
		];
	}
	return null;
}

// Airport ICAO
$icao = isset($_GET['icao']) ? $_GET['icao'] : null;

if ($icao)
{
	$sql = new mysqli($sql_server, $sql_username, $sql_password, $sql_database);
	if ($sql->connect_error)
		die('Connection failed: ' . $sql->connect_error);
	$sql->query("SET time_zone = 'Etc/UTC'");

	$sqlnav = new mysqli($sql_server, $sql_username, $sql_password, $sql_database_nav);
	if ($sqlnav->connect_error)
		die('Connection failed: ' . $sqlnav->connect_error);


	
	$airport = get_airport_data($icao);
	
	if ($airport)
	{
		$query = $sql->query("SELECT * FROM pilots WHERE online = 1 AND fp_departure = '" . $icao . "'");
		while ($row = $query->fetch_assoc())
		{
			$airport['departures'][] = [
				'id' => intval($row['id']),
				'callsign' => $row['callsign'],
				'destination' => $row['fp_destination'],
			];
		}
		
		$query = $sql->query("SELECT * FROM pilots WHERE online = 1 AND fp_destination = '" . $icao . "'");
		while ($row = $query->fetch_assoc())
		{
			$airport['arrivals'][] = [
				'id' => intval($row['id']),
				'callsign' => $row['callsign'],
				'origin' => $row['fp_departure'],
			];
		}
		echo json_encode($airport);
	}
	
	$sql->close();
	$sqlnav->close();
}