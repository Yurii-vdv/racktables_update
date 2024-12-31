<?php
/* Import rack Racktables drawracks v.0.2
// List a type of objects in a table and allow to export them via
new plugin format
# Functions added
#   plugin_drawracks_info
#   plugin_drawracks_init
#   plugin_drawracks_install
#   plugin_drawracks_uninstall
#   plugin_drawracks_upgrade
# Uninstall previous version first (the code does not handle it) before using this one !!!
# TODO: use the install/upgrade function to remove previous version
# TODO: check if the add functions are still consistent with 0.21 functions

*/
if( file_exists( dirname(__FILE__) . '/drawRacksConfig.php' ) )
	require_once dirname(__FILE__) . '/drawRacksConfig.php';
	require_once dirname(__FILE__) . '/drawRacksLib.php';

function plugin_drawracks_info ()
{               
        return array
        (       
                'name' => 'drawracks',
                'longname' => 'Import Rack', 
                'version' => '2.0',
                'home_url' => 'https://github.com/Yurii-vdv'
        );          
}  

function plugin_drawracks_init ()
{	// Build Navigation
	global $page, $tab;
	//$page['reports']['title'] = 'Import Rack';
	$tab['reports']['rack'] = 'DrawRacks';		// The title of the report tab
	$tabhandler['reports']['rack'] = 'renderDrawRacks';	// register a report rendering function

	registerTabHandler('reports', 'rack', 'renderDrawRacks');
        
}
function plugin_drawracks_install()
{
	return TRUE;
}
function plugin_drawracks_uninstall()
{
	return TRUE;
}
function plugin_drawracks_upgrade ()
{
        return TRUE;
}
function renderDrawRacks()
{
	global $drawracks_conf;
	$rp = new DrawRacks();
	if ( isset($_GET['xlsx']) ){
		if ( strlen($_COOKIE['rack_ids']) > 0) {
			$rp->output_excelfile();
			exit(0);
		}
		else
		{
			showWarning ("None of the racks is selected.");
		}
	}

	// Handle the location filter
	//startSession();
	@session_start();
	if (isset ($_REQUEST['changeLocationFilter']))
		unset ($_SESSION['locationFilter']);
	if (isset ($_REQUEST['location_id']))
		$_SESSION['locationFilter'] = $_REQUEST['location_id'];
	session_commit();
	$rp->output_form();	
}

/*
function plugin_drawracks_upgrade ()
{
	$db_info = getPlugin ('drawracks');
	$v1 = $db_info['db_version'];
	$code_info = plugin_plugin_info ();
	$v2 = $code_info['version'];
	
	if ($v1 == $v2)
		throw new RackTablesError ('Versions are identical',RackTablesError::INTERNAL);

	// find the upgrade path to be taken
	$versionhistory = array
	(
		'0.1',
		'0.2'
	);
	$skip = TRUE;
	$path = NULL;
	foreach ($versionhistory as $v)
	{
		if ($skip and $v == $v1)
		{
			$skip = FALSE;
			$path = array();
			continue;
		}
		if ($skip)
			continue;
		$path[] = $v;
		if ($v == $v2)
			break;
	}
	if ($path === NULL or ! count ($path))
		throw new RackTablesError ('Unable to determine upgrade path', RackTablesError::INTERNAL);

	// build the list of queries to execute
	$queries = array ();
	foreach ($path as $batchid)
	{
		switch ($batchid)
		{
			case '2.0':
				// perform some upgrade step here
				$queries[] = "UPDATE Plugin SET version = '2.0' WHERE name = 'myplugin'";
				break;
			case '3.0':
				// perform some upgrade step here
				$queries[] = "UPDATE Plugin SET version = '3.0' WHERE name = 'myplugin'";
				break;
			default:
				throw new RackTablesError ("Preparing to upgrade to $batchid failed", RackTablesError::INTERNAL);
		}
	}

	// execute the queries
	global $dbxlink;
	foreach ($queries as $q)
	{
		try
		{
			$result = $dbxlink->query ($q);
		}
		catch (PDOException $e)
		{
			$errorInfo = $dbxlink->errorInfo();
			throw new RackTablesError ("Query: ${errorInfo[2]}", RackTablesError::INTERNAL);
		}
	}
}*/
?>
