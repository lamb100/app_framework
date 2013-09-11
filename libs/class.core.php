<?php
include( 'class.pg_ado_connector.php' );
include( 'class.pg_ado_recordset.php' );
include( 'class.smartruct.php' );
include( "{$_APPF["DIR_LANG"]}/{$_APPF["LANG"]}/lang.core.php" );

abstract	class	Core	extends	stdClass
{
	/**
	 * 系統暫存快取
	 * @var	array
	 */
	protected	$Cache = array();
	protected	$Debug = array(
		"sql" => array() ,
		"msg"	=> array() ,
		"timestamp" => array() ,
		"last"	=>	array(
			"sql" => '' ,
			"msg"	=>	'' ,
			"time"	=>	array() ,
			"mem"	=>	0
		) ,
		"start" => array(
			"time"	=>	array() ,
			"mem"	=>	0
		) ,
		"mem"	=>	array() ,
	);
	protected	$ParamsDefine = array();
	protected	$Request = array();


	/*Magic Methods*/
	public	function	__construct()
	{
		$this->BeforeConstruct();
	}
	public	function	__destruct(){}
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

	public	function	GetLang( $strLangCode , $aryReplacePair = array() )
	{
		if( isset( $_LANG[$strLangCode] ) )
		{
			$strReturn = $_LANG[$strLangCode];
			$arySource = $aryTarget = array();
		}else
		{
			return	$strLangCode;
		}

		foreach( $aryReplacePair AS $strSource => $strTarget )
		{
			$arySource[] = "{{$strSource}}";
			$aryTarget[] = $strTarget;
		}
		return	str_replace( $arySource , $aryTarget , $strReturn );
	}

	public	function	Execute()
	{
		$aryParams = func_get_args();
		$strMethod = $aryParams[0];
		unset( $aryParams[0] );
		$strParams = '';
		foreach( $aryParams AS $intK => $null )
		{
			$strParams .= ( $strParams ? " , " : "" ) . '$params[' . $intK . ']';
		}
		$aryMethod = get_class_methods( $this );
		if( ! in_array( $strMethod , $aryMethod ) )
		{
			$aryReplace["METHOD"] = $strMethod;
			$this->SetMsgTrace( $this->GetLang( "NO_METHOD" , $aryReplace ) , __FILE__ , __LINE__ );
		}
		//return	$this->{$strMethod}();
		$strPHP = 'return $this->' . $strMethod . '( ' . $strParams . ' );';
		eval( $strPHP );
	}
	/**
	 * 在建構物件前，先初始化一些元件
	 */
	protected	function	&BeforeConstruct()
	{
		session_start();
		$this->SetTimeTrace()->SetMemTrace();
		$this->Request = $_REQUEST;
		return	$this;
	}
	protected	function	&SetTimeTrace()
	{
		list( $fltNow , $intNow ) = explode( ' ' , microtime() );
		if( ! isset( $this->Debug["start"]["time"]["time"] ) )
		{
			$this->Debug["start"]["time"] = array(
					"microtime" => $fltNow ,
					"time"	=>	$intNow  ,
			);
		}
		$this->Debug["last"]["time"] = array(
			"microtime" => $fltNow ,
			"time"	=>	$intNow  ,
		);
		$this->Debug["timestamp"][] = $this->Debug["last"]["time"] ;
		return	$this;
	}

	protected	function	&SetMemTrace()
	{
		$intMem = memory_get_usage( true );
		$this->Debug["mem"][] = $intMem;
		$this->Debug["last"]["mem"] = $intMem;
		if( ! $this->Debug["start"]["mem"] )
		{
			$this->Debug["start"]["mem"] = $intMem;
		}
		return	$this;
	}

	protected	function	&SetSQLTrace( $strSQL , $strFile , $intLine )
	{
		$strThisTime = microtime();
		$this->Debug["last"]["sql"] = $strSQL;
		$this->Debug["sql"][$strFile][$intLine][$strThisTime] = $strSQL;

		return	$this;
	}

	protected	function	&SetMsgTrace( $strMsg , $strFile , $intLine )
	{
		$strThisTime = microtime();
		$this->Debug["last"]["msg"] = $strMsg;
		$this->Debug["msg"][$strFile][$intLine][$strThisTime] = $strMsg;

		return	$this;
	}

	protected	function	&SetParamDefine( $strDefine , $intIndex = -1 )
	{
		if( (int)$intIndex > -1 )
		{
			$this->ParamsDefine[$intIndex - 0.5] = $strDefine;
		}else
		{
			$this->ParamsDefine[] = $strDefine;
		}
		ksort( $this->ParamsDefine );
		return	$this;
	}

	protected	function	&ParseParam( $aryParamDefine = array() )
	{
		if( ! is_array( $aryParamDefine ) || count( $aryParamDefine ) <= 0 )
		{
			$aryParamDefine = $this->ParamsDefine;
		}
		$aryParam = explode( '/' , $this->Request["p"] );
		foreach( $aryParamDefine AS $intK => $strDefine )
		{
			$this->Request[$strDefine] = $aryParams[$intK];
		}
		return	$this;
	}
}