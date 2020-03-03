<?php

namespace SenFramework;

class PHPBBFunctions {
	
	/**
	* Return unique id
	*/
	public function unique_id()
	{
		return bin2hex(random_bytes(8));
	}
	
	/**
	* Returns the first block of the specified IPv6 address and as many additional
	* ones as specified in the length paramater.
	* If length is zero, then an empty string is returned.
	* If length is greater than 3 the complete IP will be returned
	*/
	public function short_ipv6($ip, $length)
	{
		if ($length < 1)
		{
			return '';
		}

		// extend IPv6 addresses
		$blocks = substr_count($ip, ':') + 1;
		if ($blocks < 9)
		{
			$ip = str_replace('::', ':' . str_repeat('0000:', 9 - $blocks), $ip);
		}
		if ($ip[0] == ':')
		{
			$ip = '0000' . $ip;
		}
		if ($length < 4)
		{
			$ip = implode(':', array_slice(explode(':', $ip), 0, 1 + $length));
		}

		return $ip;
	}
	
	/**
	* This function returns a regular expression pattern for commonly used expressions
	* Use with / as delimiter for email mode and # for url modes
	* mode can be: email|bbcode_htm|url|url_inline|www_url|www_url_inline|relative_url|relative_url_inline|ipv4|ipv6
	*/
	public function get_preg_expression($mode) {
		switch ($mode)
		{
			case 'email':
				// Regex written by James Watts and Francisco Jose Martin Moreno
				// http://fightingforalostcause.net/misc/2006/compare-email-regex.php
				return '((?:[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*(?:[\w\!\#$\%\'\*\+\-\/\=\?\^\`{\|\}\~]|&amp;)+)@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,63})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)';
			break;

			case 'bbcode_htm':
				return array(
					'#<!\-\- e \-\-><a href="mailto:(.*?)">.*?</a><!\-\- e \-\->#',
					'#<!\-\- l \-\-><a (?:class="[\w-]+" )?href="(.*?)(?:(&amp;|\?)sid=[0-9a-f]{32})?">.*?</a><!\-\- l \-\->#',
					'#<!\-\- ([mw]) \-\-><a (?:class="[\w-]+" )?href="http://(.*?)">\2</a><!\-\- \1 \-\->#',
					'#<!\-\- ([mw]) \-\-><a (?:class="[\w-]+" )?href="(.*?)">.*?</a><!\-\- \1 \-\->#',
					'#<!\-\- s(.*?) \-\-><img src="\{SMILIES_PATH\}\/.*? \/><!\-\- s\1 \-\->#',
					'#<!\-\- .*? \-\->#s',
					'#<.*?>#s',
				);
			break;

			// Whoa these look impressive!
			// The code to generate the following two regular expressions which match valid IPv4/IPv6 addresses
			// can be found in the develop directory
			case 'ipv4':
				return '#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#';
			break;

			case 'ipv6':
				return '#^(?:(?:(?:[\dA-F]{1,4}:){6}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:::(?:[\dA-F]{1,4}:){0,5}(?:[\dA-F]{1,4}(?::[\dA-F]{1,4})?|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:):(?:[\dA-F]{1,4}:){4}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,2}:(?:[\dA-F]{1,4}:){3}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,3}:(?:[\dA-F]{1,4}:){2}(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,4}:(?:[\dA-F]{1,4}:)(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,5}:(?:[\dA-F]{1,4}:[\dA-F]{1,4}|(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])))|(?:(?:[\dA-F]{1,4}:){1,6}:[\dA-F]{1,4})|(?:(?:[\dA-F]{1,4}:){1,7}:)|(?:::))$#i';
			break;

			case 'url':
				// generated with regex_idn.php file in the develop folder
				return "[a-z][a-z\d+\-.]*(?<!javascript):/{2}(?:(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-z0-9.]+:[a-z0-9.]+:[a-z0-9.:]+\])(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'url_http':
				// generated with regex_idn.php file in the develop folder
				return "http[s]?:/{2}(?:(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-z0-9.]+:[a-z0-9.]+:[a-z0-9.:]+\])(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'url_inline':
				// generated with regex_idn.php file in the develop folder
				return "[a-z][a-z\d+]*(?<!javascript):/{2}(?:(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-z0-9.]+:[a-z0-9.]+:[a-z0-9.:]+\])(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'www_url':
				// generated with regex_idn.php file in the develop folder
				return "www\.(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})+(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'www_url_inline':
				// generated with regex_idn.php file in the develop folder
				return "www\.(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'relative_url':
				// generated with regex_idn.php file in the develop folder
				return "(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'()*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'relative_url_inline':
				// generated with regex_idn.php file in the develop folder
				return "(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			break;

			case 'table_prefix':
				return '#^[a-zA-Z][a-zA-Z0-9_]*$#';
			break;

			// Matches the predecing dot
			case 'path_remove_dot_trailing_slash':
				return '#^(?:(\.)?)+(?:(.+)?)+(?:([\\/\\\])$)#';
			break;

			case 'semantic_version':
				// Regular expression to match semantic versions by http://rgxdb.com/
				return '/(?<=^[Vv]|^)(?:(?<major>(?:0|[1-9](?:(?:0|[1-9])+)*))[.](?<minor>(?:0|[1-9](?:(?:0|[1-9])+)*))[.](?<patch>(?:0|[1-9](?:(?:0|[1-9])+)*))(?:-(?<prerelease>(?:(?:(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?|(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?)|(?:0|[1-9](?:(?:0|[1-9])+)*))(?:[.](?:(?:(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?|(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?)|(?:0|[1-9](?:(?:0|[1-9])+)*)))*))?(?:[+](?<build>(?:(?:(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?|(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?)|(?:(?:0|[1-9])+))(?:[.](?:(?:(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?|(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)(?:[A-Za-z]|-)(?:(?:(?:0|[1-9])|(?:[A-Za-z]|-))+)?)|(?:(?:0|[1-9])+)))*))?)$/';
			break;
		}

		return '';
	}
	
	/**
	* Normalises an internet protocol address,
	* also checks whether the specified address is valid.
	*
	* IPv4 addresses are returned 'as is'.
	*
	* IPv6 addresses are normalised according to
	*	A Recommendation for IPv6 Address Text Representation
	*	http://tools.ietf.org/html/draft-ietf-6man-text-addr-representation-07
	*
	* @param string $address	IP address
	*
	* @return mixed		false if specified address is not valid,
	*					string otherwise
	*/
	public function phpbb_ip_normalise($address)
	{
		$address = trim($address);

		if (empty($address) || !is_string($address))
		{
			return false;
		}

		if (preg_match(get_preg_expression('ipv4'), $address))
		{
			return $address;
		}

		return phpbb_inet_ntop(phpbb_inet_pton($address));
	}

