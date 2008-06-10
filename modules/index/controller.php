<?php
class indexController extends Controller {
	public function indexAction() {
		$book = new Book;
		$book = $book->findByPK(123456);
		$book->publisher;
		foreach ($book->authors as $author) {
			var_dump($author);
		}
		var_dump($book);
	}
}
