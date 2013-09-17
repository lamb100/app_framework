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

	protected	function	&BeforeConstruct()
	{
		parent::BeforeConstruct();
		$this->LastResult = true;
		return	$this;
	}

	protected	function	&SetUserBasic( $arySet = array() )
	{
		$aryNow = aryGetNow();
		if( ! isset( $arySet["vch_usb_id"] ) )
		{
			$this->SetMsgTrace( $this->GetLang( "NO_USER_ID" ) , __FILE__ , __LINE__ );
			$this->LastResult = false;
			return	$this;
		}
		$strSQL = "SELECT chr_usb_pass FROM tb_user_basic WHERE vch_usb_id = " . $this->DB->strGetQuote( $arySet["vch_usb_id"] );
		$aryRow = $this->DB->aryGetRow( 0 );
		$bolInsert = true;
		if( $aryRow )
		{
			$strPass = $aryRow["chr_usb_pass"];
			if( $strPass != strGetCrypt( $arySet["chr_usb_pass"] , $strPass ) )
			{
				$this->SetMsgTrace( $this->GetLang( "WRONG_PASS" ) , __FILE__ , __LINE__ );
				$this->LastResult = false;
				return	$this;
			}
			$bolInsert = false;
		}
		$aryNewSet["vch_usb_id"] = $this->DB->strGetQuote( $arySet["vch_usb_id"] );
		$aryNewSet["chr_usb_pass"] = $this->DB->strGetQuote( strGetCrypt( $arySet["chr_usb_pass"] ) );
		$aryNewSet["num_usb_last"] = "{$aryNow["unixtimestamp"]} + {$aryRow["microsec"]}";
		if( $bolInsert )
		{
			$aryNewSet["num_usb_first"] = "{$aryNow["unixtimestamp"]} + {$aryRow["microsec"]}";
			$strSQL = 'INSERT INTO tb_user_basic (' . implode( ',' , array_keys( $aryNewSet ) ) . ') VALUES (' . implode( ',' , array_values( $aryNewSet ) ) . ')';
		}else
		{
			$aryValuePair = array();
			foreach( $aryNewSet AS $strField => $strValue )
			{
				$aryValuePair[] = "{$strField}={$strValue}";
			}
			$strSQL = "UPDATE tb_user_basic SET " . implode( "," , $aryValuePair ) . " WHERE vch_usb_id = " . $aryNewSet["vch_usb_id"];
		}
		try
		{
			if( $this->DB->bolSetExecute( $strSQL ) )
			{
				$this->LastResult = true;
			}else
			{
				$this->LastResult = false;
			}

		}catch( ErrorException $objE )
		{
			$this->LastResult = false;
			$this->SetMsgTrace( $objE->getMessage() , $objE->getFile() , $objE->getLine() );
		}
		return	$this;
	}

	protected	function	GetUserBasic( $mixQuery = NULL )
	{
		$strQuery = "";
		if( is_array( $mixQuery ) )
		{
			$aryFields = $this->DB->aryGetFieldsName( "tb_user_basic" );
		}else if( preg_match( '/^\s+\s+$/i' ) )
		{
			
		}
	}
}