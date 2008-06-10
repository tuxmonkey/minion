<?php
class Book extends Model {
	static protected $_table = 'books';
	
	static protected $_metadata = array(
		'isbn' => array(
			'type' => 'text',
			'length' => 16,
			'primary' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 64
		),
		'publisher_id' => array(
			'type' => 'int',
			'length' => 20,
			'foreign' => array('publishers' => 'id')
		),
		'pages' => array(
			'type' => 'int',
			'length' => 4,
			'attrs' => array('unsigned')
		),
		'released' => array(
			'type' => 'datetime'
		),
		'author_id' => array(
			'type' => 'int',
			'length' => 20,
			'foreign' => array('authors' => 'id')
		)
	);
	
	static public function getWhere() {
		return join(' AND ', self::$_where);
	}
	
	static public function getParam() {
		return join(',', self::$_params);
	}
}