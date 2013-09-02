<?php
if( ! isset( $_APPF ) )
{
	$_APPF = array();
}
/*Basic*/
$_APPF["DIR_CONF"] = dirname( __FILE__ );
$_APPF["DIR_ROOT"] = dirname( $_APPF["DIR_CONF"] );
$_APPF["DIR_EXEC"] = realpath( "{$_APPF["DIR_ROOT"]}/exec" );
$_APPF["DIR_LANG"] = realpath( "{$_APPF["DIR_ROOT"]}/lang" );
$_APPF["DIR_LIBS"] = realpath( "{$_APPF["DIR_ROOT"]}/libs" );
$_APPF["DIR_MODULE"] = realpath( "{$_APPF["DIR_LIBS"]}/module" );
$_APPF["DIR_CONTROLLER"] = realpath( "{$_APPF["DIR_LIBS"]}/controller" );
$_APPF["DIR_VIEW"] = realpath( "{$_APPF["DIR_LIBS"]}/view" );
$_APPF["DIR_RESOURCE"] = realpath( "{$_APPF["DIR_ROOT"]}/resource" );
$_APPF["DIR_JS"] = realpath( "{$_APPF["DIR_RESOURCE"]}/js" );
$_APPF["DIR_CSS"] = realpath( "{$_APPF["DIR_RESOURCE"]}/css" );
$_APPF["DIR_IMAGE"] = realpath( "{$_APPF["DIR_RESOURCE"]}/image" );
$_APPF["DIR_MEDIA"] = realpath( "{$_APPF["DIR_RESOURCE"]}/media" );
$_APPF["DIR_UPLOAD"] = realpath( "{$_APPF["DIR_RESOURCE"]}/upload" );
$_APPF["DIR_TPL"] = realpath( "{$_APPF["DIR_ROOT"]}/template" );
$_APPF["DIR_CTPL"] = realpath( "{$_APPF["DIR_TPL"]}/.compiled" );
$_APPF["LANG"] = 'zh_TW';
/*DB*/
