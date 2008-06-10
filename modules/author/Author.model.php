<?php
class Author extends Model {
	protected $_table = 'authors';
	protected $_key = 'id';	
	protected $_metadata = array(
		'id' => array(
			'type' => 'int',
			'length' => 10
		),
		'name' => array(
			'type' => 'text',
			'length' => 64
		)
	);
	protected $_hasMany = array(
		'books' => array('model' => 'Book', 'local' => 'id', 'foreign' => 'author_id'));
}
