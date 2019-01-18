<?php

error_reporting(E_ALL);

include('config-inc.php');
require 'classes/whazzup.php';
$wz = new Whazzup('https://ivao.donatus.hu/whazzup.json.txt', true);

$sql = new mysqli($sql_server, $sql_username, $sql_password, $sql_database);
//$sql->autocommit(FALSE);
if ($sql->connect_error)
	die('Connection failed: ' . $sql->connect_error);

$atcs = $wz->GetATCs();
$pilots = $wz->GetPilots();

// Reading already online users from database
$alreadyOnlines = [];

$result = $sql->query('SELECT callsign, vid, connected_at FROM atcs WHERE disconnected_at IS NULL');
while ($row = $result->fetch_assoc())
{
	$alreadyOnlines[$row['callsign']] = [
		"vid" => $row['vid'],
		"connected_at" => $row['connected_at'],
	];
}

$result = $sql->query('SELECT callsign, vid, connected_at FROM pilots WHERE disconnected_at IS NULL');
while ($row = $result->fetch_assoc())
{
	$alreadyOnlines[$row['callsign']] = [
		"vid" => $row['vid'],
		"connected_at" => $row['connected_at'],
	];
}

$stmt_atc_i = $sql->prepare("INSERT INTO atcs (callsign, vid, rating, status, latitude, longitude, server, protocol, software, connected_at, frequency, radar_range, atis, atis_time, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");		
$stmt_atc_u = $sql->prepare("UPDATE atcs SET latitude=?, longitude=?, frequency=?, radar_range=?, atis=?, atis_time=?, updated_at=now() WHERE callsign=? AND connected_at=?");