	/**
	* Wrapper for inet_ntop()
	*
	* Converts a packed internet address to a human readable representation
	* inet_ntop() is supported by PHP since 5.1.0, since 5.3.0 also on Windows.
	*
	* @param string $in_addr	A 32bit IPv4, or 128bit IPv6 address.
	*
	* @return mixed		false on failure,
	*					string otherwise
	*/
	public function phpbb_inet_ntop($in_addr)
	{
		$in_addr = bin2hex($in_addr);

		switch (strlen($in_addr))
		{
			case 8:
				return implode('.', array_map('hexdec', str_split($in_addr, 2)));

			case 32:
				if (substr($in_addr, 0, 24) === '00000000000000000000ffff')
				{
					return phpbb_inet_ntop(pack('H*', substr($in_addr, 24)));
				}

				$parts = str_split($in_addr, 4);
				$parts = preg_replace('/^0+(?!$)/', '', $parts);
				$ret = implode(':', $parts);

				$matches = array();
				preg_match_all('/(?<=:|^)(?::?0){2,}/', $ret, $matches, PREG_OFFSET_CAPTURE);
				$matches = $matches[0];

				if (empty($matches))
				{
					return $ret;
				}

				$longest_match = '';
				$longest_match_offset = 0;
				foreach ($matches as $match)
				{
					if (strlen($match[0]) > strlen($longest_match))
					{
						$longest_match = $match[0];
						$longest_match_offset = $match[1];
					}
				}

				$ret = substr_replace($ret, '', $longest_match_offset, strlen($longest_match));

				if ($longest_match_offset == strlen($ret))
				{
					$ret .= ':';
				}

				if ($longest_match_offset == 0)
				{
					$ret = ':' . $ret;
				}

				return $ret;

			default:
				return false;
		}
	}

	/**
	* Wrapper for inet_pton()
	*
	* Converts a human readable IP address to its packed in_addr representation
	* inet_pton() is supported by PHP since 5.1.0, since 5.3.0 also on Windows.
	*
	* @param string $address	A human readable IPv4 or IPv6 address.
	*
	* @return mixed		false if address is invalid,
	*					in_addr representation of the given address otherwise (string)
	*/
	public function phpbb_inet_pton($address)
	{
		$ret = '';
		if (preg_match(get_preg_expression('ipv4'), $address))
		{
			foreach (explode('.', $address) as $part)
			{
				$ret .= ($part <= 0xF ? '0' : '') . dechex($part);
			}

			return pack('H*', $ret);
		}

		if (preg_match(get_preg_expression('ipv6'), $address))
		{
			$parts = explode(':', $address);
			$missing_parts = 8 - count($parts) + 1;

			if (substr($address, 0, 2) === '::')
			{
				++$missing_parts;
			}

			if (substr($address, -2) === '::')
			{
				++$missing_parts;
			}

			$embedded_ipv4 = false;
			$last_part = end($parts);

			if (preg_match(get_preg_expression('ipv4'), $last_part))
			{
				$parts[count($parts) - 1] = '';
				$last_part = phpbb_inet_pton($last_part);
				$embedded_ipv4 = true;
				--$missing_parts;
			}

			foreach ($parts as $i => $part)
			{
				if (strlen($part))
				{
					$ret .= str_pad($part, 4, '0', STR_PAD_LEFT);
				}
				else if ($i && $i < count($parts) - 1)
				{
					$ret .= str_repeat('0000', $missing_parts);
				}
			}

			$ret = pack('H*', $ret);

			if ($embedded_ipv4)
			{
				$ret .= $last_part;
			}

			return $ret;
		}

		return false;
	}
	
	/**
	* Case folds a unicode string as per Unicode 5.0, section 3.13
	*
	* @param	string	$text	text to be case folded
	* @param	string	$option	determines how we will fold the cases
	* @return	string			case folded text
	*/
	public function utf8_case_fold($text, $option = 'full')
	{
		static $uniarray = array();

		// common is always set
		if (!isset($uniarray['c']))
		{
			$uniarray['c'] = include(dirname(__FILE__) . '/utf/case_fold_c.php');
		}

		// only set full if we need to
		if ($option === 'full' && !isset($uniarray['f']))
		{
			$uniarray['f'] = include(dirname(__FILE__) . '/utf/case_fold_f.php');
		}

		// only set simple if we need to
		if ($option !== 'full' && !isset($uniarray['s']))
		{
			$uniarray['s'] = include(dirname(__FILE__) . '/utf/case_fold_s.php');
		}

		// common is always replaced
		$text = strtr($text, $uniarray['c']);

		if ($option === 'full')
		{
			// full replaces a character with multiple characters
			$text = strtr($text, $uniarray['f']);
		}
		else
		{
			// simple replaces a character with another character
			$text = strtr($text, $uniarray['s']);
		}

		return $text;
	}
	
