<h1>.: Books</h1>

<div>
	<form method="post" action="<?php echo Link::linkTo('book', 'search') ?>">
		<input type="text" size="32" name="search" />
		<input type="submit" value="Search" />
	</form>
</div>

<?php echo Table::generate($books, array(
	'exclude' => array('publisher_id', 'author_id'),
	'format' => array(
		'released' => 'substr("[field]", 0, 10);'
	),
	'actions' => array(
		'edit' => array(
			'replace' => array('isbn'),
			'img' => 'http://images.ebaumsworld.com/img/cms/edit.png',
			'url' => Link::linkTo('book', 'update', '[isbn]')
		),
		'delete' => array(
			'replace' => array('isbn', 'title'),
			'img' => 'http://images.ebaumsworld.com/img/cms/delete.png',
			'url' => Link::linkTo('book', 'delete', '[isbn]'),
			'click' => "return confirm('Are you sure you want to delete [title]?');"
		)
	)
)) ?>
