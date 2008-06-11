<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<title><?php echo (!empty($title) ? $title : $this->config->app->name . ' Version ' . $this->config->app->version) ?></title>
		<style type="text/css">
			<?php echo $renderedcss ?>
		</style>
		<script type="text/javascript">
			<?php echo $renderedjavascript ?>
		</script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->config->url->base ?>/styles/load-styles.php<?php if (!empty($_REQUEST['module'])):?>?module=<?php echo $_REQUEST['module'];?><?php endif;?>" />
		<script type="text/javascript" src="<?php echo $this->config->url->base ?>/scripts/load-scripts.php<?php if (!empty($_REQUEST['module'])):?>?module=<?php echo $_REQUEST['module'];?><?php endif;?>"></script>
		<?php $this->hook('layout_head') ?>
		
	</head>
	<body>
		<?php echo $content ?>
	
		<div id="footer">
			<?php $this->hook('layout_footer') ?>
			<hr />
			<div id="appversion"><?php echo $this->config->app->name ?> Version <?php echo $this->config->app->version ?></div>
		</div>
	</body>
</html>
