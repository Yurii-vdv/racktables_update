<?php
//
// Network ping plugin.
// Version 0.4
//
// Written by Tommy Botten Jensen
// patched for Racktables 0.20.3 by Vladimir Kushnir
// patched for Racktables 0.20.5 by Rob Walker
// patched for Racktables 0.22.0 by
// The purpose of this plugin is to map your IP ranges with the reality of your
// network using ICMP.
//
// History
// Version 0.1:  Initial release
// Version 0.2:  Added capability for racktables 0.20.3
// Version 0.3:  Fix to use ip instead of ip_bin when calling ipaddress page
// Version 0.4:  add different ping types 'single', 'curl' and 'local' (see $pingtype)
//
// Requirements:
// You need 'fping' from your local repo or http://fping.sourceforge.net/
// Racktables must be hosted on a system that has ICMP PING access to your hosts
//
// Installation:
// 1)  Copy script to plugins folder as ping.php
// 2)  Install fping if you did not read the "requirements"
// 3)  Adjust the $pingtimeout value below to match your network.


// TODO make scriptable to run via cron

/* ipaddress page */
$tab['ipaddress']['ping'] = 'Ping Log';
$tabhandler['ipaddress']['ping'] = 'ping_ipaddressPingLogTab';


function ping_ipaddressPingLogTab($ip_bin)
{

	ping_preparedatabase();
	$ip = ip4_bin2int ($ip_bin);
	$straddr = ip4_format ($ip_bin);
echo $ip;
	$result = usePreparedSelectBlade("select * from IPv4PingLog where ip = ? order by date desc", array($ip));

	$rows = $result->fetchAll(PDO::FETCH_ASSOC);

	// ping_removeold()?

	startPortlet("Ping Log");

	echo '<table class="widetable" cellspacing="0" cellpadding="5" align="center" width="50%">';
	echo "<th></th><th>Date</th><th>Result</th>";

	$odd = FALSE;
	foreach($rows as $row)
	{
		$tr_class = $odd ? 'row_odd' : 'row_even';
                echo "<tr class='$tr_class'>";
		$date = $row['date'];

		echo "<td>".ip4_format(ip4_int2bin($row['ip']))."</td><td bgcolor=".ping_logcolor($date).">$date</td><td>".$row['result']."</td></tr>";
		$odd = !$odd;
	}

	echo "</table>";
	finishPortlet();
	return;

	if(0)
	{
	echo "<pre>";
	//var_dump($rows);
	echo "</pre>";
	}
}

function ping_logcolor($date)
{
	$max_age = 3600 * 24;

	$age = date('U', time() - strtotime($date));

	$step = 255 / $max_age;

	$r = intval(($step * 2) * $age);
	$g = (255 * 2) - $r;

	if($r > 255) $r = 255;
	if($r < 0) $r = 0;
	if($g > 255) $g = 255;
	if($g < 0) $g = 0;

	return sprintf("#%02x%02x%02x",$r,$g,0);
}

function ping_preparedatabase()
{
	$result = usePreparedSelectBlade('SHOW TABLES LIKE "IPv4PingLog"');

	$row = $result->fetch();

	if($row)
		return;

	$query = "CREATE TABLE `IPv4PingLog` (
			`id` int(10) NOT NULL AUTO_INCREMENT,
			`ip` int(10) unsigned NOT NULL DEFAULT '0',
			`date` datetime NOT NULL DEFAULT NOW(),
			`result` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	$result = usePreparedSelectBlade($query);

	if($result)
		showSuccess("Created table IPv4PingLog");
}

function ping_Log($ip, $text)
{

	//return lastlogentry ?
	$lastrow = ping_getlastlog($ip);

	$columns = array('ip' => $ip, 'result' => $text );

	$result = usePreparedInsertBlade("IPv4PingLog", $columns);

	return $lastrow;
}

function ping_getlastlog($ip)
{
	$result = usePreparedSelectBlade("select * from IPv4PingLog where ip = ? order by date desc limit 1", array($ip));
	$row = $result->fetch(PDO::FETCH_ASSOC);

	if($row)
		return $row;
	else
		return array('result' => false, 'date' => NULL);
}

function ping_removeold()
{
	// only keep n Log entries


}

// Depot Tab for objects.
$tab['ipv4net']['ping'] = 'Ping overview';
$tabhandler['ipv4net']['ping'] = 'PingTab';
$ophandler['ipv4net']['ping']['importPingData'] = 'importPingData';
$ophandler['ipv4net']['ping']['executePing'] = 'ping_executePing';


