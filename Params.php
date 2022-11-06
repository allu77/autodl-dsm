<?php
function parseCommandLine($inArgs, $config, $params, $remaining = null) {

	$args = array();
	foreach ($inArgs as $a) {
		if (strpos($a, '--') === 0 && substr_count($a, '=') === 1) {
			$tmpArgs = explode('=', $a);
			array_push($args, $tmpArgs[0], $tmpArgs[1]);
		} else {
			array_push($args, $a);
		}
	}

	$argCount = count($args);
	for ($i = 1; $i < $argCount; $i++) {
		if (strpos($args[$i], '-') === 0) {
			if (array_key_exists($args[$i], $params)) {
				if(++$i < $argCount) {
					$config[$params[$args[$i - 1]]] = $args[$i];
				} else {
					// ERROR
					return false;
				}
			}
		} else {
			if ($remaining === null) {
				// ERROR
				return false;
			}
			if (! array_key_exists($remaining, $config)) {
				$config[$remaining] = array($args[$i]);
			} else if (is_array($config[$remaining])) {
				array_push($config[$remaining], $args[$i]);
			} else {
				// ERROR
				return false;
			}	

		}
    }
    return $config;
}

function parseEnvParams($config, $prefix) {
	$prefixLen = strlen($prefix);
	if ($strLen === 0) return $config;

	foreach (array_keys(getenv()) as $e) {
		if (strpos($e, $prefix) === 0) {
			$config[substr($e, $prefixLen)] = getenv($e);
		}
	}	
	return $config;
}
?>
