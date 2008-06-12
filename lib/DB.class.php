<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Database layer
 *
 * @package    Minion
 * @subpackage Classes
 */
class DB {
	/** Database connection instances based on named configurations */
	static private $instances = null;
	
	/**
	 * Create and return an instance of the PDO class using the named
	 * database configuration.  
	 *
	 * @access	public
	 * @param	string	$name				Database configuration name
	 * @return	mixed
	 */	
	public function getInstance($name) {
		global $config;

		if (!is_object(self::$instances)) {
			self::$instances = new stdClass;
		}

		if (self::$instances->{$name} instanceof PDO) {
			return self::$instances->{$name};
		} else {
			if (!isset($config->db[$name])) {
				System::logMessage('Requested database configuration does not exist (' . $name . ')',
					__FUNCTION__, __CLASS__);
				throw new Exception('Configuration does not exist');
			}
			$params = $config->db[$name];
			$separator = in_array($params->type, array('mysql', 'dblib')) ? ';'
				: ($params->type == 'pgsql' ? ' ' : '');
			$dsn  = $params->type . ':';
			if ($params->type == 'sqlite') {
				$dsn .= $params->name;
			} else if ($params->type == 'odbc') {
				$dsn .= $params->dsn;
			} else {
				!empty($params->host) ? $dsn .= 'host=' . $params->host . $separator : '';
				!empty($params->port) ? $dsn .= 'port=' . $params->host . $separator : '';
				$dsn .= 'dbname=' . $params->name;			
			}
		
			try {
				self::$instances->{$name} = new PDO($dsn, $params->user, $params->pass);
				self::$instances->{$name}->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$instances->{$name}->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
				return self::$instances->{$name};
			} catch (PDOException $e) {
				System::logMessage('Failed to connect to ' . $params->name . ' on ' . $params->host . ':' . $e->getMessage(),
					__FUNCTION__, __CLASS__);
				return false;
			}
		}
	}
}
