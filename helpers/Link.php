<?php
class Link {
	static public function linkTo() {
		global $config;

		$link = $config->url->base;
		$link .= $config->url->cleanurl ? '' : '/index.php';
		if (func_num_args() > 0) {
			$args = func_get_args();
			$link .= '/' . join('/', $args);
		}

		// Cleanup any double slashes we may have put in
		$link = preg_replace('#/+#', '/', $link);

		return $link;
	}
}		
