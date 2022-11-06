<?php

require_once('DownloadStationAPI.php');



function comparePriority($a, $b) {
	$priorityOrder = array(
		'high'		=> -10,
		'normal'	=> 0,
		'auto'		=> 0,
		'low'		=> 10,
	);

	return $priorityOrder[$a->additional->detail->priority] - $priorityOrder[$b->additional->detail->priority];
}

function sortTasks($a, $b) {
	$statusOrder = array(
		'finished'				=> 0,
		'finishing'				=> 10,
		'extracting'			=> 20,
		'hash_checking'			=> 30,
		'downloading'			=> 40,
		'waiting'				=> 50,
		'paused'				=> 60,
		'seeding'				=> 70,
		'filehosting_waiting'	=> 80,
		'error'					=> 90
	);

	if ($a->status != $b->status) return $statusOrder[$a->status] - $statusOrder[$b->status];

	switch ($a->status) {
	case 'downloading':
		$prio = comparePriority($a, $b);		
		return $prio == 0 ? $b->additional->transfer->speed_download - $a->additional->transfer->speed_download : 0;
	case 'waiting':
	case 'paused':
		$prio = comparePriority($a, $b);		
		return $prio == 0 ? ($a->size - $a->additional->transfer->size_downloaded) - ($b->size - $b->additional->transfer->size_downloaded) : $prio;
	default:
		return strcmp($a->title, $b->title);
	}
}

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
		case '-m':
		case '--max-downloads':
            if(++$i < $argCount) $config['MAXDOWNLOADS'] = $args[$i];
            break;
		case '-c':
		case '--cmd':
            if(++$i < $argCount) $config['CMD'] = $args[$i];
            break;
        default:
			// ERROR
            break;
        }
    }
    return $config;
}

$config = array(
	'DSMURI'		=> 'http://localhost:5000/webapi/',
);

$config = parseCommandLine($argv, $config);

$api = new DownloadStationAPI($config['USER'], $config['PASSWORD'], $config['DSMURI']);
$res = $api->getTaskList();

if ($res === false) {
	// ERROR
}

$tasks = $res->tasks;
usort($tasks, "sortTasks");

$remainingDownloads = $config['MAXDOWNLOADS'];

foreach ($tasks as $t) {
	echo($t->title . " - ". $t->status . " - " . $t->additional->detail->priority . " - " . $t->additional->transfer->size_downloaded . "/" . $t->size . " - " . $t->additional->transfer->speed_download . "b/s \n");

	if ($t->status === 'finished') {
		echo("CLEAR FINISHED!\n");
		if (!$api->deleteTasks($t->id)) {
			// ERROR
		} else {
			if (array_key_exists('CMD', $config)) {
				exec($config['CMD'] . " \"/" . $t->additional->detail->destination . "/" . $t->title  . "\"");
			}
			 // HANDLE DOWNLOAD
		}
	} 
	if (array_key_exists('MAXDOWNLOADS', $config)) {
		if ($t->status === 'downloading' || $t->status === 'waiting' || $t->status === 'seeding') {
			if ($remainingDownloads > 0) $remainingDownloads--;
			else {
				echo("PAUSE!\n");
				if (! $api->pauseTasks($t->id)) {
					// ERROR
				}
			}
		} else if ($t->status === 'paused') {
			if ($remainingDownloads > 0) {
				if ($api->resumeTasks($t->id)) {
					echo("RESUME!\n");
					$remainingDownloads--;
				} else {
					// ERROR
				}
			}
		}
	}
}

?>

