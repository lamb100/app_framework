<?php
$strThisDir = realpath( dirname( __FILE__ ) );
require_once( "function.php" );
/**
 * 預計有的樣版TAG
 * <t:if test="條件">
 * <t:elseif test="">
 * </t:elseif>
 * <t:else>
 * </t:else>
 * </t:if>
 *
 * <t:switch testee="受檢的項目">
 * <t:case tester="相關條件" nobreak="true|false">
 * </t:case>
 * <t:default nobreak="true|false">
 * </t:default>
 * </t:switch>
 *
 * <t:for name="名稱" start="開始值" end="結束值" step="間隔值">
 * <t:break time="跳脫層數"/>
 * <t:continue time="循環層數"/>
 * </t:for>
 *
 * <t:foreach name="名稱" from="要循環取值的陣列" key="鍵值代表陣列" item="鍵值內容值">
 * <t:break time="跳脫層數"/>
 * <t:continue time="循環層數"/>
 * <t:foreachelse>
 * </t:foreachelse>
 * </t:foreach>
 *
 * <t:while name="名稱" test="條件">
 * <t:break time="跳脫層數"/>
 * <t:continue time="循環層數"/>
 * </t:while>
 *
 * <t:do name="名稱" test="條件">
 * <t:break time="跳脫層數"/>
 * <t:continue time="循環層數"/>
 * </t:do>
 *
 * <t:include file="引入樣版的檔案位置" 參數名稱="參數內容"/>
 * <t:include_php file="引入PHP程式名稱" bolOnce="是否是只引入一次"/>
 *
 * <t:lang value=""/>
 * $變數名稱
 * #常數名稱
 * &get/&post/&session/&global/&server/&env:一些預留變數
 * *註解
 * @函數
 * 編譯順序：
 * 1.先取得所有標籤
 * 2.先處理$的變數
 * 3.再處理非$的變數
 * 4.逐一處理標籤
 * 5.處理語系的問題
 */
/**
 * 基礎模組：樣版
 * @author lamb100
 */
class	Smartruct
{
	/**
	 * 樣版所在的基礎路徑
	 * @var	string
	 */
	protected	$strTemplateDir = "";
	/**
	 * 樣版檔案
	 * @var	string
	 */
	protected	$strTemplateFile = "";
	/**
	 * 已編譯的路徑
	 * @var	string
	 */
	protected	$strCompiledDir = "";
	/**
	 * 編譯前的內容
	 * @var	string
	 */
	protected	$strPreparse = "";
	/**
	 * 編譯後的內容
	 * @var	string
	 */
	protected	$strPostparse = "";
	/**
	 * break／continue最多的次數
	 * @var	integer
	 */
	protected	$intMaxBreakTimes = 0;
	/**
	 * 標籤
	 * @var	array
	 */
	protected	$aryTags = array();
	/**
	 * 起頭
	 * @var	array
	 */
	protected	$aryHead = array();
	/**
	 * 前一個CASE是否要break
	 * @var	array
	 */
	protected	$aryCaseNoBreak = array();
	/**
	 * 目前的do所使用的測試值
	 * @var	array
	 */
	protected	$aryNowDoTest = array();
	/**
	 * 堆疊中的陣列名稱
	 * @var	array
	 */
	protected	$aryTPLName = array();
	/**
	 * 樣版內的變數
	 * @var	array
	 */
	protected	$aryTPLVars = array();
	/**
	 * 是否編譯樣版註解
	 * @var	boolean
	 */
	protected	$bolSkipCompileComment = false;

