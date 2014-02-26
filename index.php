<?php
/**
  * Displays form for submitting list of barcodes
  *
  * @author Jared Howland <sirsi@jaredhowland.com>
  * @version 2014-02-26
  * @since 2014-02-22
  *
  */
require_once 'config.php';

$html = <<<HTML
	<h1>Lettering Report</h1>
		
	<form action="label.php" method="get" accept-charset="utf-8">
		<label for="barcodes">Barcodes (one per line, limit 100):</label><br/>
		<textarea name="barcodes" rows="8" cols="40"></textarea><br/>
		<button class="button" type="submit">Run report</button>
	</form>
HTML;
  

$html = array('title' => 'Home', 'html' => $html);
template::display('generic.tmpl', $html);
?>