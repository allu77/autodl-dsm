<?php

require_once('DownloadStationAPI.php');
require_once('Params.php');

$params = array(
	'-i'				=> 'INFILE',
	'--infile'			=> 'INFILE',
	'--historyfile'		=> 'HISTORYFILE',
	'-t'				=> 'HISTORYTRUNCATE',
	'--historytruncate'	=> 'HISTORYTRUNCATE',
	'-u'				=> 'USER',
	'--user'			=> 'USER',
	'-p'				=> 'PASSWORD',
	'--password'		=> 'PASSWORD',
	'-d'				=> 'DSMURI',
	'--dsmuri'			=> 'DSMURI'
);

$config = array(
	'HISTORYFILE'	=> 'task_history',
	'URILIST'		=> array(),
	'DSMURI'		=> 'http://localhost:5000/webapi/',
);

$config = parseEnvParams($config, 'AUTODL_');
$config = parseCommandLine($argv, $config, $params, 'URILIST');


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

