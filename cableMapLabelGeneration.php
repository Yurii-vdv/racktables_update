<?php

global $tab, $ajaxhandler;
$tab['object']['csvmap'] = 'Cable labels';
registerTabHandler('object', 'csvmap', 'renderCSVmap');
$ajaxhandler['cablemap-report'] = 'cableMapAJAX';

function renderCSVmap ($object_id)
{
	// see renderPortsForObject() in interface.php
	$object = spotEntity ('object', $object_id);
	amplifyCell ($object);
	startPortlet('Cabling');
	echo "<table class='widetable zebra' border=0 cellspacing=0 cellpadding=5 align='center'><tr><th>Source hostname</th><th>Local port</th><th>Remote port</th><th>Remote hostname</th></tr>";
	$lines = 0;
	foreach ($object['ports'] as $port)
		if ($port['remote_object_id'])
		{
			echo "<tr><td>${object['name']}</td><td>${port['name']}</td><td>${port['remote_name']}</td><td>${port['remote_object_name']}</td></tr>";
			$lines++;
		}
	echo "</table>";
	echo "<p>";
	if ($lines > 0)
	{
		echo "<form method=\"POST\" action=\"index.php?module=ajax&ac=cablemap-report&objid=${object_id}\" accept-charset=\"UTF-8\">";
		echo "<input type=\"submit\" name=\"csvmap\" value=\" Export CSV \" />";
		echo "</form><p>";
	}
	else
		echo "No data available";
	finishPortlet();
}

function cableMapAJAX()
{
	$objid = 0;    
	if (isset($_REQUEST['objid']))
		$objid = $_REQUEST['objid'];
	$object = spotEntity ('object', $objid);
	amplifyCell ($object);
	ob_start();
	echo 'Source hostname,Local port,Remote port,Remote hostname'."\n";
	foreach ($object['ports'] as $port)
		if ($port['remote_object_id'])
			echo $object['name'].','.$port['name'].','.$port['remote_name'].','.$port['remote_object_name']."\n";
	$csv = ob_get_clean();

	// write file (and size) to the browser
	header('Content-Type: text/csv; charset=utf-8');
	header(sprintf("Content-Disposition: attachment;filename=\"cablelabels-${object['name']}.csv\""));
	header('Cache-Control: max-age=0');
	header('Content-Length: '.strlen($csv));
	echo $csv;
}