function importPingData() {
 // Stub connection for now :(
}

/*
 * pingtype
 *	'local' : uses fping -g to ping whole network ~4 secs for /24 and pingtimeout = 500
 *	'curl' : uses curl to parallelize ping ~7 secs (depends mostly on web server perfromace) for /24 and pingtimeout = 500
 *	'single' : uses fping ping each ip after another ~116 secs for /24 and pingtimeout = 500
 */
$pingtype = 'local';
$pingtimeout = "500";

$fping_cmd = '/usr/bin/fping';

/*
 * used to ping one ip address
 * callied by curl requests
 */
function ping_executePing()
{
	global $pingtimeout, $fping_cmd;
	if(!isset($_GET['ip']))
	{
		echo "Missing ip!";
		exit;
	}

	$straddr = $_GET['ip'];

	$starttime = microtime(true);

	$cmdretval = false;
//echo "$fping_cmd -q -c 1 -t $pingtimeout $straddr";
	system("$fping_cmd -q -c 1 -t $pingtimeout $straddr",$cmdretval);

	$stoptime = microtime(true);

	$pingreply = ($cmdretval == 0 ? true : false); // fping success returns 0 (false) !!
	echo json_encode(array( 'ip' => $straddr, 'pingreply' => $pingreply, 'time' => ($stoptime - $starttime), 'start' => $starttime));

	exit;
}

/*
 * fping whole network
 * 	uses fpings -g option
 */
function ping_localfping($net)
{
	global $pingtimeout, $fping_cmd;

	$output = array();
	$retval = false;
	$cmd = "$fping_cmd -q -C 1 -i 10 -t $pingtimeout -g $net 2>&1";

	$starttime = microtime(true);
	exec($cmd,$output, $retval);
	$stoptime = microtime(true);

	$runtime = $stoptime - $starttime;

	$results = array('runtime' => $runtime);

	$idx = 0;
	foreach($output as $line)
	{
		list($ipaddr, $time) = explode(':',$line);

		$ipaddr = trim($ipaddr);
		$time = trim($time);

		if($time == "-")
			$time = false;

		$idx++;
		$results[$ipaddr] = array('pingreply' => $time , 'time' => ($time ? $time : $runtime), 'start' => $starttime);
	}

	$results['idx'] = $idx;
	$results['runtime'] = $runtime;

	return $results;
}

/*
 * makes curl request for ervery ip
 *	uses ping_executePing();
 * execution time depends on web server performance ( parallel requests )
 */
function ping_curlfping($startip, $endip)
{
	global $pingtimeout;

	// curl request options
	$curl_opts = array(
			CURLOPT_HEADER => 0,
			CURLOPT_COOKIE => $_SERVER['HTTP_COOKIE'],
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true
			);

	$results = array();
	$ch = array();

	$starttime = microtime(true);

	$cmh = curl_multi_init();
//	curl_multi_setopt($cmh, CURLMOPT_MAXCONNECTS, 10);
//	curl_multi_setopt($cmh, CURLMOPT_PIPELINING, 0);

	$url = ($_SERVER['HTTPS']  == 'on' ? 'https://' : 'http://' ).$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'&module=redirect&op=executePing&ip=';

	// max parallel requests
	$max_requests = 50;
	$active = null;

	$idx = 0;

	for ($ip = $startip; $ip <= $endip; $ip++)
	{

		// curl ..
		$ip_bin = ip4_int2bin($ip);
		$straddr = ip4_format ($ip_bin);
		$ch[$straddr] = curl_init();
		curl_setopt_array($ch[$straddr], $curl_opts);
		curl_setopt($ch[$straddr], CURLOPT_URL, $url.$straddr);

		curl_multi_add_handle($cmh,$ch[$straddr]);

		$idx++;

		if($idx >= $max_requests)
		do
		{

			curl_multi_exec($cmh, $active);
			if(curl_multi_select($cmh))
			{

				$reqinfo = curl_multi_info_read($cmh);
				for(;$reqinfo;)
				{
					if($reqinfo['result'] == CURLE_OK)
					{
						// finished request
						$info = curl_getinfo($reqinfo['handle']);
						if($info['http_code'] == 200)
						{
							$content = curl_multi_getcontent($reqinfo['handle']);
							$json = json_decode($content, true);
							$results[$json['ip']] = $json;
							$results[$json['ip']]['info'] = $info;
						}
						else
							echo "NOT 200<br>";

						if($ip<$endip)
						{
							// curl ..
							$ip++;
							$ip_bin = ip4_int2bin($ip);
							$straddr = ip4_format ($ip_bin);
							$ch[$straddr] = curl_init();
							curl_setopt_array($ch[$straddr], $curl_opts);
							curl_setopt($ch[$straddr], CURLOPT_URL, $url.$straddr);

							curl_multi_add_handle($cmh,$ch[$straddr]);

							$idx++;
							curl_multi_exec($cmh, $active);
						}

						curl_multi_remove_handle($cmh,$reqinfo['handle']);
						$reqinfo = curl_multi_info_read($cmh);
					}
				}
			}
		} while($active >= $max_requests || (($ip >= $endip) && ($active > 0)) );

	}
	//echo "END-->$active<br>";
	curl_multi_close($cmh);

	$stoptime = microtime(true);

	$results['idx'] = $idx;
	$results['runtime'] = $stoptime - $starttime;

	return $results;

}

