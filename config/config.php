<?php
// Here for convience
define('DS', DIRECTORY_SEPARATOR);

// Create the main configuration object
$config = new stdClass();

// General Application Configuration
$config->app->name = 'Bookshelf';
$config->app->version = '0.1';

// Directory Configuration
$config->dir->system = realpath(dirname(__FILE__) . DS . '..');
$config->dir->public = $config->dir->system . DS . 'public';
$config->dir->bin = $config->dir->system . DS . 'bin';
$config->dir->templates = $config->dir->system . DS . 'templates';
$config->dir->modules = $config->dir->system . DS . 'modules';
$config->dir->classes = $config->dir->system . DS . 'classes';
$config->dir->sessions = $config->dir->system . DS . 'sessions';
$config->dir->vendors = $config->dir->system . DS . 'vendors';

// URL Access Configuration
$config->url->base = '/minion/public';
$config->url->reserved = array('ajax');
$config->url->cleanurl = true;
$config->url->separator = '=';

// Database Configuration
$config->db['default']->type = 'mysql';
$config->db['default']->host = '127.0.0.1';
$config->db['default']->port = '';
$config->db['default']->user = 'root';
$config->db['default']->pass = '';
$config->db['default']->name = 'bookshelf';

// Session Configuration
$config->session->handler = 'files';
$config->session->name = $config->app->name . '-' . $config->app->version;
$config->session->domain = '';
$config->session->path = '/';
$config->session->savepath = $config->dir->sessions;

// Cache Configuration
$config->cache->enabled = false;
$config->cache->type = 'memcache';
$config->cache->servers = array();

// Pagination Configuration
$config->paginate->limit = 20;
$config->paginate->template = 'paginate.inc';

