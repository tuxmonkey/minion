<?php
Loader::loadHelper('Link');

class defaultController extends Controller {
	public function defaultAction() {
		$this->request->forward('book', 'search');
	}
}
