<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Management of the cache for both APC and memcache
 *
 * @package    Minion
 * @subpackage Classes
 */
class CacheManager {
	static $connection = null;

	/**
	 * Setup the memcache connection pool
	 *
	 * @static
	 * @access	public
	 * @return	mixed
	 */
	static public function _connect() {
		global $config;

		if ($config->cache->type == 'memcache') {
			if (self::$connection instanceof Memcache) {
				$cache = self::$connection;
			} else {
				$cache = new Memcache();
				foreach ($config->cache->servers as $server => $port) {
					$cache->addServer($server, $port, false);
				}
			}
		}
		return $cache;
	}
	
	/**
	 * Set a value in cache
	 *
	 * @static
	 * @access	public
	 * @param	string	$key				Key to set
	 * @param	string	$value				Value to set
	 * @param	int		$expire				Number of seconds before expiration
	 * @param	string	$type				Cache type to use for this set
	 * @return	bool
	 */
	static public function set($key, $value, $expire = 0, $type = null) {
		global $config;

		if (($config->cache->enabled !== true && $type != 'file') || empty($key)) {
			return false;
		}
	
		if ($type == 'file' && !file_exists($config->cache->filedir)) {
			System::logMessage('Cache file directory is not writable by the web server', __FUNCTION__, __CLASS__);
			return false;
		}
	
		$retval = false;
		$type = !is_null($type) ? $type : $config->cache->type;

		switch ($type) {
			case 'memcache':
				$cache = self::_connect();	
				$retval = $cache->set($key, $value, MEMCACHE_COMPRESSED, $expire);
				break;
			
			case 'apc':
				$retval = apc_store($key, $value, $expire);
				break;

			case 'file':
				if (!isset($config->cache->filedir)) {
					return false;
				}
				$subdir = substr($key, 0, 1);
				$retval = file_put_contents($config->cache->filedir . DS . $subdir . DS . $key, $value);
				break;
		}

		return $retval;
	}
	
	/**
	 * Retrieve a value from memcache
	 *
	 * @static
	 * @access	public
	 * @param	string	$key				Key to retrieve
	 * @param	string	$type				Cache type to use for this set
	 * @return	mixed
	 */
	static public function get($key, $type = null) {
		global $config;

		if ($config->cache->enabled !== true || empty($key)) {
			return false;
		}

		$type = !is_null($type) ? $type : $config->cache->type;
		$retval = false;

		switch ($type) {
			case 'memcache':
				$cache = self::_connect();
				$retval = $cache->get($key);
				break;

			case 'apc':
				$retval = apc_fetch($key);
				break;

			case 'file':
				if (!isset($config->cache->filedir)) {
					$retval = false;
				} else {
					$subdir = substr($key, 0, 1);
					if (file_exists($config->cache->filedir . DS . $subdir . DS . $key)) {
						$retval = file_get_contents($config->cache->filedir . DS . $subdir . DS . $key);
					} else {
						$retval = false;
					}
				}
				break;
		}

		return $retval;
	}
	
	/**
	 * Remove a key from all cache servers
	 *
	 * @static
	 * @access	public
	 * @param	string	$key				Key to be removed
	 * @param	string	$type				Cache type to use for this set
	 * @return	bool
	 */
	static public function delete($key, $type = null) {
		global $config;

		if (($config->cache->enabled !== true && $type != 'file') || empty($key)) {
			return false;
		}

		$type = !is_null($type) ? $type : $config->cache->type;
		$retval = false;

		switch ($type) {
			case 'memcache':
				$cache = self::_connect();
				$retval = $cache->delete($key);
				break;

			case 'apc':
				$retval = apc_delete($key);
				break;

			case 'file':
				if (!isset($config->cache->filedir)) {
					return false;
				}
				
				$subdir = substr($key, 0, 1);
				$retval = unlink($config->cache->filedir . DS . $subdir . DS . $key);
				break;
		}

        return $retval;
	}
}
