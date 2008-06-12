<?php
Loader::loadModel('Book');
Loader::loadHelper('Link');

class bookController extends Controller {
	public function defaultAction() {
		Loader::loadHelper('Table');

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
			Loader::loadModel('Publisher');
			Loader::loadModel('Author');

			$book = new Book($this->params->post['isbn']);
			if (empty($book->title)) {
				$book->isbn = $this->params->post['isbn'];
				$book->title = $this->params->post['title'];
				$book->released = $this->params->post['released'];
				$book->pages = $this->params->post['pages'];

				$publisher = new Publisher;
				$publisher->import()->findByName($this->params->post['publisher']);
				if (empty($publisher->name)) {
					$publisher->name = $this->params->post['publisher'];
					$publisher->save('insert');
				}
				$book->publisher_id = $publisher->id;

				$author = new Author;
				$author->import()->findByName($this->params->post['author']);
				if (empty($author->name)) {
					$author->name = $this->params->post['author'];
					$author->save('insert');
				}
				$book->author_id = $author->id;

				if ($book->save('insert')) {
					Alert::addAlert('New book added to bookshelf');
				} else {
					Alert::addAlert('Failed to add book to bookshelf');
				}
			} else {
				Alert::addAlert('That book is already on the shelf.');
			}
		}
		$this->request->redirect(Link::linkTo('book'));
	}
}
