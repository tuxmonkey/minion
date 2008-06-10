<h1>.: Books</h1>

<div>
	<form method="post" action="<?php echo $this->_config->url->base ?>book/search/">
		<input type="text" size="32" name="search" />
		<input type="submit" value="Search" />
	</form>
</div>

<table width="100%">
	<tr>
		<td>ISBN</td>
		<td>Title</td>
		<td>Publisher</td>
	</tr>
	<?php if (is_array($books)): ?>
	<?php foreach ($books as $book): ?>
	<tr>
		<td><?php echo $book->isbn ?></td>
		<td><?php echo $book->title ?></td>
		<td><?php echo $book->publisher ?></td>
	</tr>
	<?php endforeach; ?>
	<?php endif; ?>
</table>