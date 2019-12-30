<?php

/**
 * common_functions.php
 *
 * Some commonly used PHP functions.
 *
 * @author     Donat Marko
 * @copyright  2018 Donatus
 * @license    MIT https://opensource.org/licenses/MIT
 */
class Common
{
	/**
	 * Decides whether the given string starts with the given parameter or not.
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function StartsWith($haystack, $needle)
	{
     	$length = strlen($needle);
     	return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * Decides whether the given string ends with the given parameter or not.
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function EndsWith($haystack, $needle)
	{
    	$length = strlen($needle);
	    return $length === 0 || (substr($haystack, -$length) === $needle);
	}

	/**
	 * Converts characters to UTF8 format.
	 * @param object $d
	 * @return object
	 */
	public static function Utf8ize($d)
	{
		if (is_array($d))
		{
			foreach ($d as $k => $v)
				$d[$k] = utf8ize($v);
		} else if (is_string ($d))
			return utf8_encode($d);
		return $d;
	}

	/**
	 * Calling any type of API.
	 * @param string method POST, PUT, GET, DELETE, PATCH
	 * @param string url
	 * @param mixed data
	 * @return mixed
	 */
	public static function CallAPI($method, $url, $data = false)
	{
		$curl = curl_init();

		switch ($method)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		// Optional Authentication:
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "username:password");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'curl/7.59.0/donatmarko/ivao-atclogger');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	/**
	 * Converts a JSON object to HTML table.
	 * @param string json
	 * @param bool borders
	 * @return string
	 */
	public static function JsonToTable($json = '', $borders = true)
	{
		$a = '';
		$a .= $borders ? '<table border="1">' : '<table>';
		$data = json_decode($json);

		foreach ($data as $key => $value)
			$a .= '<tr><th>' . $key . '</th><td>' . $value . '</td></tr>';

		$a .= '</table>';
		return $a;
	}

	public static function convert_from_latin1_to_utf8_recursively($dat)
	{
		if (is_string($dat)) {
			return utf8_encode($dat);
		} elseif (is_array($dat)) {
			$ret = [];
			foreach ($dat as $i => $d) $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);
			return $ret;
		} elseif (is_object($dat)) {
			foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);
			return $dat;
		} else {
			return $dat;
		}
	}
}