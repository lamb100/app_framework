<?php
include( "class.language.php" );

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
	protected	$System = array();
	public	$LastResult = false;
	protected	$DB = array();
	protected	$View = array();
	protected	$_APPF = array();
	protected	$Lang;

	/*Magic Methods*/
	public	function	__sleep(){}
	public	function	__wakeup(){}
	public	function	__call(){}
	public	function	__callStatic(){}
	public	function	__set(){}
	public	function	__get(){}
	public	function	__isset(){}
	public	function	__unset(){}
	public	function	__invoke(){}
	public	function	__set_state(){}
	public	function	__clone(){}
	public	function	__construct()
	{
		$this->BeforeConstruct();
	}
	public	function	__destruct()
	{
		$this->BeforeDestruct();
	}
	public	function	__toString()
	{
		return	__CLASS__;
	}

	protected	function	&BeforeConstruct()
	{

		$this->Lang = new Language( __CLASS__ );
		$this->_APPF = $GLOBALS["_APPF"];
		$this->Session = $_SESSION;
		$this->System = $_SERVER;
		$this->Request = $_REQUEST;
		$this->SetMemTrace()->SetTimeTrace();
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
	 * 開啟/關閉 debuger
	 * @param string $flag 開啟或關閉的旗標;若無指定,則預設是修改前次設定的值;預設是關閉的
	 * @return Core
	 */
	public	function	&TurnOnDebug( $flag = NULL )
	{
		if( is_null( $flag ) )
		{
			$this->Debug["flag"] = ! $this->Debug["flag"];
		}else
		{
			$this->Debug["flag"] = (boolean)$flag;
		}
		return $this;
	}
	/**
	 * 設定時間追蹤
	 * @return Core
	 */
	public	function	&SetTimeTrace()
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
	public	function	&SetMemTrace()
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
			if( preg_match( '/^\{(.+)\}$/' , $aryParams[$intK] , $aryMatches ) )
			{
				$this->Request[$strDefine] = json_decode( $aryMatches[1] , true );

			}else
			{
				$this->Request[$strDefine] = $aryParams[$intK];
			}
		}
		return	$this;
	}

	/**
	 * 生成Framwork所需要的URL
	 * @param string $strModule 模組名稱
	 * @param string $strFunction 功能名稱
	 * @param string $strAction 行為名稱
	 * @param array $aryParams 變數內容
	 * @param boolean $bolAdmin 是否為管理者界面
	 * @param boolean $debug_mode 是否開啟除錯模式
	 * @param array $aryParamDefine	是否帶入自定義的變數定義
	 * @param string $bolSelfUse 是否是給自已使用的變數
	 * @param string $strProtocol 通訊協定
	 * @return string
	 */
	protected	function	GenerateURL( $strModule = NULL , $strFunction = NULL  , $strAction = NULL , $aryParams = array() , $bolAdmin = false , $debug_mode = false , $aryParamDefine = array() , $bolSelfUse = true , $strProtocol = "http://" )
	{
		if( ! $strModule )
		{
			$strModule = $this->_APPF["DEFAULT_MODULE"];
		}
		if( ! $strFunction )
		{
			$strFunction = $this->_APPF["DEFAULT_FUNCTION"];
		}
		if( ! $strAction )
		{
			$strAction = $this->_APPF["DEFAULT_ACTION"];
		}
		if( $debug_mode )
		{
			$aryReturn[] = "debug";
		}
		if( $bolAdmin )
		{
			$aryReturn[] = "admin";
		}
		if( $aryParamDefine == array() )
		{
			$aryParamDefine = $this->ParamsDefine;
		}
		$aryReturn[] = $strModule;
		foreach( $aryParamDefine AS $strK => $strV )
		{
			if( is_array( $aryParams[$strV] ) )
			{
				$strURLJSON = urlencode( json_encode( $aryParams[$strV] ) );
				$aryReturn[] = "\{{$strURLJSON}\}";
			}else
			{
				$strReturn[] = $aryParams[$strV];
			}
		}
		$aryReturn[] = "{$strFunction}.{$strAction}";
		$strReturn = implode( '/' , $aryReturn );
		if( ! $bolSelfUse )
		{
			return	"{$strProtocol}{$_SERVER["HTTP_HOST"]}/{$strReturn}";
		}else
		{
		return	"/$strReturn";
		}
	}

	public	function	Process()
	{
		$debug = (bool)ucfirst( strtolower( str_replace( "/" , "" , $_GET["d"] ) ) );
		$admin = (bool)ucfirst( strtolower( str_replace( "/" , "" , $_GET["a"] ) ) );
		$class = ucfirst( strtolower( $_GET["m"] ) );
		$class_file = "{$this->_APPF["LIB_DIR"]}/class." . strtolower( $_GET["m"] ) . ".php";
		$function = ucfirst( strtolower( $_GET["f"] ) );
		$action = ucfirst( strtolower( $_GET["x"] ) );
		if( $debug )
		{
			$this->TurnOnDebug( true );
		}else
		{
			$this->TurnOnDebug( false );
		}
		$admin = ( $admin ? "Admin" : "" );
		$this->ParseParam();
		$strMethod = "Process{$admin}{$action}{$function}";
		if( file_exists( $class_file ) )
		{
			include( $class_file );
		}else
		{
			$this->SetMsgTrace( $this->Lang->GetLanguage( "NO_CLASS_FILE" ) , $strFile, $intLine)
		}
		#a:admin or not/m:module/f:function/x:action/p:params
		#RewriteRule	^\/((debug)\/)?((admin)\/)?([a-z][_0-9a-z]+)\/(.+)\/([a-z][_0-9a-z]+)\.(view|do|ajax|jsp|csp)$	/index.php?a=$4&m=$5&f=$8&x=$7&p=$6&d=$1	[L,NC,PT,QSA]
	}
}
?>