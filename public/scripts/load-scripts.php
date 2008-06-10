<?php
// Give us a shorter form of the DIRECTORY_SEPARATOR costant for simplicity
define('DS', DIRECTORY_SEPARATOR);

// Define the path for the system directory
define('_SYSTEM_', realpath('..' . DS . 'system'));

// Get the hash that the cachefile will be saved as
$hash = md5($_SERVER['REQUEST_URI']);

// Include all javascript files in the scripts directory
$files = glob(dirname(__FILE__) . DS . '*.js');
if (is_array($files)) {
	foreach ($files as $file) {
		print "/** BEGIN " . basename($file) . " **/\n";
		include_once $file;
		print "/** END " . basename($file) . " **/\n\n";
	}
}

// Include global javascript files defined in modules
$files = glob(_SYSTEM_ . DS . 'modules' . DS . '*' . DS . 'global.js');
if (is_array($files)) {
	foreach ($files as $file) {
		print "/** BEGIN " . str_replace(_SYSTEM_ . DS . 'modules', '', $file) . " **/\n";
		include_once $file;
		print "/** END " . str_replace(_SYSTEM_ . DS . 'modules', '', $file) . " **/\n\n";
	}
}

// Include module specific javascript file
if (!empty($_REQUEST['module'])) {
	if (file_exists(_SYSTEM_ . DS . 'modules' . DS . $_REQUEST['module'] . DS . 'module.js')) {
		print "/** BEGIN module.js **/\n";
		include_once _SYSTEM_ . DS . 'modules' . DS . $_REQUEST['module'] . DS . 'module.js';
		print "/** END module.js **/\n";
	}	
}