	/*Magic Methods*/
	public	function	__construct(){}
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
		$aryReturn = array(
			"me"	=>	array(
				"module"	=>	"basic" ,
				"class"	=>	__CLASS__
			)
		);
		return	serialize( $aryReturn );
	}
	public	function	__invoke(){}
	public	function	__set_state(){}
	public	function	__clone(){}

	/*public*/
	/**
	 * 預備樣版的相關設定
	 * @param	string	$strTemplateDir	樣版存放的路徑
	 * @param	string	$strCompiledDir	已編譯樣版的存放路徑
	 * @return	void
	 */
	public	function	vidSetPrepare( $strTemplateDir , $strCompiledDir = strConfCompiledTemplateDir )
	{
		try
		{
			if( ! file_exists( $strTemplateDir ) )
			{
				mkdir( $strTemplateDir );
			}
			$this->strTemplateDir = realpath( $strTemplateDir );
			if( ! file_exists( $strCompiledDir ) )
			{
				mkdir( $strCompiledDir );
			}
			$this->strCompiledDir = $strCompiledDir;
			return;
		}catch( ErrorException $objE )
		{
			throw	$objE;
			return;
		}
	}

	/**
	 * 設定樣版的檔案
	 * @param	string	$strTemplateFile	樣版檔案名稱
	 * @return	void
	 */
	public	function	vidSetTemplate( $strTemplateFile )
	{
		if( preg_match( '/(win|dos)/iu' , PHP_OS ) )
		{
			if( ! preg_match( '/^[a-z]\:[\/|\\]/iu' , $strTemplateFile ) )
			{
				$strTemplateFile = realpath( $this->strTemplateDir . "/" . $strTemplateFile );
			}
			$strTemplateFile = str_replace(  "\\" , "/" , $strTemplateFile );
		}else
		{
			if( ! preg_match( '/^\//iu' , $strTemplateFile ) )
			{
				$strTemplateFile = realpath( $this->strTemplateDir . "/" . $strTemplateFile );
			}
			$strTemplateFile = str_replace(  "\\" , "/" , $strTemplateFile );
		}
		$this->strPreparse = file_get_contents( $strTemplateFile );
		$this->strTemplateFile = $strTemplateFile;
		return;
	}

	/**
	 * 取得樣版名稱
	 * @return	string
	 */
	public	function	strGetTemplateName()
	{
		return	$this->strTemplateFile;
	}

	/**
	 * 取得樣版內容(編譯前的內容)
	 * @return	true
	 */
	public	function	strGetTemplateContent()
	{
		return	$this->strPreparse;
	}

	/**
	 * 指派樣版內的變數
	 * @param	mixed	$mixNameOrVars	<ul>
	 * <li>當不指派第二個變數時，該變數為一個陣列，裡頭為樣版變數名稱，和所賦予值的對應陣列</li>
	 * <li>當指派第二個變數時，該變數為一字串，為樣版變數的名稱</li></ul>
	 * @param string	$strValues	賦予$mixNameOrVars所指定的樣版變數名稱的值
	 * @return	void
	 */
	public	function	vidSetAssign( $mixNameOrVars , $strValues = NULL )
	{
		if( is_array( $mixNameOrVars ) )
		{
			foreach( $mixNameOrVars AS $strName => $mixValue )
			{
				$this->aryTPLVars[$strName] = $mixValue;
			}
			return;
		}
		$this->aryTPLVars[$mixNameOrVars] = $strValues;
		return;
	}

	/**
	 * 增添樣版內的變數<br/>＃如果要修改樣版變數的內容，則請利用vidSetAssign
	 * @param	mixed	$mixNameOrVars	<ul>
	 * <li>當不指派第二個變數時，為一個陣列，裡頭為樣版變數名稱，和所賦予值的對應陣列</li>
	 * <li>當指派第二個變數時，為一字串，為樣版變數的名稱</li></ul>
	 * @param string	$strValues	賦予$mixNameOrVars所指定的樣版變數名稱的值
	 * @return	void
	 */
	public	function	vidSetAppend( $mixNameOrVars , $strValues = NULL )
	{
		if( is_array( $mixNameOrVars ) )
		{
			foreach( $mixNameOrVars AS $strName => $mixValue )
			{
				if( is_array( $this->aryTPLVars[$strName] ) )
				{
					if( is_array( $mixValue ) )
					{
						foreach( $mixValue AS $mixK => $mixV )
						{
							if( isset( $this->aryTPLVars[$strName][$mixK] ) )
							{
								if( is_array( $this->aryTPLVars[$strName][$mixK] ) )
								{
									$this->aryTPLVars[$strName][$mixK][] = $mixV;
								}else
								{
									if( is_array( $mixV ) )
									{
										$mixV[] = $this->aryTPLVars[$strName][$mixK];
										$this->aryTPLVars[$strName][$mixK] = $mixV;
									}else
									{
										$this->aryTPLVars[$strName][$mixK] .= $mixV;
									}
								}
							}
							else
							{
								$this->aryTPLVars[$strName][$mixK] = $mixV;
							}
						}
					}else
					{
						$this->aryTPLVars[$strName] .= $mixValue;
					}
				}
			}
			return;
		}
		if( is_array( $this->aryTPLVars[$mixNameOrVars] ) )
		{
			if( is_array( $strValues ) )
			{
				$this->aryTPLVars[$mixNameOrVars] = array_merge( $this->aryTPLVars , $strValues );
			}else
			{
				$this->aryTPLVars[$mixNameOrVars][] = $strValues;
			}
			return;
		}
		if( is_array( $strValues ) )
		{
			$strValues[] = $this->aryTPLVars[$mixNameOrVars];
			$this->aryTPLVars[$mixNameOrVars] = $strValues;
			return;
		}
		$this->aryTPLVars[$mixNameOrVars] .= $strValues;
		return;
	}

	/**
	 * 解析樣版
	 * @param	boolean	$bolEcho	是否要直接吐出成網頁
	 * @throws	ErrorException
	 * @return	string	解析的結果
	 */
	public	function	strGetParse( $bolEcho = false )
	{
		if( ! $this->strTemplateFile )
		{
			throw new	ErrorException( "Before we parse the template, you should set the template file." , __CLASS__ . ":15" , 0 ,  __FILE__ ,  __LINE__ );
			return	false;
		}
		$__TPL_VARS = $this->aryTPLVars;
		$strCompiledFileName = md5( $this->strTemplateFile );
		if( file_exists( "{$this->strCompiledDir}/{$strCompiledFileName}.php" ) )
		{
			ob_start();
			require( "{$this->strCompiledDir}/{$strCompiledFileName}.php" );
			$strReturn = ob_get_clean();
			if( $__TPL_PROPERTY["strChecksum"] === sha1( $this->strPreparse ) )
			{
				$this->strPostparse = $strReturn;
				if( $bolEcho )
				{
					echo $strReturn;
				}
				return	$strReturn;
			}
			unset( $strReturn );
		}
		//開始解析，編譯樣版
		$resFile = fopen( "{$this->strCompiledDir}/{$strCompiledFileName}.php" , "w" );
		$aryNewCode[] = '<?php';
		$aryNewCode[] = '$__TPL_PROPERTY["strChecksum"] = "' . sha1( $this->strPreparse ) . '";';
		$aryNewCode[] = '$__TPL_PROPERTY["strLangSrc"] = $GLOBALS["lang"]["setting"]["default"];';
		$aryPreparsed = $this->aryGetAllTags( $this->strPreparse );
		$aryPostparse = array();
		//把解析出來的TAG進入編譯
		foreach( $aryPreparsed AS $strTag )
		{
			$strTag = $this->strSetParseVariable( $strTag );	//處理樣版變數
			$strTag = $this->strSetParseComment( $strTag );		//處理註解
			$strTag = $this->strSetParseLanguage( $strTag );	//處理語言
			$strTag = $this->strSetParseTags( $strTag );		//處理其他標籤
			$aryPostparse[] = $strTag;
		}
		$aryNewCode[] = implode( "\n" , $aryPostparse );
		$aryNewCode[] = '?>';
		fwrite( $resFile , $strReturn = implode( "\n" , $aryNewCode ) );
/** 編譯順序：
 * 2.先處理$的變數
 * 3.再處理非$的變數
 * 4.逐一處理標籤
 * 5.處理語系的問題*/
		fclose( $resFile );
		if( $bolEcho )
		{
			echo $strReturn;
		}
		$resFile = fopen( $strCacheFileName , "w" );
		fwrite( $resFile , $strReturn );
		fclose( $resFile );
		$this->strPostparse = $strReturn;
		return	$this->strPostparse;
	}

	/*Protected*/
	/**
	 * 將指定文字內容中所有標籤取出來
	 * @param	string	$strWholeContent	全文的內容
	 * @return	array
	 */
	protected	function	aryGetAllTags( $strWholeContent )
	{
		$strPatternStart =
		'/\<t\:((if|elseif|else|switch|case|default|for|while|do)\s+((([a-z]+)\=(\"|' . "'" . ')(\.+?)\6)*)\s+)\>/imu';
		$strPatternEnd =
		'/\<\/t\:(*|if|elseif|else|switch|case|default|for|while|do)\s+\>/imu';
		$strPatternMiddle =
		'/\<t\:((\@|\#|\$|\/\/|break|continue|include|include_php|lang)\s+((([a-z]+)\=(\"|' . "'" . ')(\.+?)\6)*)\s+)\/\>/imu';
		$aryParsed = array();
		preg_match_all( $strPatternStart , $strWholeContent , $aryParsedTemp );
		$aryParsed = array_merge( $aryParsed , $aryParsedTemp );
		preg_match_all( $strPatternEnd , $strWholeContent , $aryParsedTemp );
		$aryParsed = array_merge( $aryParsed , $aryParsedTemp );
		preg_match_all( $strPatternMiddle , $strWholeContent , $aryParsedTemp );
		$aryParsed = array_merge( $aryParsed , $aryParsedTemp );
		$aryAssign = array();
		foreach( $aryParsed[0] AS $strTags )
		{
			$aryAssign[strlen( $strTags )][] = $strTags;
		}
		krsort( $aryAssign );
		$this->aryTags = $aryAssign;
		return	$aryAssign;
	}
	/**
	 * 解析樣版文字內的變數
	 * @param	string	$strSignleContent	樣版文字的字樣
	 * @return	string	解析的結果
	 */
	protected	function	strSetParseVariable( &$strSignleContent )
	{
		//處理常數：#
		$strSignleContent = preg_replace( '/#([0-9a-z\_]+)/iu' , '\1' , $strSignleContent );
		//處理函數：@
		$strSignleContent = preg_replace( '/\@([0-9a-z_]+)\(((.+?)?(\,(.+?))*)\)/iu' , '$1( $2 )' , $strSignleContent );
		//
		//處理$
		//先抓出所有的$
		preg_match_all( '/\$([0-9a-z_]+)(((\.|\-\>)([0-9A-Z_]+))*)/iu' , $strSignleContent , $aryMatches );
		$aryReplace = array();
		$aryFind = array();
		//逐一解譯每個符合的變數
		foreach( $aryMatches[0] AS $intIndex => $strMatch )
		{
			$aryRecode = array();
			//處理物件及陣列(->：物件/.：陣列)
			if( preg_match_all( '/(\.|\-\>)([0-9A-Z_]+)/iu' , $aryMatches[2][$intIndex] , $aryInnerMatches ) )
			{
				foreach( $aryInnerMatches[0] AS $intInnerIndex => $strInnerMatch )
				{
					if( $aryInnerMatches[1][$intInnerIndex] == '->' )
					{
						$aryRecode[] = $aryInnerMatches[1][$intInnerIndex] . $aryInnerMatches[2][$intInnerIndex];
					}elseif( $aryInnerMatches[1][$intInnerIndex] == '.' )
					{
						$aryRecode[] = "['" . $aryInnerMatches[2][$intInnerIndex] . "']";
					}
				}
			}
			$strRecode = implode( '' , $aryRecode );
			//處理變數名稱(保留字property)
			if( strtolower( $aryMatches[1][$intIndex] ) == "property" )
			{
				$aryReplace[strlen($aryMatches[0][$intIndex])][] = '$__TPL_PROPERTY' . $strRecode;
			}
			else
			{
				$aryReplace[strlen($aryMatches[0][$intIndex])][] = '$__TPL_VARS["' . $aryMatches[1][$intIndex] . '"]' . $strRecode;
			}
			$aryFind[strlen($aryMatches[0][$intIndex])][] = $aryMatches[0][$intIndex];
		}
		krsort( $aryReplace );
		krsort( $aryFind );
		foreach( $aryFind AS $intIndex => $aryTemp )
		{
			$strSignleContent = str_replace( $aryFind[$intIndex] , $aryReplace[$intIndex] , $strSignleContent );
		}
		//處理PHP預留全域變數：&
		//&get/&post/&session/&global/&server/&env/&request:一些預留變數
		preg_match_all( '/\&(get|post|session|global|server|env|cookie|request)(((\.|\-\>)([0-9A-Z_]+))*)/iu' , $strSignleContent , $aryMatches );
		$aryReplace = array();
		$aryFind = array();
		foreach( $aryMatches[0] AS $intIndex => $strMatch )
		{
			$aryRecode = array();
			if( preg_match_all( '/(\.|\-\>)([0-9A-Z_]+)/iu' , $aryMatches[2][$intIndex] , $aryInnerMatches ) )
			{
				foreach( $aryInnerMatches[0] AS $intInnerIndex => $strInnerMatch )
				{
					if( $aryInnerMatches[1][$intInnerIndex] == '->' )
					{
						$aryRecode[] = $aryInnerMatches[1][$intInnerIndex] . $aryInnerMatches[2][$intInnerIndex];
					}elseif( $aryInnerMatches[1][$intInnerIndex] == '.' )
					{
						$aryRecode[] = "['" . $aryInnerMatches[2][$intInnerIndex] . "']";
					}
				}
			}
			$strRecode = implode( '' , $aryRecode );
			if( $aryMatches[1][$intIndex] != "global" )
			{
				$aryReplace[strlen($aryMatches[0][$intIndex])][] = '$GLOBALS' . $strRecode;
			}else
			{
				$aryReplace[strlen($aryMatches[0][$intIndex])][] = '$_' . strtoupper( $aryMatches[1][$intIndex] ) . $strRecode;
			}
			$aryFind[strlen($aryMatches[0][$intIndex])][] = $aryMatches[0][$intIndex];
		}
		krsort( $aryReplace );
		krsort( $aryFind );
		foreach( $aryFind AS $intIndex => $aryTemp )
		{
			$strSignleContent = str_replace( $aryFind[$intIndex] , $aryReplace[$intIndex] , $strSignleContent );
		}
		return	$strSignleContent;
	}
	/**
	 * 解析樣版文字
	 * @param	string	$strSingleContent	樣版內容
	 * @return	string	解譯的結果
	 * @throws	ErrorException
	 */
	protected	function	strSetParseTags( &$strSingleContent )
	{
		/*$strPatternStart =
		'/\<t\:((if|elseif|else|switch|case|default|for|while|do)\s+((([a-z]+)\=(\"|' . "'" . ')(\.+?)\6)*)\s+)\>/imu';
		$strPatternEnd =
		'/\<\/t\:(*|if|elseif|else|switch|case|default|for|while|do)\s+\>/imu';
		$strPatternMiddle =
		'/\<t\:((\@|\#|\$|\/\/|break|continue|include|include_php|lang)\s+((([a-z]+)\=(\"|' . "'" . ')(\.+?)\6)*)\s+)\/\>/imu';*/
		$aryNewCode = array();
		switch( true )
		{
			//if群
			//<t:if test="">
			case	preg_match( '/^\<t\:if\s+test\=(\"|' . "'" . ')?(.+?)\1\s*\>$/imu' , $strSingleContent , $aryMatches ):
				$this->aryHead[] = "if";
				$strSingleContent =	"if( {$aryMatches[2]} )\n{";
			break;
			//</t:if>
			case	preg_match( '/^\<\/t\:if\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "if" )
					{
						throw new	ErrorException( "</t:if> should has the head, <t:if>." , __CLASS__ . ":1" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$strSingleContent =	'}';
					}
				}else
				{
					throw new	ErrorException( "</t:if> should has the head, <t:if>." , __CLASS__ . ":1" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//<t:elseif test="">
			case	preg_match( '/^\<t\:elseif\s+test\=(\"|' . "'" . ')?(.+?)\1\s*\>$/imu' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "if" )
					{
						throw new	ErrorException( "<t:elseif> should be under the tag , <t:if>." , __CLASS__ . ":2" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$this->aryHead[] = "elseif";
						$strSingleContent =	"elseif( {$aryMatches[2]} )\n{";
					}
				}else
				{
					throw new	ErrorException( "<t:elseif> should be under the tag , <t:if>." , __CLASS__ . ":2" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//</t:elseif>
			case	preg_match( '/^\<\/t\:elseif\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "elseif" )
					{
						throw new	ErrorException( "</t:elseif> should has the head, <t:elseif>." , __CLASS__ . ":3" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$strSingleContent =	'}';
					}
				}else
				{
					throw new	ErrorException( "</t:elseif> should has the head, <t:elseif>." , __CLASS__ . ":3" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//<t:else>
			case	preg_match( '/^\<t\:else\>$/imu' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "if" )
					{
						throw new	ErrorException( "</t:else> should be under the tag, <t:if>." , __CLASS__ . ":4" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$this->aryHead[] = "else";
						$strSingleContent =	'else{';
					}
				}else
				{
					throw new	ErrorException( "</t:else> should be under the tag, <t:if>." , __CLASS__ . ":4" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//</t:else>
			case	preg_match( '/^\<\/t\:else\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "else" )
					{
						throw new	ErrorException( "</t:else> should has the head, <t:else>." , __CLASS__ . ":5" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$strSingleContent =	'}';
					}
				}else
				{
					throw new	ErrorException( "</t:else> should has the head, <t:else>." , __CLASS__ . ":5" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//switch群
			//<t:switch testee="">
			case	preg_match( '/\<t\:switch\s+testee\=(\"|' . "'" . ')?(.+?)\1\s*\>/imu' , $strSingleContent , $aryMatches ):
				$strSingleContent = "switch( {$aryMatches[2]} )\n{";
				$this->aryHead[] = "switch";
			break;
			//</t:switch>
			case	preg_match( '/^\<\/t\:switch\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "switch" )
					{
						throw new	ErrorException( "</t:switch> should has the head, <t:switch>." , __CLASS__ . ":6" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$strSingleContent =	'}';
					}
				}else
				{
					throw new	ErrorException( "</t:switch> should has the head, <t:switch>." , __CLASS__ . ":6" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//<t:case tester="" nobreak="">
			case	preg_match( '/\<t\:case\s+((tester|nobreak)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\>/imu' ) :
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "switch" )
					{
						throw new	ErrorException( "<t:case> should be under the tag, <t:switch>." , __CLASS__ . ":7" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						//拆解屬性
						preg_match_all( '/(tester|nobreak)\=(\"|' . "'" . ')?(.+?)\2/imu' , $aryMatches[1] , $aryMatches1 );
						$aryParams = array();
						foreach( $aryMatches1[0] AS $intKey => $mixValue )
						{
							$aryParams[strtolower($aryMatches1[1][$intKey])] = $aryMatches1[3][$intKey];
						}
						$aryParams["nobreak"] = ( $aryParams["nobreak"] === "" ? true : $aryParams["nobreak"] );
						$aryParams["nobreak"] = (boolean)$aryParams["nobreak"];
						$this->aryCaseNoBreak[] = $aryParams["nobreak"];
						$this->aryHead[] = "case";
						$strSingleContent = "case {$aryParams["tester"]} :";
					}
				}else
				{
					throw new	ErrorException( "<t:case> should be under the tag, <t:switch>." , __CLASS__ . ":7" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//</t:case>
			case	preg_match( '/^\<\/t\:case\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "case" )
					{
						throw new	ErrorException( "</t:case> should has the head, <t:case>." , __CLASS__ . ":8" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$bolCaseNoBreak = mixGetArrayLast( $this->aryCaseNoBreak );
						if( ! $bolCaseNoBreak )
						{
							$strSingleContent =	'break;';
						}
						array_pop( $this->aryCaseNoBreak );
					}
				}else
				{
					throw new	ErrorException( "</t:case> should has the head, <t:case>." , __CLASS__ . ":8" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//<t:default nobreak="">
			case	preg_match( '/\<t\:default\s+((nobreak)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\>/imu' ) :
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "switch" )
					{
						throw new	ErrorException( "<t:default> should be under the tag, <t:switch>." , __CLASS__ . ":9" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						//拆解屬性
						preg_match_all( '/(nobreak)\=(\"|' . "'" . ')?(.+?)\2/imu' , $aryMatches[1] , $aryMatches1 );
						$aryParams = array();
						foreach( $aryMatches1[0] AS $intKey => $mixValue )
						{
							$aryParams[strtolower($aryMatches1[1][$intKey])] = $aryMatches1[3][$intKey];
						}
						$aryParams["nobreak"] = ( $aryParams["nobreak"] === "" ? true : $aryParams["nobreak"] );
						$aryParams["nobreak"] = (boolean)$aryParams["nobreak"];
						$this->aryCaseNoBreak[] = $aryParams["nobreak"];
						$this->aryHead[] = "default";
						$strSingleContent = "default :";
					}
				}else
				{
					throw new	ErrorException( "<t:default> should be under the tag, <t:switch>." , __CLASS__ . ":9" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//</t:default>
			case	preg_match( '/^\<\/t\:default\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "default" )
					{
						throw new	ErrorException( "</t:default> should has the head, <t:default>." , __CLASS__ . ":10" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						array_pop( $this->aryHead );
						$bolCaseNoBreak = mixGetArrayLast( $this->aryCaseNoBreak );
						if( ! $bolCaseNoBreak )
						{
							$strSingleContent =	'break;';
						}
						array_pop( $this->aryCaseNoBreak );
					}
				}else
				{
					throw new	ErrorException( "</t:default> should has the head, <t:default>." , __CLASS__ . ":10" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//for群
			//<t:for name="" start="" end="" step="">
			case	preg_match( '/^\<t\:for\s+((name|start|end|step)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(name|start|end|step)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = '$__TPL_VARS = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"] = array();';
				$aryNewCode[] =
					'for( $__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] = ' . $aryParams["start"] . ' ; ' .
					' $__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] ' . ( $aryParams["step"] > 0 ? ">" : "<" ) . '= ' . $aryParams["end"] . ' ; ' .
					' $__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] = $__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] + ( ' . $aryParams["step"] . ' ) )\n{';
				$this->aryHead[] = "for";
				$this->aryTPLName[] = $aryParams["name"];
				$this->intMaxBreakTimes++;
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;
			//</t:for>
			case	preg_match( '/^\<\/t\:for\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "for" )
					{
						throw new	ErrorException( "</t:for> should has the head, <t:for>." , __CLASS__ . ":11" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$aryNewCode[] = "}";
						$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
						array_pop( $this->aryHead );
						array_pop( $this->aryTPLName );
					}
					$strSingleContent = implode( "\n" , $aryNewCode );
				}else
				{
					throw new	ErrorException( "</t:for> should has the head, <t:for>." , __CLASS__ . ":11" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;
			//<t:break times=""/><t:continue times=""/>
			case	preg_match( '/^\<t\:(break|continue)\s+((times)\=(\"|' . "'" . ')?(.+?)\4\s+)\s*\/\>$/imu' , $strSingleContent , $aryMatches ):
				$aryBreakable = array( "for" , "foreach" , "while" , "do" );
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( ! in_array ( $strAnswer , $aryBreakable ) )
					{
						throw new	ErrorException( "<t:break> or <t:continue> should be under those tags as fallow, <t:for>,<t:foreach>,<t:while> and <t:do>." , __CLASS__ . ":12" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						//拆解屬性
						preg_match_all( '/(times)\=(\"|' . "'" . ')?(.+?)\2/imu' , $aryMatches[1] , $aryMatches1 );
						$aryParams = array();
						foreach( $aryMatches1[0] AS $intKey => $mixValue )
						{
							$aryParams[strtolower($aryMatches1[1][$intKey])] = $aryMatches1[3][$intKey];
						}
						if( ! isset( $aryParams["times"] ) )
						{
							$aryParams["times"] = 1;
						}
						$aryNewCode[] = 'for( $intAc = 0 ; $intAc < ' . (int)( strtolower( $aryMatches[1] ) == "break" ? $aryParams["times"] : ( $aryParams["times"] - 1 ) ) . ' ; $intAc++ )\n{';
						$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
						$aryNewCode[] = '}';
						$aryNewCode[] = $aryMatches[1] . " " . (int)$aryParams["times"] . ";";
					}
				}else
				{
					throw new	ErrorException( "<t:break> or <t:continue> should be under those tags as fallow, <t:for>,<t:foreach>,<t:while> and <t:do>." , __CLASS__ . ":12" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//todo	while群
			//<t:while name="" test="">
			case	preg_match( '/^\<t\:while\s+((name|test)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(test|name)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = '$__TPL_VARS = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"] = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] = 0;';
				$aryNewCode[] = 'while( ' . $aryParams["test"] . ' ){';
				$this->aryHead[] = "while";
				$this->aryTPLName[] = $aryParams["name"];
				$this->intMaxBreakTimes++;
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;
			//</t:while>
			case	preg_match( '/^\<\/t\:while\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "while" )
					{
						throw new	ErrorException( "</t:while> should has the head, <t:while>." , __CLASS__ . ":13" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$strName = mixGetArrayLast( $this->aryTPLName );
						$aryNewCode[] = '$__TPL_PROPERTY["' . $strName . '"]["intIndex"]++;';
						$aryNewCode[] = "}";
						$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
						array_pop( $this->aryHead );
						array_pop( $this->aryTPLName );
					}
					$strSingleContent = implode( "\n" , $aryNewCode );
				}else
				{
					throw new	ErrorException( "</t:while> should has the head, <t:while>." , __CLASS__ . ":13" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//foreach群
			//<t:foreach name="" from="" key="" item="">
			case	preg_match( '/^\<t\:foreach\s+((name|from|key|item)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(name|from|key|item)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = '
				if( isset( $__TPL_FROM ) )
				{
					$__TEMP_FROM[] = $__TPL_FROM;
				}
				';
				$aryNewCode[] = '$__TPL_FROM = ' . $aryParams["from"];
				$aryNewCode[] = '$__TPL_VARS = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"] = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] = 0;';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["intTotal"] = count( $__TPL_FROM );';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["aryKey"] = array_keys( $_TPL_FROM );';
				$aryNewCode[] = 'foreach( $__TPL_FROM AS ' . ( $aryParams["key"] ? '$__TPL_VARS["' . $aryParams["key"] . '"] =>' : '' ) . '$__TPL_VARS["' . $aryParams["item"] . '"] ){';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["bolFirst"] = ( $__TPL_PROPERTY["' . $aryParams["name"] . '"]["aryKey"][0] == $__TPL_VARS["' . $aryParams["item"] . '"] );';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["bolLast"] = ( $__TPL_PROPERTY["' . $aryParams["name"] . '"]["aryKey"][count($__TPL_PROPERTY["' . $aryParams["name"] . '"]["aryKey"])-1] == $__TPL_VARS["' . $aryParams["item"] . '"] );';
				$this->aryHead[] = "foreach";
				$this->aryTPLName[] = $aryParams["name"];
				$this->intMaxBreakTimes++;
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;
			//</t:foreach>
			case	preg_match( '/^\<\/t\:foreach\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "foreach" )
					{
						throw new	ErrorException( "</t:foreach> should has the head, <t:foreach>." , __CLASS__ . ":14" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$strName = mixGetArrayLast( $this->aryTPLName );
						$aryNewCode[] = '$__TPL_PROPERTY["' . $strName . '"]["intIndex"]++;';
						$aryNewCode[] = "}";
						$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
						$aryNewCode[] = '
						if( count( $__TEMP_FROM ) > 0 )
						{
							$__TPL_FROM = array_pop( $__TEMP_FROM );
						}
						';
						array_pop( $this->aryHead );
						array_pop( $this->aryTPLName );
					}
					$strSingleContent = implode( "\n" , $aryNewCode );
				}else
				{
					throw new	ErrorException( "</t:foreach> should has the head, <t:foreach>." , __CLASS__ . ":14" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//do群
			//<t:do name="" test="">
			case	preg_match( '/^\<t\:do\s+((name|test)\=(\"|' . "'" . ')(\"|' . "'" . ')?(.+?)\3\s+)\s*\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(test|name)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = '$__TPL_VARS = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"] = array();';
				$aryNewCode[] = '$__TPL_PROPERTY["' . $aryParams["name"] . '"]["intIndex"] = 0;';
				//預儲條件式
				$this->aryNowDoTest[] = $aryParams["test"];
				$aryNewCode[] = 'do{';
				$this->aryHead[] = "do";
				$this->aryTPLName[] = $aryParams["name"];
				$this->intMaxBreakTimes++;
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;
			//</t:do>
			case	preg_match( '/^\<\/t\:do\>$/ium' , $strSingleContent , $aryMatches ):
				if( ! is_null( $strAnswer = mixGetArrayLast( $this->aryHead )  ) )
				{
					if( $strAnswer !== "do" )
					{
						throw new	ErrorException( "</t:do> should has the head, <t:do>." , __CLASS__ . ":14" , 0 ,  __FILE__ ,  __LINE__ );
						$strSingleContent =	'';
					}else
					{
						$strName = mixGetArrayLast( $this->aryTPLName );
						$strTest = mixGetArrayLast( $this->aryNowDoTest );
						$aryNewCode[] = '$__TPL_PROPERTY["' . $strName . '"]["intIndex"]++;';
						$aryNewCode[] = "}while( " . $strTest . " );";
						$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
						array_pop( $this->aryHead );
						array_pop( $this->aryTPLName );
						array_pop( $this->aryNowDoTest );
					}
					$strSingleContent = implode( "\n" , $aryNewCode );
				}else
				{
					throw new	ErrorException( "</t:do> should has the head, <t:do>." , __CLASS__ . ":14" , 0 ,  __FILE__ ,  __LINE__ );
					$strSingleContent =	'';
				}
			break;

			//include群
			//<t:include file="" assign="" ...other../>
			case	preg_match( '/^\<t\:include\s+((file|assign|[0-9a-z\_]+)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\/\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(file|assign|[0-9a-z\_]+)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = 'if( is_array( ' . $aryParams["assign"] . ' ) )
				{
					foreach( ' . $aryParams["assign"] . ' AS $strK => $mixV )
					{
						$__TPL_VARS[$strK] = $mixV;
					}
				}
				';
				foreach( $aryParams AS $strK => $mixV )
				{
					if( in_array( $strK , array( "file" , "assign" ) ) )
					{
						continue;
					}
					$aryNewCode[] = '$__TPL_VARS["' . $strK . '"] = unserialize( "' . addslashes( serialize( $mixV ) ) . '" );';
				}
				$objTemplate = clone $this;
				$objTemplate->vidSetTemplate( $aryParams["file"] );
				//只做編譯，不產生編譯結果
				$objTemplate->strGetParse( false );
				$strFileName = strConfCompiledTemplateDir . "/" . md5( $objTemplate->strGetTemplateName() ) . ".php";
				$aryNewCode[] = 'require( "' . $strFileName . '" );';
				$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;
			//<t:include_php file="" assign="" ...other../>
			case	preg_match( '/^\<t\:include_php\s+((file|assign|[0-9a-z\_]+)\=(\"|' . "'" . ')?(.+?)\3\s+)\s*\/\>$/imu' , $strSingleContent , $aryMatches ):
				preg_match_all( '/(file|assign|[0-9a-z\_]+)\=(\"|' . "'" . ')?(.+?)\2/ium' , $aryMatches[1] , $aryMatches1 );
				$aryParams = array();
				foreach( $aryMatches1[0] AS $intK => $mixV )
				{
					$aryParams[$aryMatches1[1][$intK]] = $aryMatches1[3][$intK];
				}
				$aryNewCode[] = '$__TEMP_VARS[] = $__TPL_VARS;';
				$aryNewCode[] = 'if( is_array( ' . $aryParams["assign"] . ' ) )
				{
					foreach( ' . $aryParams["assign"] . ' AS $strK => $mixV )
					{
						$__TPL_VARS[$strK] = $mixV;
					}
				}
				';
				foreach( $aryParams AS $strK => $mixV )
				{
					if( in_array( $strK , array( "file" , "assign" ) ) )
					{
						continue;
					}
					$aryNewCode[] = '$__TPL_VARS["' . $strK . '"] = unserialize( "' . serialize( $mixV ) . '" );';
				}
				if( preg_match( '/(win|dos)/iu' , PHP_OS ) )
				{
					if( ! preg_match( '/^[a-z]\:[\/|\\]/iu' , $aryParams["file"] ) )
					{
						$aryParams["file"] = realpath( $this->strTemplateDir . "/" . $aryParams["file"] );
					}
					$aryParams["file"] = str_replace(  "\\" , "/" , $aryParams["file"] );
				}else
				{
					if( ! preg_match( '/^\//iu' , $aryParams["file"] ) )
					{
						$aryParams["file"] = realpath( $this->strTemplateDir . "/" . $aryParams["file"] );
					}
					$aryParams["file"] = str_replace(  "\\" , "/" , $aryParams["file"] );
				}
				$aryNewCode[] = 'require( "' . $aryParams["file"] . '" );';
				$aryNewCode[] = '$__TPL_VARS = array_pop( $__TEMP_VARS );';
				$strSingleContent = implode( "\n" , $aryNewCode );
			break;

			case	preg_match( '/^\<t:(.+?)\/\>$/imu' , $strSingleContent , $aryMatches ):
				$strSingleContent = 'echo ' . $aryMatches[1] . ';';
			break;
			default:
				return	$strSingleContent;
			break;
		}
		$strSingleContent = "<?php\n" . $strSingleContent . "\n?>";
		return	$strSingleContent;
	}
	/**
	 * 解析註解
	 * @param	string	$strSingleContent	要用來解析前的內容
	 * @param	boolean	$bolCompiled	是否要忽略編譯註解
	 * @return	string
	 */
	protected	function	strSetParseComment( &$strSingleContent , $bolCompiled = NULL )
	{
		if( is_null( $bolCompiled ) )
		{
			$bolCompiled = $this->bolSkipCompileComment;
		}
		if( $bolCompiled )
		{
			$strSingleContent = preg_replace( '/\<t:\/\/(.*?)\/\>/iu' , '<?php\n//$1\n?>' , $strSingleContent );
			$strSingleContent = preg_replace( '/\<t:\*.*?\>(.*?)</t:\*\>/imu' , '<?php\n/*$1*/\n?>' , $strSingleContent );
		}else
		{
			$strSingleContent = preg_replace( '/\<t:\/\/(.*?)\/\>/iu' , '' , $strSingleContent );
			$strSingleContent = preg_replace( '/\<t:\*.*?\>(.*?)</t:\*\>/imu' , '' , $strSingleContent );
		}
		return	$strSingleContent;
	}
	/**
	 * 解譯語言標籤
	 * @param	string	$strSingleContent	要解譯的內容
	 * @return	string	要解譯的結果
	 */
	protected	function	strSetParseLanguage( &$strSingleContent )
	{
		//取得標籤裡記載文字來源的屬性
		if( preg_match( '/\<t\:lang\s+value\=(\"|' . "'" . ')?(.+?)\1\s*\/\>/imu' , $strSingleContent , $aryRegs ) )
		{
			$aryNewCode[] = '<?php';
			$aryNewCode[] = 'echo $GLOBALS["lang"][$_GET["lang"]]["' . $aryRegs[2] . '"];';
			$aryNewCode[] = '?>';
			return	implode( " " , $aryNewCode );
		}else
		{
			return $strSingleContent;
		}
	}

	/**
	 * 製作麵包屑
	 * @param	$aryData
	 * @return	string
	 */
	static	public	function	strGetBreadCrumb( $aryData )
	{
		$strReturn = "";
		if( ! is_array( $aryData ) )
		{
			$aryPreReturn = array();
			foreach( $aryData AS $aryItem )
			{
				if( $aryItem["strURI"] )
				{
					$aryPreReturn[] = "<a href=\"{$aryItem["strURI"]}\">{$aryItem["strText"]}</a>";
				}else
				{
					$aryPreReturn[] = $aryItem["strText"];
				}
			}
		}
		$strReturn = implode( "&raquo;" , $aryPreReturn );
		return	$strReturn;
		//RSBlues &raquo; <a href="http://www.free-css-layouts.com/bz99wxw.php?go=wxw" class="active">Home</a> &raquo;
	}
}
?>