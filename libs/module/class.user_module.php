<?php
include( "../class.pg_ado_connector.php" );
include( "../class.pg_ado_recordset.php" );
include( "{$_APPF["DIR_LANG"]}/{$_APPF["LANG"]}/lang.user.php" );

class	UserModule	extends	Core
{
	/*Magic Methods*/
	public	function	__construct()
	{
		$this->BeforeConstruct();
	}
	public	function	__destruct()
	{
		$this->BeforeDestruct();
	}
	public	function	__sleep(){}
	public	function	__wakeup(){}
	public	function	__call(){}
	public	function	__callStatic(){}
	public	function	__set(){}
	public	function	__get(){}
	public	function	__isset(){}
	public	function	__unset(){}
	public	function	__toString()
	{
		return	__CLASS__;
	}
	public	function	__invoke(){}
	public	function	__set_state(){}
	public	function	__clone(){}

	protected	function	BeforeConstruct()
	{
		parent::BeforeConstruct();
		$this->Process["db"] = new PGADOConnector();
		$this->Process["db"]->bolSetConnect( $_APPF["DB_HOST"] , $_APPF["DB_USER"] , $_APPF["DB_PASS"] , $_APPF["DB_NAME"] , $_APPF["DB_PORT"] );
	}

	protected	function	SetUserBasic( $arySet = array() )
	{
		if( ! isset( $arySet["vch_usb_id"] ) )
		{
			$this->SetMsgTrace( $this->GetLang( "NO_USER_ID" ) , __FILE__ , __LINE__ );
			return	$this;
		}
		$strSQL = "SELECT chr_usb_pass FROM tb_user_basic WHERE vch_usb_id = " . $this->Process["db"]->strGetQuote( $arySet["vch_usb_id"] );
		$aryRow = $this->Process["db"]->aryGetRow( 0 );
		if( $aryRow )
		{

		}
	}
}