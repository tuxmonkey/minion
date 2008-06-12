<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Model Class
 *
 * @package    Minion
 * @subpackage Classes
 **/
class Model {
	/** Database configuration to use if not using the default */
	protected $_dbconfig = 'default';
	
	/** Copy of the config data to make it easier to get to */
	protected $_config = null;

	/** Metadata for the model table */
	public $_metadata = array();
	
	/** Name of the table that is being presented by the model */
	protected $_table = null;
	
	/** Primary Key for table model */
	protected $_key = null;

	/** Definitions for hasOne */
	protected $_hasOne = null;

	/** Definitions for hasMany */
	protected $_hasMany = null;

	/** Whether to autoload has* records */
	protected $_autoload = false;

	/** Caching time to live */
	protected $_ttl = false;
	
	/** Enable/Disable debug mode */
	public $_debug = false;
	
	/** Data for the given row that is being handled */
	protected $_data = array();

	/** Fields that have been modified since the last load or save action */
	protected $_modified = array();

	/** Whether we are in a saved state or not */
	protected $_saved = false;

	/** Array containing data to be used in next built query */
	protected $_queryData = array();
	
	/** The number of rows affected by the last update or delete */
	public $_affected = 0;

	/**
	 * Model constructor
	 *
	 * @access	public
	 * @return	object
	 */
	public function __construct() {
		$this->_config = $GLOBALS['config'];
		if (func_num_args() > 0) {
			if ($this->_config->cache->enabled === true && $this->_ttl !== false) {
				$args = func_get_args();
				$cachekey = call_user_func_array(array($this, 'generateCacheKey'), $args);
				$result = CacheManager::get($cachekey);
				if ($result !== false) {
					return $result;
				}
			}
			
			if (func_num_args() > 1 && is_array($this->_key)) {
				foreach ($this->_key as $key => $field) {
					$arg = func_get_arg($key);
					$this->where($field . ' = ?', $arg);
				}
			} else {
				$arg = func_get_arg(0);
				$this->where($this->_key . ' = ?', $arg);
			}

			$result = $this->limit(1)->import()->find();
			if ($result === true) {
				if ($this->_config->cache->enabled === true && $this->_ttl !== false) {
					CacheManager::set($cachekey, $this, $this->_ttl);
				}
		
				$this->setSaveState(true);	
			}
		}

		if ($this->_autoload === true) {
			$_ENV['autoloading'][get_class($this)] = true;
			if (is_array($this->_hasOne)) {
				foreach ($this->_hasOne as $key => $definition) {
					if (!in_array($definition['model'], array_keys($_ENV['autoloading']))) {
						$this->{$key};
					}
				}
			}
			if (is_array($this->_hasMany)) {
				foreach ($this->_hasMany as $key => $definition) {
					if (!in_array($definition['model'], array_keys($_ENV['autoloading']))) {
						$this->{$key};
					}
				}
			}
			unset($_ENV['autoloading'][get_class($this)]);
		}
	}		
	
	/**
	 * Magic method for retrieval of rows by fields existing in the table.
	 * 
	 * Parameters for findBy<field> methods:
	 *	string	criteria			What to match the field against
	 *
	 * @access	protected
	 * @param	string	$funcname			Name of function that was called
	 * @param	array 	$params				Array of parameters that were passed
	 * @return 	mixed
	 */
	protected function __call($funcname, $params = array()) {
		$fields = join('|', array_keys($this->_metadata));
		if (preg_match('/^findBy(' . $fields . ')$/i', $funcname, $match)) {
			$field = trim($match[1]);
			$value = trim($params[0]);
			$operator = $params[1] === true ? 'like' : '=';
			$this->where("$field $operator ?", $value);
			return $this->find();
		} else {
			return null;
		}
	}

	protected function __set($var, $value) {
		if (in_array($var, array_keys($this->_metadata))) {
			$this->_data[$var] = $value;
			$this->_modified[] = $var;
			$this->setSaveState(false);
		} else {
			$this->{$var} = $value;
		}
	}

	protected function __isset($var) {
		return isset($this->_data[$var]);
	}

	protected function __get($var) {
		if (in_array($var, array_keys($this->_metadata))) {
			return $this->_data[$var];
		} else {
			if (is_array($this->_hasOne) && in_array($var, array_keys($this->_hasOne))) {
				$class = $this->_hasOne[$var]['model'];
				Loader::loadModel($class);
				$obj = new $class;
				$obj->where($this->_hasOne[$var]['foreign'] . ' = ?', $this->{$this->_hasOne[$var]['local']});
				$obj->limit(1);
				list($this->{$var}) = $obj->find();
				return $this->{$var};
			} else if (is_array($this->_hasMany) && in_array($var, array_keys($this->_hasMany))) {
				$class = $this->_hasMany[$var]['model'];
				Loader::loadModel($class);
				$obj = new $class;
				$obj->where($this->_hasMany[$var]['foreign'] . ' = ?', $this->{$this->_hasMany[$var]['local']});
				$this->{$var} = $obj->find();
				return $this->{$var};
			}
		}
	}

