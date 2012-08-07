<?
// path where the apache logs are to be found
define('LOG_PATH', "/var/log/apache2/");

// number of lines to start with in the access log - improving this may reduce the number of times it checks
define('ACCESS_LINES', 1000);

// number of lines to start with in the error log
define('ERROR_LINES', 100);

// get env value
$log_path = isset($_ENV["apache_vh_log_path"]) ? getenv("apache_vh_log_path") : LOG_PATH;
$access_lines = isset($_ENV["apache_vh_access_lines"]) ? getenv("apache_vh_access_lines") : ACCESS_LINES;
$error_lines = isset($_ENV["apache_vh_error_lines"]) ? getenv("apache_vh_error_lines") : ERROR_LINES;

// $excludes is an array of urls to exclude. use it to exclude testing sites.
$excludes = array();
$excludes[]="|^.dev.*$|i";

// $includes is a list of sites that should be included, regardless of the above regex.
$includes = array();
$includes[] = "development.com";

exec(sprintf("find %s -iname '*-access.log' | grep -v dev", $log_path), $files);
asort($files);

function get_time($file, $num_lines) {
	$string = exec(sprintf("tail -n %s %s 2>/dev/null | head -n 1", $num_lines, $file));
	$checkstring =  exec(sprintf("head -n 1 %s 2>/dev/null", $file));

	// if we get to log start, use size of log as num_lines, to minimise skew.
	$checkstring =  exec(sprintf("head -n 1 %s 2>/dev/null", $file));
	if ($checkstring == $string) {
		$seen_lines =  exec(sprintf("wc -l %s 2>/dev/null", $file));
		if (file_exists($file . ".1")) {
			$string = exec(sprintf("tail -n %s %s.1 2>/dev/null | head -n 1", ($num_lines - $seen_lines), $file));
		} else {
			$num_lines = $seen_lines;
		}
	}

	preg_match('|^([^\[]*)?\[([^]]+)\].*|', $string, $matches);
	if (!isset($matches[2])) {
		$time = time();
	} else {
		$time = strtotime($matches[2]);
	}
	$ago = time() - $time;
	return $ago;
}

function hashname($file) {
	return 'f_' . preg_replace('|[^a-z0-9]|i', '', sitename($file));
}

function sitename($file) {
	list($sitename)=explode("_", basename($file));
	return $sitename;
}
