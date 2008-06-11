<?php
class Link {
	static public function linkTo($controller, $action = null, $params = array()) {
		global $config;

		$link = $config->url->base;
		$link .= $config->url->cleanurl ? '' : '/index.php';
		$link .= '/' . $controller;

		if (!is_null($action)) {
			$link .= '/' . $action;
		}

		if (count($params) > 0) {
			foreach ($params as $key => $value) {
				if (is_numeric($key)) {
					$link .= '/' . $value;
				} else {
					$link .= '/' . $key . $config->url->separator . $value;
				}
			}
		}

		return $link;
	}
}		