	/**
	* Takes the input and does a "special" case fold. It does minor normalization
	* and returns NFKC compatable text
	*
	* @param	string	$text	text to be case folded
	* @param	string	$option	determines how we will fold the cases
	* @return	string			case folded text
	*/
	function utf8_case_fold_nfkc($text, $option = 'full')
	{
		static $fc_nfkc_closure = array(
			"\xCD\xBA"	=> "\x20\xCE\xB9",
			"\xCF\x92"	=> "\xCF\x85",
			"\xCF\x93"	=> "\xCF\x8D",
			"\xCF\x94"	=> "\xCF\x8B",
			"\xCF\xB2"	=> "\xCF\x83",
			"\xCF\xB9"	=> "\xCF\x83",
			"\xE1\xB4\xAC"	=> "\x61",
			"\xE1\xB4\xAD"	=> "\xC3\xA6",
			"\xE1\xB4\xAE"	=> "\x62",
			"\xE1\xB4\xB0"	=> "\x64",
			"\xE1\xB4\xB1"	=> "\x65",
			"\xE1\xB4\xB2"	=> "\xC7\x9D",
			"\xE1\xB4\xB3"	=> "\x67",
			"\xE1\xB4\xB4"	=> "\x68",
			"\xE1\xB4\xB5"	=> "\x69",
			"\xE1\xB4\xB6"	=> "\x6A",
			"\xE1\xB4\xB7"	=> "\x6B",
			"\xE1\xB4\xB8"	=> "\x6C",
			"\xE1\xB4\xB9"	=> "\x6D",
			"\xE1\xB4\xBA"	=> "\x6E",
			"\xE1\xB4\xBC"	=> "\x6F",
			"\xE1\xB4\xBD"	=> "\xC8\xA3",
			"\xE1\xB4\xBE"	=> "\x70",
			"\xE1\xB4\xBF"	=> "\x72",
			"\xE1\xB5\x80"	=> "\x74",
			"\xE1\xB5\x81"	=> "\x75",
			"\xE1\xB5\x82"	=> "\x77",
			"\xE2\x82\xA8"	=> "\x72\x73",
			"\xE2\x84\x82"	=> "\x63",
			"\xE2\x84\x83"	=> "\xC2\xB0\x63",
			"\xE2\x84\x87"	=> "\xC9\x9B",
			"\xE2\x84\x89"	=> "\xC2\xB0\x66",
			"\xE2\x84\x8B"	=> "\x68",
			"\xE2\x84\x8C"	=> "\x68",
			"\xE2\x84\x8D"	=> "\x68",
			"\xE2\x84\x90"	=> "\x69",
			"\xE2\x84\x91"	=> "\x69",
			"\xE2\x84\x92"	=> "\x6C",
			"\xE2\x84\x95"	=> "\x6E",
			"\xE2\x84\x96"	=> "\x6E\x6F",
			"\xE2\x84\x99"	=> "\x70",
			"\xE2\x84\x9A"	=> "\x71",
			"\xE2\x84\x9B"	=> "\x72",
			"\xE2\x84\x9C"	=> "\x72",
			"\xE2\x84\x9D"	=> "\x72",
			"\xE2\x84\xA0"	=> "\x73\x6D",
			"\xE2\x84\xA1"	=> "\x74\x65\x6C",
			"\xE2\x84\xA2"	=> "\x74\x6D",
			"\xE2\x84\xA4"	=> "\x7A",
			"\xE2\x84\xA8"	=> "\x7A",
			"\xE2\x84\xAC"	=> "\x62",
			"\xE2\x84\xAD"	=> "\x63",
			"\xE2\x84\xB0"	=> "\x65",
			"\xE2\x84\xB1"	=> "\x66",
			"\xE2\x84\xB3"	=> "\x6D",
			"\xE2\x84\xBB"	=> "\x66\x61\x78",
			"\xE2\x84\xBE"	=> "\xCE\xB3",
			"\xE2\x84\xBF"	=> "\xCF\x80",
			"\xE2\x85\x85"	=> "\x64",
			"\xE3\x89\x90"	=> "\x70\x74\x65",
			"\xE3\x8B\x8C"	=> "\x68\x67",
			"\xE3\x8B\x8E"	=> "\x65\x76",
			"\xE3\x8B\x8F"	=> "\x6C\x74\x64",
			"\xE3\x8D\xB1"	=> "\x68\x70\x61",
			"\xE3\x8D\xB3"	=> "\x61\x75",
			"\xE3\x8D\xB5"	=> "\x6F\x76",
			"\xE3\x8D\xBA"	=> "\x69\x75",
			"\xE3\x8E\x80"	=> "\x70\x61",
			"\xE3\x8E\x81"	=> "\x6E\x61",
			"\xE3\x8E\x82"	=> "\xCE\xBC\x61",
			"\xE3\x8E\x83"	=> "\x6D\x61",
			"\xE3\x8E\x84"	=> "\x6B\x61",
			"\xE3\x8E\x85"	=> "\x6B\x62",
			"\xE3\x8E\x86"	=> "\x6D\x62",
			"\xE3\x8E\x87"	=> "\x67\x62",
			"\xE3\x8E\x8A"	=> "\x70\x66",
			"\xE3\x8E\x8B"	=> "\x6E\x66",
			"\xE3\x8E\x8C"	=> "\xCE\xBC\x66",
			"\xE3\x8E\x90"	=> "\x68\x7A",
			"\xE3\x8E\x91"	=> "\x6B\x68\x7A",
			"\xE3\x8E\x92"	=> "\x6D\x68\x7A",
			"\xE3\x8E\x93"	=> "\x67\x68\x7A",
			"\xE3\x8E\x94"	=> "\x74\x68\x7A",
			"\xE3\x8E\xA9"	=> "\x70\x61",
			"\xE3\x8E\xAA"	=> "\x6B\x70\x61",
			"\xE3\x8E\xAB"	=> "\x6D\x70\x61",
			"\xE3\x8E\xAC"	=> "\x67\x70\x61",
			"\xE3\x8E\xB4"	=> "\x70\x76",
			"\xE3\x8E\xB5"	=> "\x6E\x76",
			"\xE3\x8E\xB6"	=> "\xCE\xBC\x76",
			"\xE3\x8E\xB7"	=> "\x6D\x76",
			"\xE3\x8E\xB8"	=> "\x6B\x76",
			"\xE3\x8E\xB9"	=> "\x6D\x76",
			"\xE3\x8E\xBA"	=> "\x70\x77",
			"\xE3\x8E\xBB"	=> "\x6E\x77",
			"\xE3\x8E\xBC"	=> "\xCE\xBC\x77",
			"\xE3\x8E\xBD"	=> "\x6D\x77",
			"\xE3\x8E\xBE"	=> "\x6B\x77",
			"\xE3\x8E\xBF"	=> "\x6D\x77",
			"\xE3\x8F\x80"	=> "\x6B\xCF\x89",
			"\xE3\x8F\x81"	=> "\x6D\xCF\x89",
			"\xE3\x8F\x83"	=> "\x62\x71",
			"\xE3\x8F\x86"	=> "\x63\xE2\x88\x95\x6B\x67",
			"\xE3\x8F\x87"	=> "\x63\x6F\x2E",
			"\xE3\x8F\x88"	=> "\x64\x62",
			"\xE3\x8F\x89"	=> "\x67\x79",
			"\xE3\x8F\x8B"	=> "\x68\x70",
			"\xE3\x8F\x8D"	=> "\x6B\x6B",
			"\xE3\x8F\x8E"	=> "\x6B\x6D",
			"\xE3\x8F\x97"	=> "\x70\x68",
			"\xE3\x8F\x99"	=> "\x70\x70\x6D",
			"\xE3\x8F\x9A"	=> "\x70\x72",
			"\xE3\x8F\x9C"	=> "\x73\x76",
			"\xE3\x8F\x9D"	=> "\x77\x62",
			"\xE3\x8F\x9E"	=> "\x76\xE2\x88\x95\x6D",
			"\xE3\x8F\x9F"	=> "\x61\xE2\x88\x95\x6D",
			"\xF0\x9D\x90\x80"	=> "\x61",
			"\xF0\x9D\x90\x81"	=> "\x62",
			"\xF0\x9D\x90\x82"	=> "\x63",
			"\xF0\x9D\x90\x83"	=> "\x64",
			"\xF0\x9D\x90\x84"	=> "\x65",
			"\xF0\x9D\x90\x85"	=> "\x66",
			"\xF0\x9D\x90\x86"	=> "\x67",
			"\xF0\x9D\x90\x87"	=> "\x68",
			"\xF0\x9D\x90\x88"	=> "\x69",
			"\xF0\x9D\x90\x89"	=> "\x6A",
			"\xF0\x9D\x90\x8A"	=> "\x6B",
			"\xF0\x9D\x90\x8B"	=> "\x6C",
			"\xF0\x9D\x90\x8C"	=> "\x6D",
			"\xF0\x9D\x90\x8D"	=> "\x6E",
			"\xF0\x9D\x90\x8E"	=> "\x6F",
			"\xF0\x9D\x90\x8F"	=> "\x70",
			"\xF0\x9D\x90\x90"	=> "\x71",
			"\xF0\x9D\x90\x91"	=> "\x72",
			"\xF0\x9D\x90\x92"	=> "\x73",
			"\xF0\x9D\x90\x93"	=> "\x74",
			"\xF0\x9D\x90\x94"	=> "\x75",
			"\xF0\x9D\x90\x95"	=> "\x76",
			"\xF0\x9D\x90\x96"	=> "\x77",
			"\xF0\x9D\x90\x97"	=> "\x78",
			"\xF0\x9D\x90\x98"	=> "\x79",
			"\xF0\x9D\x90\x99"	=> "\x7A",
			"\xF0\x9D\x90\xB4"	=> "\x61",
			"\xF0\x9D\x90\xB5"	=> "\x62",
			"\xF0\x9D\x90\xB6"	=> "\x63",
			"\xF0\x9D\x90\xB7"	=> "\x64",
			"\xF0\x9D\x90\xB8"	=> "\x65",
			"\xF0\x9D\x90\xB9"	=> "\x66",
			"\xF0\x9D\x90\xBA"	=> "\x67",
			"\xF0\x9D\x90\xBB"	=> "\x68",
			"\xF0\x9D\x90\xBC"	=> "\x69",
			"\xF0\x9D\x90\xBD"	=> "\x6A",
			"\xF0\x9D\x90\xBE"	=> "\x6B",
			"\xF0\x9D\x90\xBF"	=> "\x6C",
			"\xF0\x9D\x91\x80"	=> "\x6D",
			"\xF0\x9D\x91\x81"	=> "\x6E",
			"\xF0\x9D\x91\x82"	=> "\x6F",
			"\xF0\x9D\x91\x83"	=> "\x70",
			"\xF0\x9D\x91\x84"	=> "\x71",
			"\xF0\x9D\x91\x85"	=> "\x72",
			"\xF0\x9D\x91\x86"	=> "\x73",
			"\xF0\x9D\x91\x87"	=> "\x74",
			"\xF0\x9D\x91\x88"	=> "\x75",
			"\xF0\x9D\x91\x89"	=> "\x76",
			"\xF0\x9D\x91\x8A"	=> "\x77",
			"\xF0\x9D\x91\x8B"	=> "\x78",
			"\xF0\x9D\x91\x8C"	=> "\x79",
			"\xF0\x9D\x91\x8D"	=> "\x7A",
			"\xF0\x9D\x91\xA8"	=> "\x61",
			"\xF0\x9D\x91\xA9"	=> "\x62",
			"\xF0\x9D\x91\xAA"	=> "\x63",
			"\xF0\x9D\x91\xAB"	=> "\x64",
			"\xF0\x9D\x91\xAC"	=> "\x65",
			"\xF0\x9D\x91\xAD"	=> "\x66",
			"\xF0\x9D\x91\xAE"	=> "\x67",
			"\xF0\x9D\x91\xAF"	=> "\x68",
			"\xF0\x9D\x91\xB0"	=> "\x69",
			"\xF0\x9D\x91\xB1"	=> "\x6A",
			"\xF0\x9D\x91\xB2"	=> "\x6B",
			"\xF0\x9D\x91\xB3"	=> "\x6C",
			"\xF0\x9D\x91\xB4"	=> "\x6D",
			"\xF0\x9D\x91\xB5"	=> "\x6E",
			"\xF0\x9D\x91\xB6"	=> "\x6F",
			"\xF0\x9D\x91\xB7"	=> "\x70",
			"\xF0\x9D\x91\xB8"	=> "\x71",
			"\xF0\x9D\x91\xB9"	=> "\x72",
			"\xF0\x9D\x91\xBA"	=> "\x73",
			"\xF0\x9D\x91\xBB"	=> "\x74",
			"\xF0\x9D\x91\xBC"	=> "\x75",
			"\xF0\x9D\x91\xBD"	=> "\x76",
			"\xF0\x9D\x91\xBE"	=> "\x77",
			"\xF0\x9D\x91\xBF"	=> "\x78",
			"\xF0\x9D\x92\x80"	=> "\x79",
			"\xF0\x9D\x92\x81"	=> "\x7A",
			"\xF0\x9D\x92\x9C"	=> "\x61",
			"\xF0\x9D\x92\x9E"	=> "\x63",
			"\xF0\x9D\x92\x9F"	=> "\x64",
			"\xF0\x9D\x92\xA2"	=> "\x67",
			"\xF0\x9D\x92\xA5"	=> "\x6A",
			"\xF0\x9D\x92\xA6"	=> "\x6B",
			"\xF0\x9D\x92\xA9"	=> "\x6E",
			"\xF0\x9D\x92\xAA"	=> "\x6F",
			"\xF0\x9D\x92\xAB"	=> "\x70",
			"\xF0\x9D\x92\xAC"	=> "\x71",
			"\xF0\x9D\x92\xAE"	=> "\x73",
			"\xF0\x9D\x92\xAF"	=> "\x74",
			"\xF0\x9D\x92\xB0"	=> "\x75",
			"\xF0\x9D\x92\xB1"	=> "\x76",
			"\xF0\x9D\x92\xB2"	=> "\x77",
			"\xF0\x9D\x92\xB3"	=> "\x78",
			"\xF0\x9D\x92\xB4"	=> "\x79",
			"\xF0\x9D\x92\xB5"	=> "\x7A",
			"\xF0\x9D\x93\x90"	=> "\x61",
			"\xF0\x9D\x93\x91"	=> "\x62",
			"\xF0\x9D\x93\x92"	=> "\x63",
			"\xF0\x9D\x93\x93"	=> "\x64",
			"\xF0\x9D\x93\x94"	=> "\x65",
			"\xF0\x9D\x93\x95"	=> "\x66",
			"\xF0\x9D\x93\x96"	=> "\x67",
			"\xF0\x9D\x93\x97"	=> "\x68",
			"\xF0\x9D\x93\x98"	=> "\x69",
			"\xF0\x9D\x93\x99"	=> "\x6A",
			"\xF0\x9D\x93\x9A"	=> "\x6B",
			"\xF0\x9D\x93\x9B"	=> "\x6C",
			"\xF0\x9D\x93\x9C"	=> "\x6D",
			"\xF0\x9D\x93\x9D"	=> "\x6E",
			"\xF0\x9D\x93\x9E"	=> "\x6F",
			"\xF0\x9D\x93\x9F"	=> "\x70",
			"\xF0\x9D\x93\xA0"	=> "\x71",
			"\xF0\x9D\x93\xA1"	=> "\x72",
			"\xF0\x9D\x93\xA2"	=> "\x73",
			"\xF0\x9D\x93\xA3"	=> "\x74",
			"\xF0\x9D\x93\xA4"	=> "\x75",
			"\xF0\x9D\x93\xA5"	=> "\x76",
			"\xF0\x9D\x93\xA6"	=> "\x77",
			"\xF0\x9D\x93\xA7"	=> "\x78",
			"\xF0\x9D\x93\xA8"	=> "\x79",
			"\xF0\x9D\x93\xA9"	=> "\x7A",
			"\xF0\x9D\x94\x84"	=> "\x61",
			"\xF0\x9D\x94\x85"	=> "\x62",
			"\xF0\x9D\x94\x87"	=> "\x64",
			"\xF0\x9D\x94\x88"	=> "\x65",
			"\xF0\x9D\x94\x89"	=> "\x66",
			"\xF0\x9D\x94\x8A"	=> "\x67",
			"\xF0\x9D\x94\x8D"	=> "\x6A",
			"\xF0\x9D\x94\x8E"	=> "\x6B",
			"\xF0\x9D\x94\x8F"	=> "\x6C",
			"\xF0\x9D\x94\x90"	=> "\x6D",
			"\xF0\x9D\x94\x91"	=> "\x6E",
			"\xF0\x9D\x94\x92"	=> "\x6F",
			"\xF0\x9D\x94\x93"	=> "\x70",
			"\xF0\x9D\x94\x94"	=> "\x71",
			"\xF0\x9D\x94\x96"	=> "\x73",
			"\xF0\x9D\x94\x97"	=> "\x74",
			"\xF0\x9D\x94\x98"	=> "\x75",
			"\xF0\x9D\x94\x99"	=> "\x76",
			"\xF0\x9D\x94\x9A"	=> "\x77",
			"\xF0\x9D\x94\x9B"	=> "\x78",
			"\xF0\x9D\x94\x9C"	=> "\x79",
			"\xF0\x9D\x94\xB8"	=> "\x61",
			"\xF0\x9D\x94\xB9"	=> "\x62",
			"\xF0\x9D\x94\xBB"	=> "\x64",
			"\xF0\x9D\x94\xBC"	=> "\x65",
			"\xF0\x9D\x94\xBD"	=> "\x66",
			"\xF0\x9D\x94\xBE"	=> "\x67",
			"\xF0\x9D\x95\x80"	=> "\x69",
			"\xF0\x9D\x95\x81"	=> "\x6A",
			"\xF0\x9D\x95\x82"	=> "\x6B",
			"\xF0\x9D\x95\x83"	=> "\x6C",
			"\xF0\x9D\x95\x84"	=> "\x6D",
			"\xF0\x9D\x95\x86"	=> "\x6F",
			"\xF0\x9D\x95\x8A"	=> "\x73",
			"\xF0\x9D\x95\x8B"	=> "\x74",
			"\xF0\x9D\x95\x8C"	=> "\x75",
			"\xF0\x9D\x95\x8D"	=> "\x76",
			"\xF0\x9D\x95\x8E"	=> "\x77",
			"\xF0\x9D\x95\x8F"	=> "\x78",
			"\xF0\x9D\x95\x90"	=> "\x79",
			"\xF0\x9D\x95\xAC"	=> "\x61",
			"\xF0\x9D\x95\xAD"	=> "\x62",
			"\xF0\x9D\x95\xAE"	=> "\x63",
			"\xF0\x9D\x95\xAF"	=> "\x64",
			"\xF0\x9D\x95\xB0"	=> "\x65",
			"\xF0\x9D\x95\xB1"	=> "\x66",
			"\xF0\x9D\x95\xB2"	=> "\x67",
			"\xF0\x9D\x95\xB3"	=> "\x68",
			"\xF0\x9D\x95\xB4"	=> "\x69",
			"\xF0\x9D\x95\xB5"	=> "\x6A",
			"\xF0\x9D\x95\xB6"	=> "\x6B",
			"\xF0\x9D\x95\xB7"	=> "\x6C",
			"\xF0\x9D\x95\xB8"	=> "\x6D",
			"\xF0\x9D\x95\xB9"	=> "\x6E",
			"\xF0\x9D\x95\xBA"	=> "\x6F",
			"\xF0\x9D\x95\xBB"	=> "\x70",
			"\xF0\x9D\x95\xBC"	=> "\x71",
			"\xF0\x9D\x95\xBD"	=> "\x72",
			"\xF0\x9D\x95\xBE"	=> "\x73",
			"\xF0\x9D\x95\xBF"	=> "\x74",
			"\xF0\x9D\x96\x80"	=> "\x75",
			"\xF0\x9D\x96\x81"	=> "\x76",
			"\xF0\x9D\x96\x82"	=> "\x77",
			"\xF0\x9D\x96\x83"	=> "\x78",
			"\xF0\x9D\x96\x84"	=> "\x79",
			"\xF0\x9D\x96\x85"	=> "\x7A",
			"\xF0\x9D\x96\xA0"	=> "\x61",
			"\xF0\x9D\x96\xA1"	=> "\x62",
			"\xF0\x9D\x96\xA2"	=> "\x63",
			"\xF0\x9D\x96\xA3"	=> "\x64",
			"\xF0\x9D\x96\xA4"	=> "\x65",
			"\xF0\x9D\x96\xA5"	=> "\x66",
			"\xF0\x9D\x96\xA6"	=> "\x67",
			"\xF0\x9D\x96\xA7"	=> "\x68",
			"\xF0\x9D\x96\xA8"	=> "\x69",
			"\xF0\x9D\x96\xA9"	=> "\x6A",
			"\xF0\x9D\x96\xAA"	=> "\x6B",
			"\xF0\x9D\x96\xAB"	=> "\x6C",
			"\xF0\x9D\x96\xAC"	=> "\x6D",
			"\xF0\x9D\x96\xAD"	=> "\x6E",
			"\xF0\x9D\x96\xAE"	=> "\x6F",
			"\xF0\x9D\x96\xAF"	=> "\x70",
			"\xF0\x9D\x96\xB0"	=> "\x71",
			"\xF0\x9D\x96\xB1"	=> "\x72",
			"\xF0\x9D\x96\xB2"	=> "\x73",
			"\xF0\x9D\x96\xB3"	=> "\x74",
			"\xF0\x9D\x96\xB4"	=> "\x75",
			"\xF0\x9D\x96\xB5"	=> "\x76",
			"\xF0\x9D\x96\xB6"	=> "\x77",
			"\xF0\x9D\x96\xB7"	=> "\x78",
			"\xF0\x9D\x96\xB8"	=> "\x79",
			"\xF0\x9D\x96\xB9"	=> "\x7A",
			"\xF0\x9D\x97\x94"	=> "\x61",
			"\xF0\x9D\x97\x95"	=> "\x62",
			"\xF0\x9D\x97\x96"	=> "\x63",
			"\xF0\x9D\x97\x97"	=> "\x64",
			"\xF0\x9D\x97\x98"	=> "\x65",
			"\xF0\x9D\x97\x99"	=> "\x66",
			"\xF0\x9D\x97\x9A"	=> "\x67",
			"\xF0\x9D\x97\x9B"	=> "\x68",
			"\xF0\x9D\x97\x9C"	=> "\x69",
			"\xF0\x9D\x97\x9D"	=> "\x6A",
			"\xF0\x9D\x97\x9E"	=> "\x6B",
			"\xF0\x9D\x97\x9F"	=> "\x6C",
			"\xF0\x9D\x97\xA0"	=> "\x6D",
			"\xF0\x9D\x97\xA1"	=> "\x6E",
			"\xF0\x9D\x97\xA2"	=> "\x6F",
			"\xF0\x9D\x97\xA3"	=> "\x70",
			"\xF0\x9D\x97\xA4"	=> "\x71",
			"\xF0\x9D\x97\xA5"	=> "\x72",
			"\xF0\x9D\x97\xA6"	=> "\x73",
			"\xF0\x9D\x97\xA7"	=> "\x74",
			"\xF0\x9D\x97\xA8"	=> "\x75",
			"\xF0\x9D\x97\xA9"	=> "\x76",
			"\xF0\x9D\x97\xAA"	=> "\x77",
			"\xF0\x9D\x97\xAB"	=> "\x78",
			"\xF0\x9D\x97\xAC"	=> "\x79",
			"\xF0\x9D\x97\xAD"	=> "\x7A",
			"\xF0\x9D\x98\x88"	=> "\x61",
			"\xF0\x9D\x98\x89"	=> "\x62",
			"\xF0\x9D\x98\x8A"	=> "\x63",
			"\xF0\x9D\x98\x8B"	=> "\x64",
			"\xF0\x9D\x98\x8C"	=> "\x65",
			"\xF0\x9D\x98\x8D"	=> "\x66",
			"\xF0\x9D\x98\x8E"	=> "\x67",
			"\xF0\x9D\x98\x8F"	=> "\x68",
			"\xF0\x9D\x98\x90"	=> "\x69",
			"\xF0\x9D\x98\x91"	=> "\x6A",
			"\xF0\x9D\x98\x92"	=> "\x6B",
			"\xF0\x9D\x98\x93"	=> "\x6C",
			"\xF0\x9D\x98\x94"	=> "\x6D",
			"\xF0\x9D\x98\x95"	=> "\x6E",
			"\xF0\x9D\x98\x96"	=> "\x6F",
			"\xF0\x9D\x98\x97"	=> "\x70",
			"\xF0\x9D\x98\x98"	=> "\x71",
			"\xF0\x9D\x98\x99"	=> "\x72",
			"\xF0\x9D\x98\x9A"	=> "\x73",
			"\xF0\x9D\x98\x9B"	=> "\x74",
			"\xF0\x9D\x98\x9C"	=> "\x75",
			"\xF0\x9D\x98\x9D"	=> "\x76",
			"\xF0\x9D\x98\x9E"	=> "\x77",
			"\xF0\x9D\x98\x9F"	=> "\x78",
			"\xF0\x9D\x98\xA0"	=> "\x79",
			"\xF0\x9D\x98\xA1"	=> "\x7A",
			"\xF0\x9D\x98\xBC"	=> "\x61",
			"\xF0\x9D\x98\xBD"	=> "\x62",
			"\xF0\x9D\x98\xBE"	=> "\x63",
			"\xF0\x9D\x98\xBF"	=> "\x64",
			"\xF0\x9D\x99\x80"	=> "\x65",
			"\xF0\x9D\x99\x81"	=> "\x66",
			"\xF0\x9D\x99\x82"	=> "\x67",
			"\xF0\x9D\x99\x83"	=> "\x68",
			"\xF0\x9D\x99\x84"	=> "\x69",
			"\xF0\x9D\x99\x85"	=> "\x6A",
			"\xF0\x9D\x99\x86"	=> "\x6B",
			"\xF0\x9D\x99\x87"	=> "\x6C",
			"\xF0\x9D\x99\x88"	=> "\x6D",
			"\xF0\x9D\x99\x89"	=> "\x6E",
			"\xF0\x9D\x99\x8A"	=> "\x6F",
			"\xF0\x9D\x99\x8B"	=> "\x70",
			"\xF0\x9D\x99\x8C"	=> "\x71",
			"\xF0\x9D\x99\x8D"	=> "\x72",
			"\xF0\x9D\x99\x8E"	=> "\x73",
			"\xF0\x9D\x99\x8F"	=> "\x74",
			"\xF0\x9D\x99\x90"	=> "\x75",
			"\xF0\x9D\x99\x91"	=> "\x76",
			"\xF0\x9D\x99\x92"	=> "\x77",
			"\xF0\x9D\x99\x93"	=> "\x78",
			"\xF0\x9D\x99\x94"	=> "\x79",
			"\xF0\x9D\x99\x95"	=> "\x7A",
			"\xF0\x9D\x99\xB0"	=> "\x61",
			"\xF0\x9D\x99\xB1"	=> "\x62",
			"\xF0\x9D\x99\xB2"	=> "\x63",
			"\xF0\x9D\x99\xB3"	=> "\x64",
			"\xF0\x9D\x99\xB4"	=> "\x65",
			"\xF0\x9D\x99\xB5"	=> "\x66",
			"\xF0\x9D\x99\xB6"	=> "\x67",
			"\xF0\x9D\x99\xB7"	=> "\x68",
			"\xF0\x9D\x99\xB8"	=> "\x69",
			"\xF0\x9D\x99\xB9"	=> "\x6A",
			"\xF0\x9D\x99\xBA"	=> "\x6B",
			"\xF0\x9D\x99\xBB"	=> "\x6C",
			"\xF0\x9D\x99\xBC"	=> "\x6D",
			"\xF0\x9D\x99\xBD"	=> "\x6E",
			"\xF0\x9D\x99\xBE"	=> "\x6F",
			"\xF0\x9D\x99\xBF"	=> "\x70",
			"\xF0\x9D\x9A\x80"	=> "\x71",
			"\xF0\x9D\x9A\x81"	=> "\x72",
			"\xF0\x9D\x9A\x82"	=> "\x73",
			"\xF0\x9D\x9A\x83"	=> "\x74",
			"\xF0\x9D\x9A\x84"	=> "\x75",
			"\xF0\x9D\x9A\x85"	=> "\x76",
			"\xF0\x9D\x9A\x86"	=> "\x77",
			"\xF0\x9D\x9A\x87"	=> "\x78",
			"\xF0\x9D\x9A\x88"	=> "\x79",
			"\xF0\x9D\x9A\x89"	=> "\x7A",
			"\xF0\x9D\x9A\xA8"	=> "\xCE\xB1",
			"\xF0\x9D\x9A\xA9"	=> "\xCE\xB2",
			"\xF0\x9D\x9A\xAA"	=> "\xCE\xB3",
			"\xF0\x9D\x9A\xAB"	=> "\xCE\xB4",
			"\xF0\x9D\x9A\xAC"	=> "\xCE\xB5",
			"\xF0\x9D\x9A\xAD"	=> "\xCE\xB6",
			"\xF0\x9D\x9A\xAE"	=> "\xCE\xB7",
			"\xF0\x9D\x9A\xAF"	=> "\xCE\xB8",
			"\xF0\x9D\x9A\xB0"	=> "\xCE\xB9",
			"\xF0\x9D\x9A\xB1"	=> "\xCE\xBA",
			"\xF0\x9D\x9A\xB2"	=> "\xCE\xBB",
			"\xF0\x9D\x9A\xB3"	=> "\xCE\xBC",
			"\xF0\x9D\x9A\xB4"	=> "\xCE\xBD",
			"\xF0\x9D\x9A\xB5"	=> "\xCE\xBE",
			"\xF0\x9D\x9A\xB6"	=> "\xCE\xBF",
			"\xF0\x9D\x9A\xB7"	=> "\xCF\x80",
			"\xF0\x9D\x9A\xB8"	=> "\xCF\x81",
			"\xF0\x9D\x9A\xB9"	=> "\xCE\xB8",
			"\xF0\x9D\x9A\xBA"	=> "\xCF\x83",
			"\xF0\x9D\x9A\xBB"	=> "\xCF\x84",
			"\xF0\x9D\x9A\xBC"	=> "\xCF\x85",
			"\xF0\x9D\x9A\xBD"	=> "\xCF\x86",
			"\xF0\x9D\x9A\xBE"	=> "\xCF\x87",
			"\xF0\x9D\x9A\xBF"	=> "\xCF\x88",
			"\xF0\x9D\x9B\x80"	=> "\xCF\x89",
			"\xF0\x9D\x9B\x93"	=> "\xCF\x83",
			"\xF0\x9D\x9B\xA2"	=> "\xCE\xB1",
			"\xF0\x9D\x9B\xA3"	=> "\xCE\xB2",
			"\xF0\x9D\x9B\xA4"	=> "\xCE\xB3",
			"\xF0\x9D\x9B\xA5"	=> "\xCE\xB4",
			"\xF0\x9D\x9B\xA6"	=> "\xCE\xB5",
			"\xF0\x9D\x9B\xA7"	=> "\xCE\xB6",
			"\xF0\x9D\x9B\xA8"	=> "\xCE\xB7",
			"\xF0\x9D\x9B\xA9"	=> "\xCE\xB8",
			"\xF0\x9D\x9B\xAA"	=> "\xCE\xB9",
			"\xF0\x9D\x9B\xAB"	=> "\xCE\xBA",
			"\xF0\x9D\x9B\xAC"	=> "\xCE\xBB",
			"\xF0\x9D\x9B\xAD"	=> "\xCE\xBC",
			"\xF0\x9D\x9B\xAE"	=> "\xCE\xBD",
			"\xF0\x9D\x9B\xAF"	=> "\xCE\xBE",
			"\xF0\x9D\x9B\xB0"	=> "\xCE\xBF",
			"\xF0\x9D\x9B\xB1"	=> "\xCF\x80",
			"\xF0\x9D\x9B\xB2"	=> "\xCF\x81",
			"\xF0\x9D\x9B\xB3"	=> "\xCE\xB8",
			"\xF0\x9D\x9B\xB4"	=> "\xCF\x83",
			"\xF0\x9D\x9B\xB5"	=> "\xCF\x84",
			"\xF0\x9D\x9B\xB6"	=> "\xCF\x85",
			"\xF0\x9D\x9B\xB7"	=> "\xCF\x86",
			"\xF0\x9D\x9B\xB8"	=> "\xCF\x87",
			"\xF0\x9D\x9B\xB9"	=> "\xCF\x88",
			"\xF0\x9D\x9B\xBA"	=> "\xCF\x89",
			"\xF0\x9D\x9C\x8D"	=> "\xCF\x83",
			"\xF0\x9D\x9C\x9C"	=> "\xCE\xB1",
			"\xF0\x9D\x9C\x9D"	=> "\xCE\xB2",
			"\xF0\x9D\x9C\x9E"	=> "\xCE\xB3",
			"\xF0\x9D\x9C\x9F"	=> "\xCE\xB4",
			"\xF0\x9D\x9C\xA0"	=> "\xCE\xB5",
			"\xF0\x9D\x9C\xA1"	=> "\xCE\xB6",
			"\xF0\x9D\x9C\xA2"	=> "\xCE\xB7",
			"\xF0\x9D\x9C\xA3"	=> "\xCE\xB8",
			"\xF0\x9D\x9C\xA4"	=> "\xCE\xB9",
			"\xF0\x9D\x9C\xA5"	=> "\xCE\xBA",
			"\xF0\x9D\x9C\xA6"	=> "\xCE\xBB",
			"\xF0\x9D\x9C\xA7"	=> "\xCE\xBC",
			"\xF0\x9D\x9C\xA8"	=> "\xCE\xBD",
			"\xF0\x9D\x9C\xA9"	=> "\xCE\xBE",
			"\xF0\x9D\x9C\xAA"	=> "\xCE\xBF",
			"\xF0\x9D\x9C\xAB"	=> "\xCF\x80",
			"\xF0\x9D\x9C\xAC"	=> "\xCF\x81",
			"\xF0\x9D\x9C\xAD"	=> "\xCE\xB8",
			"\xF0\x9D\x9C\xAE"	=> "\xCF\x83",
			"\xF0\x9D\x9C\xAF"	=> "\xCF\x84",
			"\xF0\x9D\x9C\xB0"	=> "\xCF\x85",
			"\xF0\x9D\x9C\xB1"	=> "\xCF\x86",
			"\xF0\x9D\x9C\xB2"	=> "\xCF\x87",
			"\xF0\x9D\x9C\xB3"	=> "\xCF\x88",
			"\xF0\x9D\x9C\xB4"	=> "\xCF\x89",
			"\xF0\x9D\x9D\x87"	=> "\xCF\x83",
			"\xF0\x9D\x9D\x96"	=> "\xCE\xB1",
			"\xF0\x9D\x9D\x97"	=> "\xCE\xB2",
			"\xF0\x9D\x9D\x98"	=> "\xCE\xB3",
			"\xF0\x9D\x9D\x99"	=> "\xCE\xB4",
			"\xF0\x9D\x9D\x9A"	=> "\xCE\xB5",
			"\xF0\x9D\x9D\x9B"	=> "\xCE\xB6",
			"\xF0\x9D\x9D\x9C"	=> "\xCE\xB7",
			"\xF0\x9D\x9D\x9D"	=> "\xCE\xB8",
			"\xF0\x9D\x9D\x9E"	=> "\xCE\xB9",
			"\xF0\x9D\x9D\x9F"	=> "\xCE\xBA",
			"\xF0\x9D\x9D\xA0"	=> "\xCE\xBB",
			"\xF0\x9D\x9D\xA1"	=> "\xCE\xBC",
			"\xF0\x9D\x9D\xA2"	=> "\xCE\xBD",
			"\xF0\x9D\x9D\xA3"	=> "\xCE\xBE",
			"\xF0\x9D\x9D\xA4"	=> "\xCE\xBF",
			"\xF0\x9D\x9D\xA5"	=> "\xCF\x80",
			"\xF0\x9D\x9D\xA6"	=> "\xCF\x81",
			"\xF0\x9D\x9D\xA7"	=> "\xCE\xB8",
			"\xF0\x9D\x9D\xA8"	=> "\xCF\x83",
			"\xF0\x9D\x9D\xA9"	=> "\xCF\x84",
			"\xF0\x9D\x9D\xAA"	=> "\xCF\x85",
			"\xF0\x9D\x9D\xAB"	=> "\xCF\x86",
			"\xF0\x9D\x9D\xAC"	=> "\xCF\x87",
			"\xF0\x9D\x9D\xAD"	=> "\xCF\x88",
			"\xF0\x9D\x9D\xAE"	=> "\xCF\x89",
			"\xF0\x9D\x9E\x81"	=> "\xCF\x83",
			"\xF0\x9D\x9E\x90"	=> "\xCE\xB1",
			"\xF0\x9D\x9E\x91"	=> "\xCE\xB2",
			"\xF0\x9D\x9E\x92"	=> "\xCE\xB3",
			"\xF0\x9D\x9E\x93"	=> "\xCE\xB4",
			"\xF0\x9D\x9E\x94"	=> "\xCE\xB5",
			"\xF0\x9D\x9E\x95"	=> "\xCE\xB6",
			"\xF0\x9D\x9E\x96"	=> "\xCE\xB7",
			"\xF0\x9D\x9E\x97"	=> "\xCE\xB8",
			"\xF0\x9D\x9E\x98"	=> "\xCE\xB9",
			"\xF0\x9D\x9E\x99"	=> "\xCE\xBA",
			"\xF0\x9D\x9E\x9A"	=> "\xCE\xBB",
			"\xF0\x9D\x9E\x9B"	=> "\xCE\xBC",
			"\xF0\x9D\x9E\x9C"	=> "\xCE\xBD",
			"\xF0\x9D\x9E\x9D"	=> "\xCE\xBE",
			"\xF0\x9D\x9E\x9E"	=> "\xCE\xBF",
			"\xF0\x9D\x9E\x9F"	=> "\xCF\x80",
			"\xF0\x9D\x9E\xA0"	=> "\xCF\x81",
			"\xF0\x9D\x9E\xA1"	=> "\xCE\xB8",
			"\xF0\x9D\x9E\xA2"	=> "\xCF\x83",
			"\xF0\x9D\x9E\xA3"	=> "\xCF\x84",
			"\xF0\x9D\x9E\xA4"	=> "\xCF\x85",
			"\xF0\x9D\x9E\xA5"	=> "\xCF\x86",
			"\xF0\x9D\x9E\xA6"	=> "\xCF\x87",
			"\xF0\x9D\x9E\xA7"	=> "\xCF\x88",
			"\xF0\x9D\x9E\xA8"	=> "\xCF\x89",
			"\xF0\x9D\x9E\xBB"	=> "\xCF\x83",
			"\xF0\x9D\x9F\x8A"	=> "\xCF\x9D",
		);

		// do the case fold
		$text = self::utf8_case_fold($text, $option);

		// convert to NFKC
		\Normalizer::normalize($text, \Normalizer::NFKC);

		// FC_NFKC_Closure, http://www.unicode.org/Public/5.0.0/ucd/DerivedNormalizationProps.txt
		$text = strtr($text, $fc_nfkc_closure);

		return $text;
	}
	
