<?php
// filerepo.php
// File Repository
// By Abdullah Daud, chelahmy@gmail.com
// 2 January 2023

if (basename($_SERVER['SCRIPT_FILENAME']) === 'filerepo.php') {
	exit('Access denied!');
}

$app_path = dirname(__DIR__);
$filerepo_path = "../../filerepo";
$filerepo_root = $app_path . "/" . $filerepo_path;

function frepo_path($fn) {
	global $filerepo_root;
	$path = $filerepo_root;
	for ($i = 0; $i < 32; $i += 2) {
		$sf = substr($fn, $i, 2);
		$path .= "/$sf";
		if (!is_dir($path))
			return false;
	}
	return $path;
}

function is_frepo_exist($fn) {
	$path = frepo_path($fn);
	if ($path === false || strlen($path) <= 0)
		return false;
	return true;
}

function create_frepo($fn) {
	global $filerepo_root;
	$path = $filerepo_root;
	for ($i = 0; $i < 32; $i += 2) {
		$sf = substr($fn, $i, 2);
		$path .= "/$sf";
		if (!is_dir($path)) {
			if (!mkdir($path))
				return false;
		}
	}
	return true;
}

function new_repofilename() {
	for ($i = 0; $i < 3; $i++) {
		$bytes = random_bytes(16);
		$hexstr = bin2hex($bytes);
		if (!is_frepo_exist($hexstr))
			return $hexstr;
	}
	return "";
}

function new_frepo() {
	$fn = new_repofilename();
	if (strlen($fn) > 0 && create_frepo($fn))
		return $fn;
	return "";
}

function write_frepo_metadata($fn, $md) {
	if (!is_array($md))
		return false;
	$path = frepo_path($fn);
	if ($path === false || strlen($path) <= 0)
		return false;
	return file_put_contents($path . "/file.md", json_encode($md));
}

function read_frepo_metadata($fn) {
	$path = frepo_path($fn);
	if ($path === false || strlen($path) <= 0)
		return false;
	$json = file_get_contents($path . "/file.md");
	return json_decode($json, true);
}


