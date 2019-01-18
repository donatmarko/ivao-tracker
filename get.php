<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
header("Content-Type: application/json");

include('config-inc.php');
require 'classes/whazzup.php';

// 1-online, 0-tracker
$type = isset($_GET['t']) ? $_GET['t'] : 0;

// VID
$vid = isset($_GET['vid']) && !empty($_GET['vid']) ? $_GET['vid'] : null;

// Callsign
$callsign = isset($_GET['cs']) && !empty($_GET['cs'] && $_GET['cs'] !== '%') ? strtoupper($_GET['cs']) : null;

// Session ID
$id = isset($_GET['id']) ? $_GET['id'] : null;
$client = isset($_GET['cl']) ? $_GET['cl'] : null;

// ONLINE sessions
if ($type == 1)
{
	$wz = new Whazzup('https://ivao.donatus.hu/whazzup.json.txt', true);
	$result = [];
	
	foreach ($wz->GetAll() as $client)
	{
		if ($vid)
		{
			if ($client["vid"] == $vid)
				$result[] = $client;
		}
		if ($callsign)
		{
			if ($client["callsign"] == $callsign)
				$result[] = $client;
		}
	}

	if (count($result) == 1)
		echo json_encode($result[0]);
	else
		echo json_encode($result);
}

// TRACKED sessions
if ($type == 0)
{
	$sql = new mysqli($sql_server, $sql_username, $sql_password, $sql_database);
	if ($sql->connect_error)
		die('Connection failed: ' . $sql->connect_error);

	$sqlnav = new mysqli($sql_server, $sql_username, $sql_password, $sql_database_nav);
	if ($sqlnav->connect_error)
		die('Connection failed: ' . $sqlnav->connect_error);

	$sessions = [];
	$airports = [];

	$queryPilot = 'SELECT "PILOT" AS type, id, callsign, vid, rating, server, software, connected_at, disconnected_at, mode_a, fp_aircraft, fp_speed, fp_rfl, fp_departure, fp_destination, fp_alternate, fp_alternate2, fp_type, fp_pob, fp_route, fp_item18, fp_rule, fp_deptime, fp_eet, fp_endurance, sim_type, updated_at FROM pilots';
	$queryATC = 'SELECT "ATC" AS type, id, callsign, vid, rating, server, software, connected_at, disconnected_at, latitude, longitude, radar_range, frequency, atis, updated_at FROM atcs';
	
	if ($id && $client !== null)
	{
		$queryPilot .= ' WHERE id=' . $id;
		$queryATC .= ' WHERE id=' . $id;
	}
	else if ($vid && $callsign)
	{
		$queryPilot .= ' WHERE vid=' . $vid . ' AND callsign="' . $callsign . '"';
		$queryATC .= ' WHERE vid=' . $vid . ' AND callsign="' . $callsign . '"';
	}
	else if ($vid)
	{
		$queryPilot .= ' WHERE vid=' . $vid;
		$queryATC .= ' WHERE vid=' . $vid;
	}
	else if ($callsign)
	{
		$queryPilot .= ' WHERE callsign LIKE "' . $callsign . '"';
		$queryATC .= ' WHERE callsign LIKE "' . $callsign . '"';
	}
	else
		die(json_encode(['error' => 'incorrect filter']));

	$queryPilot .= ' ORDER BY connected_at DESC';
	$queryATC .= ' ORDER BY connected_at DESC';

	if ($client == 0 || $client == 1)
	{
		$queryATC = $sql->query($queryATC);
		while ($row = $queryATC->fetch_assoc())
		{
			if ($row["disconnected_at"] != "0000-00-00 00:00:00")
			{
				$duration = strtotime($row["disconnected_at"]) - strtotime($row["connected_at"]);
				$data["online"] = false;
			}
			else
			{
				$duration = time() - strtotime($row["connected_at"]);
				$data["online"] = true;
			}
			$data["duration"] = date("H:i:s", $duration);

			$sessions[] = array_merge($row, $data);
		}
	}
	
	if ($client == 0 || $client == 2)
	{
		$queryPilot = $sql->query($queryPilot);
		while ($row = $queryPilot->fetch_assoc())
		{
			if (!array_key_exists($row['fp_departure'], $airports))
			{
				$querynav = $sqlnav->query("SELECT * FROM airports WHERE icao='" . $row["fp_departure"] . "'");
				if ($rownav = $querynav->fetch_assoc())
				{
					$airports[$row["fp_departure"]] = [
						"name" => $rownav["name"],
						"lat" => $rownav["latitude"],
						"lon" => $rownav["longitude"],
						"arrivals" => 0,
						"departures" => 0,
					];
				}
			}
			if (!array_key_exists($row['fp_destination'], $airports))
			{
				$querynav = $sqlnav->query("SELECT * FROM airports WHERE icao='" . $row["fp_destination"] . "'");
				if ($rownav = $querynav->fetch_assoc())
				{
					$airports[$row["fp_destination"]] = [
						"name" => $rownav["name"],
						"lat" => $rownav["latitude"],
						"lon" => $rownav["longitude"],
						"arrivals" => 0,
						"departures" => 0,
					];
				}
			}

			if (array_key_exists($row["fp_departure"], $airports))
			{
				$data["departure"] = [
					"lat" => $airports[$row["fp_departure"]]["lat"],
					"lon" => $airports[$row["fp_departure"]]["lon"],
				];
				$airports[$row["fp_departure"]]["departures"]++;
			}
			if (array_key_exists($row["fp_destination"], $airports))
			{
				$data["destination"] = [
					"lat" => $airports[$row["fp_destination"]]["lat"],
					"lon" => $airports[$row["fp_destination"]]["lon"],
				];
				$airports[$row["fp_destination"]]["arrivals"]++;
			}

			if ($row["disconnected_at"] != "0000-00-00 00:00:00")
			{
				$duration = strtotime($row["disconnected_at"]) - strtotime($row["connected_at"]);
				$data["online"] = false;
			}
			else
			{
				$duration = time() - strtotime($row["connected_at"]);
				$data["online"] = true;
			}
			$data["duration"] = date("H:i:s", $duration);

			$sessions[] = array_merge($row, $data);
		}
	}

	$sql->close();
	$sqlnav->close();

	if ($id && count($sessions) == 1)
	{
		echo json_encode($sessions[0]);
	}
	else
	{
		$airportList = [];
		foreach ($airports as $key => $value)
			$airportList[] = array_merge(["icao" => $key], $value);

		function date_compare($a, $b)
		{
			$t1 = strtotime($a['connected_at']);
			$t2 = strtotime($b['connected_at']);
			return $t1 < $t2;
		}    
		usort($sessions, 'date_compare');
			
		echo json_encode(["sessions" => $sessions, "airports" => $airportList]);
	}
}