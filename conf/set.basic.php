<?php
if( ! isset( $_APPF ) )
{
	$_APPF = array();
}
//The default setting
/*Basic*/
$_APPF["DIR_CONF"] = dirname( __FILE__ );
$_APPF["DIR_ROOT"] = dirname( $_APPF["DIR_CONF"] );
$_APPF["DIR_EXEC"] = realpath( "{$_APPF["DIR_ROOT"]}/exec" );
$_APPF["DIR_LANG"] = realpath( "{$_APPF["DIR_ROOT"]}/lang" );
$_APPF["DIR_LIBS"] = realpath( "{$_APPF["DIR_ROOT"]}/libs" );
$_APPF["DIR_MODULE"] = realpath( "{$_APPF["DIR_LIBS"]}/module" );
$_APPF["DIR_CONTROLLER"] = realpath( "{$_APPF["DIR_LIBS"]}/controller" );
$_APPF["DIR_VIEW"] = realpath( "{$_APPF["DIR_LIBS"]}/view" );
$_APPF["DIR_RESOURCE"] = realpath( "{$_APPF["DIR_ROOT"]}/res" );
$_APPF["DIR_JS"] = realpath( "{$_APPF["DIR_RESOURCE"]}/js" );
$_APPF["DIR_CSS"] = realpath( "{$_APPF["DIR_RESOURCE"]}/css" );
$_APPF["DIR_IMAGE"] = realpath( "{$_APPF["DIR_RESOURCE"]}/image" );
$_APPF["DIR_MEDIA"] = realpath( "{$_APPF["DIR_RESOURCE"]}/media" );
$_APPF["DIR_UPLOAD"] = realpath( "{$_APPF["DIR_RESOURCE"]}/upload" );
$_APPF["WEB_ROOT"] = '/';
$_APPF["WEB_JS"] = ( "{$_APPF["WEB_ROOT"]}/js" );
$_APPF["WEB_CSS"] = ( "{$_APPF["WEB_ROOT"]}/css" );
$_APPF["WEB_IMAGE"] = ( "{$_APPF["WEB_ROOT"]}/image" );
$_APPF["WEB_MEDIA"] = ( "{$_APPF["WEB_ROOT"]}/media" );
$_APPF["WEB_UPLOAD"] = ( "{$_APPF["WEB_ROOT"]}/upload" );
$_APPF["DIR_TPL"] = realpath( "{$_APPF["DIR_ROOT"]}/template" );
$_APPF["DIR_CTPL"] = realpath( "{$_APPF["DIR_TPL"]}/.compiled" );
$_APPF["LANG"] = 'zh_TW';
/*DB*/
$_APPF["DB_HOST"] = "localhost";
$_APPF["DB_USER"] = "appf";
$_APPF["DB_PASS"] = "appf@postgresql";
$_APPF["DB_NAME"] = "appf";
$_APPF["DB_PORT"] = 54321;

/*If there are other setting for this website, you can set as above in the file named by the host name.*/
if( isset( $_SERVER["HTTP_HOST"] ) )
{
	if( file_exists( "set.{$_SERVER["HTTP_HOST"]}.php" ) )
	{
		include( "set.{$_SERVER["HTTP_HOST"]}.php" );
	}
}