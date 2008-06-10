<?php
class indexController extends Controller {
	public function indexAction() {
		$this->_view->where = Book::findByPK(123456);
	}
}