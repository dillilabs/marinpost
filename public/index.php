<?php

// Setup environment-friendly configs...
// because Craft's multi-env db config doesn't actually work as advertised.
// From: https://github.com/BarrelStrength/Craft-Master/blob/master/public/index.php

switch ($_SERVER['SERVER_NAME']) {
	case 'marinpost.org' :
		define('CRAFT_ENVIRONMENT', 'live');
		break;

	case 'dev.marinpost.org' :
		define('CRAFT_ENVIRONMENT', 'dev');
		break;

	default :
		define('CRAFT_ENVIRONMENT', 'local');
		break;
}

// Path to your craft/ folder
$craftPath = '../craft';

// Do not edit below this line
$path = rtrim($craftPath, '/').'/app/index.php';

if (!is_file($path))
{
	if (function_exists('http_response_code'))
	{
		http_response_code(503);
	}

	exit('Could not find your craft/ folder. Please ensure that <strong><code>$craftPath</code></strong> is set correctly in '.__FILE__);
}

require_once $path;
