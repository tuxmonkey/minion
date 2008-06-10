<?php
// Boostrap the framework
require_once '../bootstrap.php';

// Handle Request
$request->handleRequest();

// Render results for user
$view->render();
