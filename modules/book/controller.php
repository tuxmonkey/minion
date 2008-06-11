<?php
class bookController extends Controller {
	public function defaultAction() {
		$book = new Book;
		$this->view->books = $book->findAll();
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
			foreach ($this->request->post as $key => $value) {
				$book->$key = $value;
			}
			if ($book->save()) {
				Alert::addAlert('New book added to bookshelf');
			} else {
				Alert::addAlert('Failed to add book to bookshelf');
			}
		}
		$this->request->redirect($this->config->url->base);
	}
}