	public function find($single = false) {
		$db = DB::getInstance($this->_dbconfig);
		if (!($db instanceof PDO)) {
			return false;
		}

		$sql = $this->buildQuery('SELECT');
		try {
			$stmt = $db->prepare($sql);
			$params = is_array($this->_queryData['whereParams'])
				? $this->_queryData['whereParams'] : array();
			$params = is_array($this->_queryData['havingParams'])
				? array_merge($params, $this->_queryData['havingParams']) : $params;
			if (isset($this->_queryData['import'])) {
				$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			} else {
				$stmt->setFetchMode(PDO::FETCH_CLASS, get_class($this));
			}
			$stmt->execute($params);
			if (isset($this->_queryData['import'])) {
				$stmt->fetch();
				$result = true;
			} else {
				$result = $single === true ? $stmt->fetchColumn(0) : $stmt->fetchAll();
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}

		$this->clearQuery();
		return $result;
	}

	/**
	 * Retrieve a set of rows based on the "page" they would be on.
	 *
	 * @access	public
	 * @param	int		$page			Page number to retrieve items for
	 * @param	int		$items			Number of items per page
	 * @return 	array
	 */
	public function findByPage($page = 1, $items = null) {
		$items = !is_null($items) ? $items : $this->_config->paginate->limit;
		return $this->limit($items)->offset($items * ($page - 1))->find();
	}
	
	/**
	 * Insert new row into the table
	 *
	 * @access	public
	 * @param	array 	$data			Array containing data to be inserted into the table
	 * @return 	bool
	 */
	public function insert($data) {
		if (!is_array($data)) {
			System::logMessage('Data not passed as array', __FUNCTION__, __CLASS__);
		}

		$db = DB::getInstance($this->_dbconfig);
		if (!($db instanceof PDO)) {
			return false;
		}

		$sql = $this->buildQuery('INSERT', $data);
		try {
			$stmt = $db->prepare($sql);
			$result = $stmt->execute(array_values($data));
			if ($result === true && !is_array($this->_key) && !isset($this->{$this->_key})) {
				$this->{$this->_key} = $db->lastInsertId();
			}
			return $result;
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
	}
	
	/**
	 * Update row in the table
	 *
	 * @access	public
	 * @param	array 	$data			Array containing data to be inserted into the table
	 * @return 	bool
	 */
	public function update($data) {
		if (!is_array($data)) {
			System::logMessage('Data not passed as array', __FUNCTION__, __CLASS__);
			return false;
		}
	
		$db = DB::getInstance($this->_dbconfig);
		if (!($db instanceof PDO)) {
			return false;
		}

		$sql = $this->buildQuery('UPDATE', $data);
		$params = array_values($data);
		$params = is_array($this->_queryData['whereParams'])
			? array_merge($params, $this->_queryData['whereParams']) : $params;
		
		try {
			$stmt = $db->prepare($sql);
			foreach ($params as $key => $val) {
				$stmt->bindValue(($key + 1), $val);
			}
			$result = $stmt->execute();
			$this->_affected = $stmt->rowCount();
			
			if ($this->_config->cache->enabled === true && $retval === true) {
				$cachekey = $this->generateCacheKey();
				CacheManager::delete($cachekey);
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
		
		$this->clearQuery();
		return $result;
	}
	
	/**
	 * Delete rows from the table
	 *
	 * @access	public
	 * @return 	bool
	 */
	public function delete() {
		$db = DB::getInstance($this->_dbconfig);
		if (!($db instanceof PDO)) {
			return false;
		}

		$sql = $this->buildQuery('DELETE');
		try {
			$stmt = $db->prepare($sql);
			$retval = $stmt->execute($this->_queryData['whereParams']);
			$this->_affected = $stmt->rowCount();
			
			if ($this->_config->cache->enabled === true && $retval === true) {
				$cachekey = $this->generateCacheKey();
				CacheManager::delete($cachekey);
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}

		$this->clearQuery();
		return $retval;
	}
	
	/**
	 * Save the current row being worked with into back to the table
	 *
	 * @access	public
	 * @param	string	$method				Method to be used for the save (insert|update)
	 * @param	bool	$cascade			Whether to cascade the save action
	 * @return 	bool
	 */
	public function save($method = 'insert', $cascade = false) {
		// If we're already in a saved state don't even bother
		if ($this->_saved === true) {
			return true;
		}

		$data = array();
		foreach ($this->_modified as $field) {
			$data[$field] = $this->_data[$field];
		}

		if ($method == 'update') {
			if (is_array($this->_key)) {
				foreach ($this->_key as $key) {
					$this->where($key . ' = ?', $this->{$key});
				}
			} else {
				$this->where($this->_key . ' = ?', $this->{$this->_key});
			}
		}

		$retval = $this->$method($data);	
		if ($this->_config->cache->enabled === true && $retval === true) {
			$cachekey = $this->generateCacheKey();
			CacheManager::delete($cachekey);
		}

		if ($cascade === true) {
			if (is_array($this->_hasOne)) {
				$keys = array_keys($this->_hasOne);
				foreach ($keys as $key) {
					$this->{$key}->save($method, $cascade);
				}
			}
			if (is_array($this->_hasMany)) {
				$keys = array_keys($this->_hasMany);
				foreach ($keys as $key) {
					foreach ($this->{$key} as $row) {
						$row->save($method, $cascade);
					}
				}
			}
		}
		return $retval;
	}
	
	/**
	 * Set the list of fields to be retrieved in the next select query
	 *
	 * @access	public
	 * @param	string	$fields			Fields to be retrieved
	 * @return 	object
	 */
	public function fields($fields) {
		if (!empty($fields)) {
			$this->_queryData['fields'] = $fields;
		}
		return $this;
	}
	
	/**
	 * Add a condition onto the stack to be used in the next query
	 * Conditions may included place holders for prepared parameters,
	 * and then pass the parameters as extra arguments to the function.
	 * For example:
	 *		Model::where('first_name = ?', 'Bob');
	 *
	 * You can also pass multiple conditions in one call to the method
	 * by simply adding AND/OR between conditions, Example:
	 *		Model::where('first_name = ? AND last_name = ?', 'Bob', 'Smith');
	 *
	 * @access	public
	 * @param	array 	$conditions		Condition(s) to be added to the stack
	 * @return 	object
	 */
	public function where($condition) {
		$this->_queryData['where'][] = $condition;
		$numargs = func_num_args();
		if ($numargs > 1) {
			for ($x = 1; $x < $numargs; $x++) {
				$param = func_get_arg($x);
				if (is_array($param)) {
					$this->_queryData['whereParams'][] = join(',', $params);
				} else {
					$this->_queryData['whereParams'][] = $param;
				}
			}
		}
		return $this;
	}
	
	/**
	 * Add fields onto the group stack
	 *
	 * @access	public
	 * @param	mixed	$field				Field(s) to be added to the group stack.
	 * @return 	object
	 */
	public function groupBy($field) {
		if (is_array($field)) {
			foreach ($field as $item) {
				$this->groupBy($item);
			}
		} else if (strpos($field, ',') !== false) {
			$fields = explode(',', $field);
			foreach ($fields as $field) {
				$this->groupBy($field);
			}
		} else {
			$this->_queryData['groupBy'][] = trim($field);
		}
		return $this;
	}
	
	/**
	 * Add items onto the order stack.  Items should be given in the format of
	 * array(field[, sortorder]) or a single item directly as method parameters.
	 * Multiple items can be passed at once using
	 * a multidimensional array.
	 *
	 * @access	public
	 * @param	string 	$order		Field(s) to order on
	 * @return 	object
	 */
	public function orderby($order) {
		if (!empty($order)) {
			$this->_queryData['orderBy'][] = trim($order);
		}
		return $this;
	}
	
	/**
	 * Set the limit to be used for the next select query
	 *
	 * @access	public
	 * @param	int		$limit			Number of rows to set limit to
	 * @return 	object
	 */
	public function limit($limit) {
		if (is_numeric($limit)) {
			$this->_queryData['limit'] = $limit;
		}
		return $this;
	}
	
	/**
	 * Set the offset to be used in the next select query
	 *
	 * @access	public
	 * @param	int		$offset			Offset to be used
	 * @return 	object
	 */
	public function offset($offset) {
		if (is_numeric($offset)) {
			$this->_queryData['offset'][] = $offset;
		}
		return $this;
	}
	
	/**
	 * Add a join statement for the next select query.  
	 *
	 * @access	public
	 * @param	string	$join				Join statement to add
	 * @return 	object
	 */
	public function join($join) {
		$this->_queryData['joins'][] = $join;
		return $this;
	}
	
	/**
	 * Optimization hint to be used in next query
	 * 
	 * @access	public
	 * @param 	string	$hint			Optimization hint to be used in next query
	 * @return 	object
	 */
	public function optHint($hint) {
		$this->_queryData['optHint'] = $hint;
		return $this;
	}
	
	/**
	 * Index hint to be used in next query
	 * 
	 * @access	public
	 * @param 	string	$hint			Index hint to be used in next query
	 * @return 	object
	 */
	public function indexHint($hint) {
		$this->_queryData['indexHint'] = $hint;
		return $this;
	}	
	
	/**
	 * Enable or disable maintaining of conditions, limits, groups, etc.
	 * through multiple queries
	 * 
	 * @access	public
	 * @param 	bool	$clear			Whether or not to clear the options
	 * @return 	object
	 */
	public function clearAfter($clear = true) {
		if ($clear === false) {
			$this->_queryData['clearAfter'] = 0;
		} else if (isset($this->_queryData['clearAfter'])) {
			unset($this->_queryData['clearAfter']);
		}
		return $this;
	}
	
	protected function buildQuery($type = 'SELECT', &$data = null) {
		switch (strtoupper($type)) {
			case 'SELECT':
				$sql  = "SELECT ";
				$sql .= isset($this->_queryData['fields']) ? $this->_queryData['fields'] : '*';
				$sql .= " FROM " . $this->_table;
				break;

			case 'INSERT':
				if (is_array($data) && count($data) > 0) {
					$sql  = "INSERT INTO " . $this->_table . " (" . join(',', array_keys($data)) . ") ";
					$sql .= "VALUES(" . (count($data) > 1 ? str_repeat('?,', count($data) - 1) : '') . "?)";
				}
				break;

			case 'UPDATE':
				if (is_array($data) && count($data) > 0) {
					foreach ($data as $key => $val) {
						$dataset .= !empty($dataset) ? ', ' : '';
						if ($val[0] == '=' && $this->_metadata[$key]['allowLiteral'] === true) {
							$dataset .= $key . ' = ' . substr($val, 1) . ' ';
							unset($data[$key]);
						} else {
							$dataset .= $key . ' = ? ';
						}
					}
				}

				$sql = "UPDATE " . $this->_table . " SET " . $dataset;
				break;

			case 'DELETE':
				$sql = "DELETE FROM " . $this->_table;

				if (!is_array($this->_queryData['where']) || count($this->_queryData['where']) == 0
				|| !is_array($this->_queryData['whereParams']) || count($this->_queryData['whereParams']) == 0) {
					if (is_array($this->_key)) {
						foreach ($this->_key as $key) {
							$this->where($key . ' = ?', $this->{$key});
						}
					} else {
						$this->where($this->_key . ' = ?', $this->{$this->_key});
					}
				}
				break;
		}

		$sql .= isset($this->_queryData['joins']) ? ' ' . join(' ', $this->_queryData['joins']) : '';
		$sql .= isset($this->_queryData['where']) ? ' WHERE ' . join(' AND ', $this->_queryData['where']) : '';
		$sql .= isset($this->_queryData['groupBy']) ? ' GROUP BY ' . join(',', $this->_queryData['groupBy']) : '';
		$sql .= isset($this->_queryData['having']) ? ' HAVING ' . join(' AND ', $this->_queryData['having']) : '';
		$sql .= isset($this->_queryData['orderBy']) ? ' ORDER BY ' . join(',', $this->_queryData['orderBy']) : '';
		$sql .= isset($this->_queryData['limit']) ? ' LIMIT ' . $this->_queryData['limit'] : '';
		$sql .= isset($this->_queryData['offset']) ? ' OFFSET ' . join(',', $this->_queryData['offset']) : '';

		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}

		return $sql;
	}

	/**
	 * Return the result of the build query process to be reviewed
	 *
	 * @access	public
	 * @param	string	$type			Type of query that is being built (SELECT, DELETE, INSERT, UPDATE)
	 * @return	string
	 */
	public function getQuery($type = 'SELECT') {
		return $this->buildQuery($type);
	}

	/**
	 * Give us a clean slate for the prepared query data
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearQuery() {
		if (isset($this->_queryData['clearAfter'])) {
			$this->clearAfter();
		} else {
			$this->_queryData = array();
		}
		return $this;
	}

	public function setSaveState($state) {
		$this->_saved = $state;
		return $this;
	}

	public function setDB($dbconfig) {
		$this->_dbconfig = $dbconfig;
		return $this;
	}

	public function setTTL($ttl) {
		$this->_ttl = $ttl;
		return $this;
	}

	public function import() {
		$this->_queryData['import'] = true;
		return $this;
	}

	/**
	 * Generate the cache key for the current object
	 *
	 * @access	public
	 * @return	string
	 */
	public function generateCacheKey() {
		if (func_num_args() > 0) {
			if (is_array($this->_key)) {
				$cachekey = $this->_table . '-pk-' . join('-', func_get_args());
			} else {
				$cachekey = $this->_table . '-pk-' . func_get_arg(0);
			}
		} else {
			if (is_array($this->_key)) {
				$cachekey = $this->_table . '-pk';
				foreach ($this->_key as $key) {
					$cachekey .= '-' . $this->{$key};
				}
			} else {
				$cachekey = $this->_table . '-pk-' . $this->{$this->_key};
			}
		}
		return $cachekey;
	}	
}
