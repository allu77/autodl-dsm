<?php

require_once('DownloadStationAPI.php');
require_once('Params.php');

$config = array(
	'DSMURI'		=> 'http://localhost:5000/webapi/',
);

$params = array(
	'-u'				=> 'USER',
	'--user'			=> 'USER',
	'-p'				=> 'PASSWORD',
	'--password'		=> 'PASSWORD',
	'-d'				=> 'DSMURI',
	'--dsm-uri'			=> 'DSMURI',
);

$config = parseEnvParams($config, 'AUTODL_');
$config = parseCommandLine($argv, $config, $params);

$api = new DownloadStationAPI($config['USER'], $config['PASSWORD'], $config['DSMURI']);
$res = $api->getTaskList();

if ($res === false) {
	exit(1);
}

$tasks = $res->tasks;

printf(true ? "%d\n" : "There are %d active tasks.\n", count($res->tasks));
exit(0);

?>

