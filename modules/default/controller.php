<?php
class defaultController extends Controller {
	public function defaultAction() {
		$book = new Book;
		$this->view->book = $book->findByPK(123456);
	}
}
