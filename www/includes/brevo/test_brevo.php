<?php
/**
*
* Brevo self-check (CLI only, no test infra in 3.0.x).
* Usage (in container):
*   php www/includes/brevo/test_brevo.php
*   BREVO_TEST_EMAIL=you@example.com php www/includes/brevo/test_brevo.php (real send)
*
*/

/**
* @ignore
*/
if (php_sapi_name() !== 'cli')
{
	exit;
}
if (!defined('IN_PHPBB'))
{
	define('IN_PHPBB', true);
}

$brevo_fail = 0;

/**
* Report a self-check result.
*
* @param string $label Check description
* @param bool $cond Check outcome
* @return void
*/
function brevo_check($label, $cond)
{
	global $brevo_fail;
	echo ($cond ? 'ok' : 'FAIL') . ' - ' . $label . "\n";
	if (!$cond)
	{
		$brevo_fail++;
	}
}

$phpbb_root_path = dirname(dirname(dirname(__FILE__))) . '/';
require_once($phpbb_root_path . 'includes/brevo/brevo_mail.php');

brevo_check('brevo_parse_addresses loaded', function_exists('brevo_parse_addresses'));
brevo_check('brevo_mail loaded', function_exists('brevo_mail'));

$parsed = brevo_parse_addresses('John <a@b.fr>');
brevo_check('name <mail> parsed', sizeof($parsed) === 1 && $parsed[0]['email'] === 'a@b.fr');

$parsed = brevo_parse_addresses('a@b.fr, c@d.fr');
brevo_check('two recipients parsed', sizeof($parsed) === 2);

brevo_check('undisclosed filtered', brevo_parse_addresses('undisclosed-recipients:;') === array());
brevo_check('invalid filtered', brevo_parse_addresses('pas-un-mail') === array());

$parsed = brevo_parse_addresses('X <x@y.fr>');
brevo_check('cc-style entry parsed', sizeof($parsed) === 1 && $parsed[0]['email'] === 'x@y.fr');

$test_email = getenv('BREVO_TEST_EMAIL');
$api_key = getenv('BREVO_API_KEY');
if ($test_email === false || $test_email === '' || $api_key === false || $api_key === '')
{
	echo 'SKIP - real send (set BREVO_TEST_EMAIL + BREVO_API_KEY)' . "\n";
}
else
{
	global $config;
	$config = array(
		'board_email'	=> 'hello@arador-dayn.fr',
		'board_contact'	=> 'hello@arador-dayn.fr',
		'sitename'		=> 'test',
	);
	$err = '';
	$ok = brevo_mail($test_email, '[Arador] Brevo test', "Test body\nline2", array(), "\n", $err);
	brevo_check('real send ok', $ok === true);
}

exit($brevo_fail ? 1 : 0);
