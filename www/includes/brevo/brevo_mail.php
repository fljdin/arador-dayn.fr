<?php
if (!defined('IN_PHPBB'))
{
	exit;
}

if (!function_exists('brevo_parse_addresses'))
{
	function brevo_parse_addresses($str)
	{
		$out = array();
		foreach (explode(',', (string) $str) as $part)
		{
			$part = trim($part);
			if ($part === '' || stripos($part, 'undisclosed-recipients') !== false)
			{
				continue;
			}
			if (preg_match('/<(.*?)>/', $part, $m))
			{
				$email = trim($m[1]);
				$name = trim(str_replace($m[0], '', $part), " \t\"'");
			}
			else
			{
				$email = $part;
				$name = '';
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				continue;
			}
			if ($name !== '' && function_exists('mb_decode_mimeheader'))
			{
				$name = trim(mb_decode_mimeheader($name), " \t\"'");
			}
			$entry = array('email' => $email);
			if ($name !== '' && $name !== $email)
			{
				$entry['name'] = $name;
			}
			$out[] = $entry;
		}
		return $out;
	}
}

if (!function_exists('brevo_mail'))
{
	function brevo_mail($to, $subject, $msg, $headers, $eol, &$err_msg)
	{
		global $config, $phpbb_root_path;

		$api_key = getenv('BREVO_API_KEY');
		if ($api_key === false || $api_key === '')
		{
			$err_msg = 'BREVO_API_KEY missing';
			return false;
		}
		if (!class_exists('GuzzleHttp\Client'))
		{
			$autoload = $phpbb_root_path . 'vendor/autoload.php';
			if (file_exists($autoload))
			{
				require_once($autoload);
			}
		}
		if (!class_exists('GuzzleHttp\Client'))
		{
			$err_msg = 'Guzzle missing (vendor/autoload.php)';
			return false;
		}

		$recipients = brevo_parse_addresses($to);
		if (!sizeof($recipients))
		{
			$err_msg = 'No valid recipient';
			return false;
		}
		if (trim($subject) === '' || trim($msg) === '')
		{
			$err_msg = 'No email subject/message specified';
			return false;
		}

		$cc = $bcc = array();
		$custom_headers = array();
		if (is_array($headers))
		{
			foreach ($headers as $h)
			{
				$h = trim($h);
				if (stripos($h, 'cc:') === 0)
				{
					$cc = array_merge($cc, brevo_parse_addresses(substr($h, 3)));
				}
				else if (stripos($h, 'bcc:') === 0)
				{
					$bcc = array_merge($bcc, brevo_parse_addresses(substr($h, 4)));
				}
				else if (stripos($h, 'X-') === 0 && strpos($h, ':') !== false)
				{
					list($k, $v) = explode(':', $h, 2);
					$custom_headers[trim($k)] = trim($v);
				}
			}
		}

		$data = array(
			'sender' => array(
				'name' => isset($config['sitename']) ? $config['sitename'] : '',
				'email' => $config['board_email'],
			),
			'to' => $recipients,
			'subject' => $subject,
			'textContent' => $msg,
			'htmlContent' => nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')),
		);
		if (sizeof($cc))
		{
			$data['cc'] = $cc;
		}
		if (sizeof($bcc))
		{
			$data['bcc'] = $bcc;
		}
		if (!empty($config['board_contact']) && filter_var($config['board_contact'], FILTER_VALIDATE_EMAIL))
		{
			$data['replyTo'] = array('email' => $config['board_contact']);
		}
		if (sizeof($custom_headers))
		{
			$data['headers'] = $custom_headers;
		}

		try {
			$client = new \GuzzleHttp\Client(array(
				'base_uri' => 'https://api.brevo.com',
				'timeout' => 15,
			));
			$client->post('/v3/smtp/email', array(
				'headers' => array(
					'api-key' => $api_key,
					'accept' => 'application/json',
					'content-type' => 'application/json',
				),
				'json' => $data,
			));
			return true;
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$err_msg = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
			return false;
		} catch (\Exception $e) {
			$err_msg = $e->getMessage();
			return false;
		}
	}
}
