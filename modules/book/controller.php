<?php
class bookController extends Controller {
	public function indexAction() {
		$query = new Doctrine_Query();
		$this->_view->books = $query->from('Book b')->orderby('b.isbn')->execute();
	}
	
	public function searchAction() {
		if ($this->_request->isPostRequest()) {
			$wsdl_url = 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl?';
			$client = new SoapClient($wsdl_url);

			$params->AWSAccessKeyId = '0X7F3F4YJ4STN0JB8F82';
			$params->Operation = 'ItemSearch';
			$params->Request->SearchIndex = 'Books';
			$params->Request->Title = $_POST['search'];
			$params->Request->ResponseGroup = 'Large';

			$item = $client->ItemSearch($params);
			$this->_view->books = $item->Items->Item;
		}
	}
	
	public function addAction() {
		if ($this->_request->isPostRequest()) {
			$book = new GenericModel('books', 'isbn');
			foreach ($_POST as $key => $value) {
				$book->$key = $value;
			}
			if ($book->save()) {
				Alert::addAlert('New book added to bookshelf');
			} else {
				Alert::addAlert('Failed to add book to bookshelf');
			}
		}
		Request::redirectRequest($this->_config->url->base);
	}
}
