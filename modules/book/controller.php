<?php
Loader::loadModel('Book');
Loader::loadHelper('Link');

class bookController extends Controller {
	public function defaultAction() {
		$book = new Book;
		$this->view->books = $book->findByPage(1);
	}
	
	public function searchAction() {
		if ($this->request->isPost()) {
			$wsdl_url = 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl?';
			$client = new SoapClient($wsdl_url);

			$params->AWSAccessKeyId = '0X7F3F4YJ4STN0JB8F82';
			$params->Operation = 'ItemSearch';
			$params->Request->SearchIndex = 'Books';
			$params->Request->Title = $_POST['search'];
			$params->Request->ResponseGroup = 'Large';

			$item = $client->ItemSearch($params);
			$this->view->books = $item->Items->Item;
		}
	}
	
	public function addAction() {
		if ($this->request->isPost()) {
			$book = new Book;

			if (!empty($this->params->post['publisher'])) {
				$publisher = new Publisher;
				$publisher->name = $this->params->post['publisher'];
				$book->publisher_id = $publisher->save();
			}

			if (!empty($this->params->post['author'])) {
				$author = new Author;
				$author->name = $this->params->post['author'];
				$book->author_id = $author->save();
			}
			
			foreach ($this->params->post as $key => $value) {
				$book->$key = $value;
			}
		
			if ($book->save()) {
				Alert::addAlert('New book added to bookshelf');
			} else {
				Alert::addAlert('Failed to add book to bookshelf');
			}
		}
		$this->request->redirect(Link::linkTo('book'));
	}
}
