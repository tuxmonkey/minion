<?php if ($this->_request->isPostRequest()):?>
<div>
	<?php foreach ($books as $book):?>
	<p> 
		<?php echo $book->ItemAttributes->ISBN ?> - <?php echo $book->ItemAttributes->Title ?><br />
		<small>Released <?php echo $book->ItemAttributes->PublicationDate?></small>
		<form method="post" action="<?php echo $this->_config->url->base ?>book/add/">
			<input type="hidden" name="isbn" value="<?php echo $book->ItemAttributes->ISBN ?>" />
			<input type="hidden" name="title" value="<?php echo $book->ItemAttributes->Title ?>" />
			<input type="hidden" name="list_price" value="<?php echo preg_replace('/[^0-9\.]/', '', $book->ItemAttributes->ListPrice->FormattedPrice) ?>" />
			<input type="hidden" name="publisher" value="<?php echo $book->ItemAttributes->Publisher ?>" />
			<input type="hidden" name="pages" value="<?php echo $book->ItemAttributes->NumberOfPages ?>" />
			<input type="hidden" name="released" value="<?php echo $book->ItemAttributes->PublicationDate ?>" />
			<input type="submit" value="Add Book" />
		</form>
	</p>
</div>
<?php endforeach;?>
<?php else:?>
<form method="post" action="<?php echo $this->_config->url->base ?>book/search/">
	<input type="text" size="32" name="search" />
	<input type="submit" value="Find" />
</form>
<?php endif;?>