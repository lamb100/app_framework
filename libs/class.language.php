<?php
class	Language	extends	stdClass
{
	protected	$Lang = '';
	protected	$LangPath = '';
	const	DEFAULT_LANG = 'en_US';
	const	DEFAULT_LANG_PATH = '../lang/en_US';

	public	function	__construct( $language = NULL )
	{
		global	$_APPF;
		if( is_null( $language ) )
		{
			$language = $_APPF["LANG"];
		}
		$this->Lang = $language;
		$this->LangPath = "{$_APPF["DIR_LANG"]}/{$language}";
		if( ! realpath( $this->LangPath ) )
		{
			$this->Lang = self::DEFAULT_LANG;
			$this->LangPath = "{$_APPF["DIR_LANG"]}/{$language}";
		}
		return;
	}

	public	function	&Init( $strClass )
	{
		if( is_object( $strClass ) )
		{
			$strClass = get_class( $strClass );
		}
		if( realpath( "{$this->LangPath}/lang.{$strClass}.php" ) )
		{
			include( "{$this->LangPath}/lang.{$strClass}.php" );
		}elseif( realpath( self::DEFAULT_LANG_PATH . "/lang.{$strClass}.php" ) )
		{
			include( realpath( self::DEFAULT_LANG_PATH . "/lang.{$strClass}.php" ) );
		}else
		{
			throw	new	ErrorException( "No Language Setting" );
		}
		return	$this;
	}
}