$stmt_pilot_i = $sql->prepare("INSERT INTO pilots (callsign, vid, rating, status, latitude, longitude, server, protocol, software, connected_at, heading, on_ground, altitude, groundspeed, mode_a, fp_aircraft, fp_speed, fp_rfl, fp_departure, fp_destination, fp_alternate, fp_alternate2, fp_type, fp_pob, fp_route, fp_item18, fp_rev, fp_rule, fp_deptime, fp_eet, fp_endurance, sim_type, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt_pilot_u = $sql->prepare("UPDATE pilots SET latitude=?, longitude=?, heading=?, on_ground=?, altitude=?, groundspeed=?, mode_a=?, fp_aircraft=?, fp_speed=?, fp_rfl=?, fp_departure=?, fp_destination=?, fp_alternate=?, fp_alternate2=?, fp_type=?, fp_pob=?, fp_route=?, fp_item18=?, fp_rev=?, fp_rule=?, fp_deptime=?, fp_eet=?, fp_endurance=?, updated_at=now() WHERE callsign=? AND connected_at=?");

foreach ($atcs as $atc)
{
	if (array_key_exists($atc['callsign'], $alreadyOnlines))
	{
		// if user is already online, we're updating its details	
		$stmt_atc_u->bind_param('dddissss',
			$atc['latitude'],
			$atc['longitude'],
			$atc['frequency'],
			$atc['radar_range'],
			$atc['atis'],
			$atc['atis_time'],
			$atc['callsign'],
			$atc['connected_at']
		);
		$stmt_atc_u->execute();

		// if he/she's already online we're deleting the record from the dictionary - indicate somehow 
		$alreadyOnlines[$atc['callsign']] = null;
		echo $atc['callsign'] . ' already online ' . PHP_EOL;
	}
	else
	{
		// if user connected newly we're adding him to the database
		$stmt_atc_i->bind_param('sissddssssdiss',
			$atc['callsign'],
			$atc['vid'],
			$atc['rating'],
			$atc['status'],
			$atc['latitude'],
			$atc['longitude'],
			$atc['server'],
			$atc['protocol'],
			$atc['software'], 
			$atc['connected_at'],
			$atc['frequency'],
			$atc['radar_range'],
			$atc['atis'],
			$atc['atis_time']
		);

		$stmt_atc_i->execute();
		echo $atc['callsign'] . ' newly connected ' . PHP_EOL;


		// Checking whether he/she is validated KFOR controller or not
		if (Common::StartsWith($atc['callsign'], 'LHKR_') && Common::EndsWith($atc['callsign'], '_CTR'))
		{
			$result = $sql->query('SELECT vid, is_active FROM kfor WHERE is_active=true AND vid=' . $atc['vid']);
			if ($result->num_rows > 0)
			{
				echo $atc['callsign'] . " is KFOR validated controller";
			}
			else
			{
				echo $atc['callsign'] . " is not KFOR validated controller";
				$mail = Common::CallAPI('POST', $mail_apiurl, [
					'apikey' => $mail_apikey,
					'mail' => json_encode([
						'from' => [
							'name' => $mail_fromName,
							'email' => $mail_fromEmail
						],
						'to' => $mail_toEmail,
						'cc' => 'srta14@ivao.aero',
						'subject' => '[IVAO-HU] Non-validated KFOR controller online',
						'body' => Common::JsonToTable(json_encode($atc)) . "<p>Mail sent automatically, do not reply</p>"
					])
				]);
			}
		}
	}
}

foreach ($pilots as $pilot)
{	
	if (array_key_exists($pilot['callsign'], $alreadyOnlines))
	{
		$stmt_pilot_u = $sql->prepare("UPDATE pilots SET latitude=?, longitude=?, heading=?, on_ground=?, altitude=?, groundspeed=?, mode_a=?, fp_aircraft=?, fp_speed=?, fp_rfl=?, fp_departure=?, fp_destination=?, fp_alternate=?, fp_alternate2=?, fp_type=?, fp_pob=?, fp_route=?, fp_item18=?, fp_rev=?, fp_rule=?, fp_deptime=?, fp_eet=?, fp_endurance=?, updated_at=now() WHERE callsign=? AND connected_at=?");

		// if user is already online, we're updating its details	
		$stmt_pilot_u->bind_param('ddiiiiisssssssssssissiiss', 
			$pilot['latitude'],
			$pilot['longitude'],
			$pilot['heading'],
			$pilot['on_ground'],
			$pilot['altitude'],
			$pilot['groundspeed'],
			$pilot['mode_a'],
			$pilot['fp_aircraft'],
			$pilot['fp_speed'],
			$pilot['fp_rfl'],
			$pilot['fp_departure'],
			$pilot['fp_destination'],
			$pilot['fp_alternate'],
			$pilot['fp_alternate2'],
			$pilot['fp_type'],
			$pilot['fp_pob'],
			$pilot['fp_route'],
			$pilot['fp_item18'],
			$pilot['fp_rev'],
			$pilot['fp_rule'],
			$pilot['fp_deptime'],
			$pilot['fp_eet'],
			$pilot['fp_endurance'],
			$pilot['callsign'],
			$pilot['connected_at']
		);
		$stmt_pilot_u->execute();

		// if he/she's already online we're deleting the record from the dictionary - indicate somehow 
		$alreadyOnlines[$pilot['callsign']] = null;
		echo $pilot['callsign'] . ' already online ' . PHP_EOL;
	}
	else
	{
		$stmt_pilot_i = $sql->prepare("INSERT INTO pilots (callsign, vid, rating, status, latitude, longitude, server, protocol, software, connected_at, heading, on_ground, altitude, groundspeed, mode_a, fp_aircraft, fp_speed, fp_rfl, fp_departure, fp_destination, fp_alternate, fp_alternate2, fp_type, fp_pob, fp_route, fp_item18, fp_rev, fp_rule, fp_deptime, fp_eet, fp_endurance, sim_type, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

		$stmt_pilot_i->bind_param('sissddssssiiiiisssssssississiiss', 
			$pilot['callsign'],
			$pilot['vid'],
			$pilot['rating'],
			$pilot['status'],
			$pilot['latitude'],
			$pilot['longitude'],
			$pilot['server'],
			$pilot['protocol'],
			$pilot['software'], 
			$pilot['connected_at'],
			$pilot['heading'],
			$pilot['on_ground'],
			$pilot['altitude'],
			$pilot['groundspeed'],
			$pilot['mode_a'],
			$pilot['fp_aircraft'],
			$pilot['fp_speed'],
			$pilot['fp_rfl'],
			$pilot['fp_departure'],
			$pilot['fp_destination'],
			$pilot['fp_alternate'],
			$pilot['fp_alternate2'],
			$pilot['fp_type'],
			$pilot['fp_pob'],
			$pilot['fp_route'],
			$pilot['fp_item18'],
			$pilot['fp_rev'],
			$pilot['fp_rule'],
			$pilot['fp_deptime'],
			$pilot['fp_eet'],
			$pilot['fp_endurance'],
			$pilot['sim_type']
		);

		$stmt_pilot_i->execute();
		echo $pilot['callsign'] . ' newly connected ' . PHP_EOL;
	}
}

// existing users without cleared data in the dictionary mean he/she's not online anymore
$stmt_atc_d = $sql->prepare("UPDATE atcs SET disconnected_at=now(), updated_at=now() WHERE callsign=? AND connected_at=? AND vid=?");
$stmt_pilot_d = $sql->prepare("UPDATE pilots SET disconnected_at=now(), updated_at=now() WHERE callsign=? AND connected_at=? AND vid=?");

foreach ($alreadyOnlines as $callsign => $data)
{
	if ($data)
	{
		$stmt_atc_d->bind_param('ssi', $callsign, $data["connected_at"], $data["vid"]);
		$stmt_atc_d->execute();

		$stmt_pilot_d->bind_param('ssi', $callsign, $data["connected_at"], $data["vid"]);
		$stmt_pilot_d->execute();

		echo $callsign . ' disconnected ' . PHP_EOL;
	}
}

$stmt_atc_i->close();
$stmt_atc_u->close();
$stmt_pilot_i->close();
$stmt_pilot_u->close();
$stmt_atc_d->close();
$stmt_pilot_d->close();

$sql->commit();
$sql->close();