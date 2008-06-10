<?php
class indexController extends Controller {
	public function indexAction() {
		$book = new Book;
		$book = $book->findByPK(123456);
		$book->publisher;
		$book->authors;
		var_dump($book);
	}
}
