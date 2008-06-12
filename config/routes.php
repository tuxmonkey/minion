<?php
// Pull in any module defined routes
System::hook('routes');

// Default system routes
Request::registerRoute('/(?<controller>[a-z\-_]+)/(?<action>[a-z\-_]+)');
Request::registerRoute('/(?<controller>[a-z\-_]+)');
