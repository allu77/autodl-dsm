<?php

require_once('DownloadStationAPI.php');

function parseCommandLine($inArgs, $config) {

	$args = array();
	foreach ($inArgs as $a) {
		if (strpos($a, '--') === 0 && substr_count($a, '=') === 1) {
			$tmpArgs = explode('=', $a);
			array_push($args, $tmpArgs[0], $tmpArgs[1]);
		} else {
			array_push($args, $a);
		}
	}

	$argCount = count($inArgs);
	for ($i = 1; $i < $argCount; $i++) {
		switch ($inArgs[$i]) {
		case '-i':
		case '--infile':
			if(++$i < $argCount) $config['INFILE'] = $args[$i];
			break;
		case '--historyfile':
			if(++$i < $argCount) $config['HISTORYFILE'] = $args[$i];
			break;
		case '-t':
		case '--historytruncate':
			if(++$i < $argCount) $config['HISTORYTRUNCATE'] = $args[$i];
			break;
		case '-u':
		case '--user':
			if(++$i < $argCount) $config['USER'] = $args[$i];
			break;
		case '-p':
		case '--password':
			if(++$i < $argCount) $config['PASSWORD'] = $args[$i];
			break;
		case '-d':
		case '--dsmuri':
			if(++$i < $argCount) $config['DSMURI'] = $args[$i];
			break;

		default:
			if (strpos($args[$i], '-') === 0) {
				// ERROR
			} else {
				array_push($config['URILIST'], $args[$i]);
			}
			break;
		}
	}
	return $config;
}

$config = array(
	'HISTORYFILE'	=> 'task_history',
	'URILIST'		=> array(),
	'DSMURI'		=> 'http://localhost:5000/webapi/',
);
$config = parseCommandLine($argv, $config);


if (array_key_exists('INFILE', $config)) {
	if ($config['INFILE'] === '-') $config['INFILE'] = 'php://stdin';
	if ($inFile = fopen($config['INFILE'], 'r')) {
		while (!feof($inFile)) {
			$uri = trim(fgets($inFile));
			if (strlen($uri) > 0)  array_push($config['URILIST'], $uri);
		}
		fclose($inFile);
	} else {
		// ERROR
	}
}

$config['HISTORY'] = array();

if (file_exists($config['HISTORYFILE'])) {
	if ($historyFile = fopen($config['HISTORYFILE'], 'r')) {
		while (!feof($historyFile)) {
			$uri = trim(fgets($historyFile));
			if (strlen($uri) > 0)  array_push($config['HISTORY'], $uri);
		}
		fclose($historyFile);
	} else {
		// ERROR
	}
}

if ($historyFile = fopen($config['HISTORYFILE'], 'a')) {

	$api = new DownloadStationAPI($config['USER'], $config['PASSWORD'], $config['DSMURI']);

	foreach($config['URILIST'] as $uri) {
		if (array_search($uri, $config['HISTORY']) === false) {

			echo("Adding $uri\n");

			$res = $api->createTasks($uri);

			if ($res === false) {
				// ERROR
			} else {
				array_push($config['HISTORY'], $uri);
				fwrite($historyFile, $uri . "\n");
			}
		}
	}
	fclose($historyFile);
} else {
	// ERROR
}

if ($config['HISTORYTRUNCATE'] && (count($config['HISTORY']) > $config['HISTORYTRUNCATE'])) {

	$config['HISTORY'] = array_slice($config['HISTORY'], count($config['HISTORY']) - $config['HISTORYTRUNCATE']);

	if ($historyFile = fopen($config['HISTORYFILE'], 'w')) {
		foreach($config['HISTORY'] as $uri) {
			fwrite($historyFile, $uri . "\n");
		}
		fclose($historyFile);
	} else {
		// ERROR
	}

}


?>

