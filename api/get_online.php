<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
header("Content-Type: application/json");
date_default_timezone_set('Etc/UTC');

include('config-inc.php');
require 'classes/whazzup.php';

// VID
$vid = isset($_GET['vid']) && !empty($_GET['vid']) ? $_GET['vid'] : null;

// Callsign
$callsign = isset($_GET['cs']) && !empty($_GET['cs'] && $_GET['cs'] !== '%') ? strtoupper($_GET['cs']) : null;

$wz = json_decode(file_get_contents('https://api.donatus.hu/oavi/json/whazzup.json'), true);
$wz = array_merge($wz["atcs"], $wz["pilots"]);
$result = [];

// if filtered to a specific member
if ($vid || $callsign)
{
	foreach ($wz as $client)
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
	echo json_encode($result);
}
else	// if we like to return every online member
	echo json_encode($wz);