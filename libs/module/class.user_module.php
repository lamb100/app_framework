<?php
include( "../class.pg_ado_connector.php" );
include( "../class.pg_ado_recordset.php" );
include( "{$_APPF["DIR_LANG"]}/{$_APPF["LANG"]}/lang.user.php" );

define( "USER_DB_MAX_READ_ROWS" , 1000 );

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
		$this->InitDB();
		return	$this;
	}
	/**
	 * 設定tb_user_basic
	 * @param unknown $arySet
	 * @return UserModule
	 */
	protected	function	&SetUserBasic( $arySet = array() )
	{
		$aryNow = aryGetNow();
		if( ! isset( $arySet["vch_usb_id"] ) )
		{
			$this->SetMsgTrace( $this->GetLang( "NO_USER_ID" ) , __FILE__ , __LINE__ );
			$this->LastResult = false;
			return	$this;
		}
		$strSQL = "SELECT chr_usb_pass FROM tb_user_basic WHERE vch_usb_id = " . $this->ExecuteDB( "strGetQuote" , $arySet["vch_usb_id"] );
		$aryRow = $this->ExecuteDB( "aryGetRow" ,  0 );
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
		$aryNewSet["vch_usb_id"] = $this->ExecuteDB( "strGetQuote" ,  $arySet["vch_usb_id"] );
		$aryNewSet["chr_usb_pass"] = $this->ExecuteDB( "strGetQuote" ,  strGetCrypt( $arySet["chr_usb_pass"] ) );
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
			if( $this->ExecDB( "bolSetExecute" , $strSQL ) )
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

	protected	function	GetUserBasic( $mixFields = NULL , $mixQuery = NULL , $bolGetSQL = false )
	{
		//拚湊WHERE的子句{
		$strQuery = "";
		if( is_array( $mixQuery ) )
		{
			$aryFields = $this->ExecDB( "aryGetFieldsName" , "tb_user_basic" );
			foreach( $mixQuery AS $mixK => $strV )
			{
				if( in_array( $mixK , $aryFields ) )
				{
					switch( $mixK )
					{
						case	"chr_usb_sha1":
						case	"vch_usb_id":
							$strQuery .= ( $strQuery ? " AND " : "" ) . "( \"{$mixK}\" = " . $this->ExecuteDB( "strGetQuote" , $strV ) . " )";
						break;
						default:
							$strQuery .= ( $strQuery ? " AND " : "" ) . "( \"{$mixK}\" = " . $strV . " )";
						break;
					}
				}else if( preg_match( '/(\>|\<|\=|\!)/i' , $strV ) )
				{
					$strQuery .= ( $strQuery ? " AND " : "" ) . "( " . $strV . " )";
				}
			}
		}else if( preg_match( '/(\>|\<|\=|\!)/i' , $mixQuery ) )
		{
			$strQuery = $mixQuery;
		}
		//}
		$strSQL = "SELECT * FROM tb_user_basic WHERE " . ( $strQuery ? $strQuery : " true " );
		if( $bolGetSQL )
		{
			return	$strSQL;
		}
		try
		{
			$objRS = $this->ExecuteDB( "objSetExecute" , $strSQL );
		}catch( ErrorException $objE )
		{
			$this->SetMsgTrace( $this->GetLang( "ERROR_FOR_SQL_EXECUTE" , array( "REASON" => $objE->getMessage() ) ) , $objE->getFile() , $objE->getLine() );
			throw	$objE;
		}
		$aryRows = array();
		$intCount = 0;
		while( ! $objRS->bolEOF )
		{
			$aryRows[] = $objRS->aryGetFetchRow();
			$objRS->bolSetMoveNext();
			$intCount++;
			if( $intCount > USER_DB_MAX_READ_ROWS )
			{
				break;
			}
		}
		return	$aryRows;
	}
}