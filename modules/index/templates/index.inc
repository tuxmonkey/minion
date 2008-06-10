<h1>.: <?php echo $this->_config->app->name ?></h1>

<pre>
<?php var_dump($where);?>
</pre>

<div id="navigation">
	<form method="post" action="<?php echo $this->_config->url->base ?>">
		<label>Title:</label>
		<input type="text" size="16" name="title" value="<?php echo $_POST['title'] ?>" />
		<br />
		<label>Publisher:</label>
		<select name="publisher">
			<option value=""> --- </option>
			<?php foreach ($publishers as $publisher): ?>
			<?php var_dump($publisher);?>
			<option value="<?php echo $publisher ?>"<?php if ($_POST['publisher'] == $publisher): ?> selected="1"<?php endif; ?>><?php echo $publisher ?></option>
			<?php endforeach; ?>
		</select>
		<br />
		<input type="submit" value="Search" />
	</form>
</div>

<?php if (is_array($books)): ?>
<ul id="list">
	<?php foreach ($books as $book): ?>
	<li><?php echo $book->title ?></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<iframe id="viewer" />
