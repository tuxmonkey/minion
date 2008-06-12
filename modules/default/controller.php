<?php
Loader::loadHelper('Link');
Loader::loadModel('Book');

class defaultController extends Controller {
	public function defaultAction() {
		$book = new Book(123456);
	}
}
