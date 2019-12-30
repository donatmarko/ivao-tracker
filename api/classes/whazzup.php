<?php

require 'functions.php';

/**
 * whazzup.php
 *
 * Gets an always up-to-date IVAO Whazzup, and converts to human (and PHP)-readable format.
 *
 * @author     Donat Marko
 * @copyright  2018 Donatus
 */

class Whazzup
{
	private $url = '';
	private $jsonMode = false;

	private $fsTypes = [
 		0 => 'Unknown',
		1 => 'Microsoft Flight Simulator 95',
		2 => 'Microsoft Flight Simulator 98',
		3 => 'Microsoft Combat Flight Simulator',
		4 => 'Microsoft Flight Simulator 2000',
		5 => 'Microsoft Combat Flight Simulator 2',
		6 => 'Microsoft Flight Simulator 2002',
		7 => 'Microsoft Combat Flight Simulator 3',
		8 => 'Microsoft Flight Simulator 2004',
		9 => 'Microsoft Flight Simulator X',
		11 => 'X-Plane (unknown version)',
		12 => 'X-Plane 8.x',
		13 => 'X-Plane 9.x',
		14 => 'X-Plane 10.x',
		15 => 'PS1',
		16 => 'X-Plane 11.x',
		17 => 'X-Plane 12.x',
		20 => 'Fly!',
		21 => 'Fly! 2',
		25 => 'FlightGear',
		30 => 'Prepar3D'
	];

	private $pilotRatings = [
		1 => 'Observer',
		2 => 'FS1',
		3 => 'FS2',
		4 => 'FS3',
		5 => 'PP',
		6 => 'SPP',
		7 => 'CP',
		8 => 'ATP',
		9 => 'SFI',
		10 => 'CFI'
	];

	private $atcRatings = [
		1 => 'Observer',
		2 => 'AS1',
		3 => 'AS2',
		4 => 'AS3',
		5 => 'ADC',
		6 => 'APC',
		7 => 'ACC',
		8 => 'SEC',
		9 => 'SAI',
		10 => 'CAI'
	];

	private $userStatuses = [
		-2 => 'Deleted User',
		-1 => 'In Memoriam',
		0 => 'Suspended User',
		1 => 'Inactive User',
		2 => 'Active User',
		11 => 'Supervisor',
		12 => 'Administrator'
	];

	/**
	 * Just a simple constructor...
	 * ATTENTION!
	 * 		IVAO only allows 1 request per 3 minutes.
	 * 		Don't use the IVAO Whazzup source directly!
	 * 		Use e.g. https://ivao.donatus.hu/whazzup.txt
	 * @param string $url - IVAO Whazzup source
	 */
	public function __construct($url, $jsonMode = false)
	{
		$this->url = $url;
		$this->jsonMode = $jsonMode;
	}

	/**
	 * Downloading Whazzup and creating objects from it.
	 * @return string[] whazzup client object array
	 */
	public function GetAll()
	{
		$objs = array();

		if ($this->jsonMode)
		{
			$objs = json_decode(file_get_contents($this->url), true); 
		}
		else
		{
			$txt = preg_split('/\r\n|\r|\n/', file_get_contents($this->url));
			$section = '';

			foreach ($txt as $line)
			{
				if (Common::StartsWith($line, '!'))
				{
					$section = $line;
					continue;
				}
				if ($section === '!CLIENTS')
					$objs[] = $this->toObject($line);
			}
		}

		return $objs;
	}

	/**
	 * Creating client object (string associative array) from the whazzup line.
	 * @param string whazzup line
	 * @return string[] whazzup client object
	 */
	private function toObject($line)
	{
		$data = explode(':', $line);

		$a = array();
		$a['type'] = $data[3];
		$a['callsign'] = $data[0];
		$a['vid'] = (int)$data[1];
		// $a['name'] = $data[2];				
		$a['status'] = array_key_exists($data[40], $this->userStatuses) ? $this->userStatuses[$data[40]] : $data[40];
		$a['latitude'] = (float)$data[5];
		$a['longitude'] = (float)$data[6];
		$a['server'] = $data[14];
		$a['protocol'] = $data[15];
		$a['software'] = $data[38] . ' ' . $data[39];
		$a['connected_at'] = $data[37];		
		
		if ($data[3] === "ATC")
		{
			$a['frequency'] = (float)$data[4];
			$a['radar_range'] = (int)$data[19];
			$a['atis'] = str_replace('^§', '\n', Common::Utf8ize($data[35]));
			$a['atis_time'] = $data[36];
			$a['rating'] = array_key_exists($data[41], $this->atcRatings) ? $this->atcRatings[$data[41]] : $data[41];
		}

		if ($data[3] === "PILOT")
		{
			$a['heading'] = (int)$data[45];
			$a['on_ground'] = $data[46] === '1';
			$a['altitude'] = (int)$data[7];
			$a['groundspeed'] = (int)$data[8];
			$a['mode_a'] = (int)$data[17];
			$a['fp_aircraft'] = $data[9];
			$a['fp_speed'] = $data[10];
			$a['fp_departure'] = $data[11];
			$a['fp_rfl'] = $data[12];
			$a['fp_destination'] = $data[13];
			$a['fp_alternate'] = $data[28];
			$a['fp_alternate2'] = $data[42];
			$a['fp_type'] = $data[43];
			$a['fp_pob'] = (int)$data[44];
			$a['fp_item18'] = $data[29];
			$a['fp_route'] = $data[30];
			$a['fp_rev'] = (int)$data[20];
			$a['fp_rule'] = $data[21];
			$a['fp_deptime'] = $data[22];
			$a['fp_eet'] = intval($data[24]) * 60 + intval($data[25]);
			$a['fp_endurance'] = intval($data[26]) * 60 + intval($data[27]);	
			$a['sim_type'] = array_key_exists($data[47], $this->fsTypes) ? $this->fsTypes[$data[47]] : $data[47];
			$a['rating'] = array_key_exists($data[41], $this->pilotRatings) ? $this->pilotRatings[$data[41]] : $data[41];
		}
		return $a;
	}

	/**
	 * Getting the pilots from the online objects.
	 * @return string[] whazzup client object array
	 */
	public function GetPilots()
	{
		$pilots = array();
		foreach ($this->GetAll() as $obj)
		{
			if ($obj['type'] === 'PILOT')
				$pilots[] = $obj;
		}
		return $pilots;
	}

	/**
	 * Getting the ATCs from the online objects.
	 * @return string[] whazzup client object array
	 */
	public function GetATCs()
	{
		$atcs = array();
		foreach ($this->GetAll() as $obj)
		{
			if ($obj['type'] === 'ATC')
				$atcs[] = $obj;
		}
		return $atcs;
	}

	/**
	 * Formatting the whole client list to JSON.
	 * @return string json
	 */
	public function GetJSON()
	{
		// return json_encode(['atcs' => $this->GetATCs(), 'pilots' => $this->GetPilots()]);
		return json_encode(array_merge(Common::convert_from_latin1_to_utf8_recursively($this->getATCs()), Common::convert_from_latin1_to_utf8_recursively($this->getPilots())));
	}
}