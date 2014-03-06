<?php
include( "../conf/set.basic.php" );
include( "{$_APPF["DIR_LIBS"]}/class.pg_ado_connector.php" );
include( "{$_APPF["DIR_LIBS"]}/class.pg_ado_recordset.php" );
include( "{$_APPF["DIR_LIBS"]}/class.smartruct.php" );
include( "{$_APPF["DIR_LIBS"]}/function.php" );
include( "{$_APPF["DIR_LIBS"]}/class.core.php" );

#a:admin or not/m:module/f:function/x:action/p:params
#RewriteRule	^\/(admin\/)?([a-z][_0-9a-z]+)\/(.+)\/([a-z][_0-9a-z]+)\.(view|act|ajax|jsp|csp)$	/index.php?a=$1&m=$2&f=$5&x=$4&p=$3	[L,NC,PT,QSA]
$strAdmin = strtolower( $_GET["a"] );
$strModule = strtolower( $_GET["m"] );
$strFunction = strtolower( $_GET["f"] );
$strAction = strtolower( $_GET["x"] );
$strParams = $_GET["p"];

$aryExplode = explode( '_' , $strModule );
$strModuleClassBase = '';
foreach( $aryExplode AS $strExplode )
{
	$strModuleClassBase .= ucfirst( strtolower( $strExplode ) );
}
$strModule = strtolower( $strModule );

switch( strtolower( $strFunction ) )
{
	case	'view':
	case	'jsp':
	case	'csp':
		$strType = "view";
	break;
	case	'act':
		$strType = "controller";
	break;
}
if( file_exists( "{$_APPF["DIR_LIBS"]}/{$strType}/class.{$strModule}.php" ) )
{
	include( "{$_APPF["DIR_LIBS"]}/{$strType}/class.{$strModule}.php" );
}else
{
	header("HTTP/1.0 460 No Such Module (app_framework)");
	exit;
}
$strClass = $strModuleClassBase;
$objProcess = new $strClass();
$strMethod = ucfirst( $strAdmin ) . ucfirst( $strType ) . ucfirst( $strFunction ) . ucfirst( $strAction );
$objProcess->Execute( $strMethod );
?>