// Display the ping overview:
function PingTab($id) {

	global $pingtimeout, $pingtype;

	if(!isset($_POST['pingnow']))
		$pingtype = 'log';


	$debug = false; // output timing informations

	if (isset($_REQUEST['pg']))
		$page = $_REQUEST['pg'];
	else
		$page=0;
	global $pageno, $tabno;
	$maxperpage = getConfigVar ('IPV4_ADDRS_PER_PAGE');
	$range = spotEntity ('ipv4net', $id);
	loadIPAddrList ($range);

	echo "<center><h1>${range['ip']}/${range['mask']}</h1><h2>${range['name']}</h2></center>\n";

	echo "<table class=objview border=0 width='100%'><tr><td class=pcleft>";
	startPortlet ('icmp ping comparrison:');
	$target = makeHref($_GET);
	echo '<form method="POST" action="'.$target.'"><input type="submit" value="Ping Now" name="pingnow"></form></p>';

	$startip = ip4_bin2int ($range['ip_bin']);
	$endip = ip4_bin2int (ip_last ($range));
	$realstartip = $startip;
	$realendip = $endip;
	$numpages = 0;
	if ($endip - $startip > $maxperpage)
	{
		$numpages = ($endip - $startip) / $maxperpage;
		$startip = $startip + $page * $maxperpage;
		$endip = $startip + $maxperpage - 1;
	}
	echo "<center>";
	if ($numpages)
		echo '<h3>' . ip4_format (ip4_int2bin ($startip)) . ' ~ ' . ip4_format (ip4_int2bin ($endip)) . '</h3>';
	for ($i=0; $i<$numpages; $i++)
		if ($i == $page)
			echo "<b>$i</b> ";
		else
			echo "<a href='".makeHref(array('page'=>$pageno, 'tab'=>$tabno, 'id'=>$id, 'pg'=>$i))."'>$i</a> ";
	echo "</center>";

	echo "<table class='widetable' border=0 cellspacing=0 cellpadding=5 align='center'>\n";
	echo "<tr><th>address</th><th>name</th><th>response</th>";
	echo "<th>last log date</th><th>last log response</th></tr>";
	$box_counter = 1;
	$cnt_ok = $cnt_noreply = $cnt_mismatch = 0;
	$start_totaltime = microtime(true);

	$results = array();

	switch($pingtype)
	{
		case 'local':
			echo "using local ping ".date("d.m.Y H:i:s");
			$results = ping_localfping($range['ip']."/".$range['mask']);
			break;
		case 'curl':
			echo "using curl ping ".date("d.m.Y H:i:s");
			$results = ping_curlfping($startip, $endip);
			break;
		case 'single':
			echo "using single ping ".date("d.m.Y H:i:s");
			$start_runtime = microtime(true);
			$idx = 0;
			// singel
			for ($ip = $startip; $ip <= $endip; $ip++)
			{
				$idx++;
				$ip_bin = ip4_int2bin($ip);
				$straddr = ip4_format ($ip_bin);
				echo $straddr."";
				$starttime = microtime(true);
				$cmdretval = Null;
				system("/usr/bin/fping -q -c 1 -t $pingtimeout $straddr",$cmdretval);
				
				$stoptime = microtime(true);
				$pingreply = ($cmdretval == 0 ? true : false); // fping success returns 0 (false) !!
				$results[$straddr] = array( 'pingreply' => $pingreply, 'time' => $stoptime - $starttime, 'start' => $starttime);
			}
			$stop_runtime = microtime(true);
			$results['idx'] = $idx;
			$results['runtime'] = $stop_runtime - $start_runtime;
		default:
			// get last ping log
			echo "/usr/bin/fping -q -c 1 -t $pingtimeout $straddr";
			$start_runtime = microtime(true);
			$idx = 0;
			for ($ip = $startip; $ip <= $endip; $ip++)
			{
				$idx++;
				$ip_bin = ip4_int2bin($ip);
				$straddr = ip4_format ($ip_bin);
				$results[$straddr] = ping_getlastlog($ip);
				$results[$straddr]['pingreply'] = ($results[$straddr]['result'] == 'no response' ? false : true);
			//echo $results[$straddr]['result'];
			}
			$stop_runtime = microtime(true);
			$results['idx'] = $idx;
			$results['runtime'] = $stop_runtime - $start_runtime;

	}

	$idx = $results['idx'];
//var_dump($php);
	// print results
	for ($ip = $startip; $ip <= $endip; $ip++)
	{
		$ip_bin = ip4_int2bin($ip);
		$straddr = ip4_format ($ip_bin);
		$addr = isset ($range['addrlist'][$ip_bin]) ? $range['addrlist'][$ip_bin] : array ('name' => '', 'reserved' => 'no');

		if(!isset($results[$straddr]))
			$results[$straddr] = array('pingreply' => false, 'time' => "-", 'start' => '-');

		$result = $results[$straddr];

		if($pingtype == 'curl')
			if($result['info']['http_code'] != 200)
			{
				echo "$addr: HTTP Response: ".$result['info']['http_code']."<br>";
				continue;
			}

		$pingreply = $result['pingreply'];

		// FIXME: This is a huge and ugly IF/ELSE block. Prettify anyone?
		if ($pingreply) {
		
			if ( (!empty($addr['name']) and ($addr['reserved'] == 'no')) or (!empty($addr['allocs']))) {
				echo '<tr class=trok';
				$cnt_ok++;
			}
			else {
				echo ( $addr['reserved'] == 'yes' ) ? '<tr class=trwarning':'<tr class=trerror';
				$cnt_mismatch++;
			}
		}
		else {
			if ( (!empty($addr['name']) and ($addr['reserved'] == 'no')) or !empty($addr['allocs']) ) {
				echo '<tr class=trwarning';
				$cnt_noreply++;
			}
			else {
				echo '<tr';
			}
		}
		echo "><td class='tdleft";
		if (isset ($range['addrlist'][$ip_bin]['class']) and strlen ($range['addrlist'][$ip_bin]['class']))
			echo ' ' . $range['addrlist'][$ip_bin]['class'];
		echo "'><a href='".makeHref(array('page'=>'ipaddress', 'ip'=>$straddr))."'>${straddr}</a></td>";
		echo "<td class=tdleft>${addr['name']}</td><td class=tderror>";
		if($pingtype == 'log')
			echo "-";
		else
		{
			if ($pingreply)
				echo "Yes";
			else
				echo "No";
		}

		if($debug)
		{
			echo "</td><td>".$result['time']."111</td>";
			echo "<td>".$result['start'];
			if($pingtype == 'curl')
				echo "</td><td>".$result['info']['total_time'];
		}
 
		// update PingLog
		if($pingtype != 'log')
		{
			$lastlog = ping_Log($ip, ($pingreply ? "OK (".$result['time'].")" : "no response"));

			if($lastlog)
				echo '<td bgcolor="'.ping_logcolor($lastlog['date']).'">'.$lastlog['date']."</td><td>".$lastlog['result'];
		}
		else
			echo '<td bgcolor="'.ping_logcolor($result['date']).'">'.$result['date']."</td><td>".$result['result'];

		echo "</td></tr>\n";
	}

	echo "</td></tr>";
	echo "</table>";
	echo "</form>";
	finishPortlet();

	echo "</td><td class=pcright>";

	startPortlet ('stats');
	echo "<table border=0 width='100%' cellspacing=0 cellpadding=2>";
	echo "<tr class=trok><th class=tdright>OKs:</th><td class=tdleft>${cnt_ok}</td></tr>\n";
	echo "<tr class=trwarning><th class=tdright>Did not reply:</th><td class=tdleft>${cnt_noreply}</td></tr>\n";
	if ($cnt_mismatch)
		echo "<tr class=trerror><th class=tdright>Unallocated answer:</th><td class=tdleft>${cnt_mismatch}</td></tr>\n";
	echo "</table>\n";
	finishPortlet();

	echo "</td></tr></table>\n";

	if($debug)
	{
		$stop_totaltime = microtime(true);
		echo "Total Ping Run Time: ".$results['runtime']."<br>";
		echo "Total Page Time: ".($stop_totaltime - $start_totaltime)."<br>";
	}

}

?>