	/**
	* This function is used to generate a "clean" version of a string.
	* Clean means that it is a case insensitive form (case folding) and that it is normalized (NFC).
	* Additionally a homographs of one character are transformed into one specific character (preferably ASCII
	* if it is an ASCII character).
	*
	* Please be aware that if you change something within this function or within
	* functions used here you need to rebuild/update the username_clean column in the users table. And all other
	* columns that store a clean string otherwise you will break this functionality.
	*
	* @param	string	$text	An unclean string, mabye user input (has to be valid UTF-8!)
	* @return	string			Cleaned up version of the input string
	*/
	public function utf8_clean_string($text) {
		static $homographs = array();
		
		if (empty($homographs)) {
			$homographs = include(dirname(__FILE__).'/utf/confusables.php');
		}

		$text = $this->utf8_case_fold_nfkc($text);
		$text = strtr($text, $homographs);
		// Other control characters
		$text = preg_replace('#(?:[\x00-\x1F\x7F]+|(?:\xC2[\x80-\x9F])+)#', '', $text);

		// we need to reduce multiple spaces to a single one
		$text = preg_replace('# {2,}#', ' ', $text);

		// we can use trim here as all the other space characters should have been turned
		// into normal ASCII spaces by now
		return trim($text);
	}

	/**
	* A wrapper for htmlspecialchars($value, ENT_COMPAT, 'UTF-8')
	*/
	public function utf8_htmlspecialchars($value)
	{
		return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
	}
}