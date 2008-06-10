<?php
class Book extends Model {
	protected $_table = 'books';
	protected $_key = 'isbn';	
	protected $_metadata = array(
		'isbn' => array(
			'type' => 'text',
			'length' => 16
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
	protected $_hasOne = array(
		'publisher' => array('model' => 'Publisher', 'local' => 'publisher_id', 'foreign' => 'id'));
	protected $_hasMany = array(
		'authors' => array('model' => 'Author', 'local' => 'author_id', 'foreign' => 'id'));
}
