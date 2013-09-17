<?php
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
		"flag"	=>	false ,
	);
	protected	$ParamsDefine = array();
	protected	$Request = array();
	protected	$Session = array();
	public	$LastResult = false;
	protected	$DB = array();
	protected	$View = array();


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

	/**
	 * 取得語言翻譯
	 * @param	string	$strLangCode	語言碼
	 * @param	array	$aryReplacePair	語言中要取代的文字
	 * @return	string
	 */
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

	/**
	 * 執行特定的方法
	 * @param	string	$strMethod	要執行的方法名稱
	 * @param	mixed	$mixParam1	第一個變數(2,3,4依序類推)
	 * @return	mixed	方法執行的結果
	 */
	public	function	Execute()
	{
		$strClass = get_class( $this );
		if( preg_match( '/module$/i' , $strClass ) )
		{
			$this->SetMsgTrace(  $this->GetLang( "NO_USE_FOR_MODULE" ) , __FILE__ , __LINE__ );
			return	false;
		}
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
		$strPHP = 'return $this->Exec' . $strMethod . '( ' . $strParams . ' );';
		eval( $strPHP );
	}

	/**
	 * 開啟／關閉除錯器
	 * @param	boolean	$bolOn	設定除錯器的開啟或關閉	Default:NULL(原先是開啟，則關閉；原先關閉，則開啟)
	 * @return Core
	 */
	public	function	&TurnDebug( $bolOn = NULL )
	{
		if( is_null( $bolOn ) )
		{
			$this->Debug["flag"] = ! $this->Debug["flag"];
		}else
		{
			$this->Debug["flag"] = (bool)$bolOn;
		}
		return	$this;
	}
	/**
	 * 在完成建構前成立
	 * @return Core
	 */
	protected	function	&BeforeConstruct()
	{
		session_start();
		$this->SetTimeTrace()->SetMemTrace();
		$this->Request = $_REQUEST;
		$this->Session = $_SESSION;
		return	$this;
	}
	protected	function	&BeforeDestruct()
	{
		if( $this->Debug["flag"] )
		{
			echo '<pre>' . print_r( $this->Debug , true ) . '</pre>';
		}
		return	$this;
	}
	/**
	 * 設定時間追蹤
	 * @return Core
	 */
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

	/**
	 * 設定記憶體追蹤
	 * @return Core
	 */
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

	/**
	 * 設定SQL指令的追蹤
	 * @param	string	$strSQL	SQL指令
	 * @param string	$strFile	執行SQL時所在的檔案(建議使用__FILE__)
	 * @param	integer	$intLine	執行SQL時，記錄所在的行數(建議使用__LINE__)
	 * @return Core
	 */
	protected	function	&SetSQLTrace( $strSQL , $strFile , $intLine )
	{
		$strThisTime = microtime();
		$this->Debug["last"]["sql"] = $strSQL;
		$this->Debug["sql"][$strFile][$intLine][$strThisTime] = $strSQL;

		return	$this;
	}
	/**
	 * 設定信息的追蹤
	 * @param	string	$strMsg	信息內容
	 * @param string	$strFile	留下信息時所在的檔案(建議使用__FILE__)
	 * @param	integer	$intLine	留下信息時所在的行數(建議使用__LINE__)
	 * @return Core
	 */
	protected	function	&SetMsgTrace( $strMsg , $strFile , $intLine )
	{
		$strThisTime = microtime();
		$this->Debug["last"]["msg"] = $strMsg;
		$this->Debug["msg"][$strFile][$intLine][$strThisTime] = $strMsg;

		return	$this;
	}

	/**
	 * 定義變數引入的位置所代表的變數意義
	 * @param	string	$strDefine	定義的變數名稱
	 * @param	integer	$intIndex	變數所在的順序	Default:-1
	 * @return Core
	 */
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

	/**
	 * 解譯變數(將解譯結果傳至Core::Request中)
	 * @param	array	$aryParamDefine	變數定義陣列	Default:array()
	 * @return Core
	 */
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