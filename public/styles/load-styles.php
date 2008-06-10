<?php
# Give us a shorter form of the DIRECTORY_SEPARATOR costant for simplicity
define('DS', DIRECTORY_SEPARATOR);

# Define the path for the system directory
define('_SYSTEM_', '..' . DS . 'system');

// Include all style files in the scripts directory
$files = glob(dirname(__FILE__) . DS . '*.css');
if (is_array($files)) {
	foreach ($files as $file) {
		print "/** BEGIN " . basename($file) . " **/\n";
		include_once $file;
		print "/** END " . basename($file) . " **/\n\n";
	}
}

// Include global style files defined in modules
$files = glob(_SYSTEM_ . DS . 'modules' . DS . '*' . DS . 'global.css');
if (is_array($files)) {
	foreach ($files as $file) {
		print "/** BEGIN " . str_replace(_SYSTEM_ . DS . 'modules', '', $file) . " **/\n";
		include_once $file;
		print "/** END " . str_replace(_SYSTEM_ . DS . 'modules', '', $file) . " **/\n\n";
	}
}

// Include module specific style file
if (!empty($_REQUEST['module'])) {
	if (file_exists(_SYSTEM_ . DS . 'modules' . DS . $_REQUEST['module'] . DS . 'module.css')) {
		print "/** BEGIN module.js **/\n";
		include_once _SYSTEM_ . DS . 'modules' . DS . $_REQUEST['module'] . DS . 'module.css';
		print "/** END module.js **/\n";
	}	
}