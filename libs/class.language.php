<?php
class	Language	extends	stdClass
{
	protected	$Language = "";
	protected	$Path = "";
	protected	$Class = "";
	protected	$_APPF = "";
	protected	$Content = array();
	private	$LeftTerminal = '{';
	private	$RightTerminal = '}';
	const	DEFAULT_LANG = 'en_US';
	const	DEFAULT_LANG_PATH = '../lang/en_US';
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
	public	function	__construct( $strClass = NULL , $strLanguage = NULL )
	{
		if( is_null( $strClass ) )
		{
			throw	new	ErrorException( "No such class named {$strClass}" , $code , $severity , $filename , $lineno , $previous );
			return	false;
		}
		$this->_APPF = &$GLOBALS["APPF"];
		$this->Class = $strClass;
		if( is_null( $strLanguage ) )
		{
			$strLanguage = $this->_APPF["LANG"];
		}
		$this->Language = $strLanguage;
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
	/**
	 * 初始化物件
	 * @return Language
	 */
	protected	function	&BeforeConstruct()
	{
		$strLangFilePath = "{$this->_APPF["PATH_LANG"]}/{$this->Language}/lang.{$this->Class}.php";
		$this->Path = $strLangFilePath;
		if( file_exists( $this->Path ) )
		{
			include( $strLangFilePath );
		}
		$this->Content = array_merge( $this->Content , $_LANG );
		return	$this;
	}
	/**
	 * 離開前要做的事
	 * @return Language
	 */
	protected	function	&BeforeConstruct()
	{
		return	$this;
	}
	/**
	 * 取得語言資訊
	 * @param	string	$LangCode	語言碼
	 * @param	array	$Params	參數
	 * @return	string
	 */
	public	function	GetLanguage( $LangCode , $Params = array() )
	{
		if( ! isset( $this->Content[$LangCode] ) )
		{
			return	$LangCode;
		}
		$Return = $this->Content[$this->Class][$LangCode];
		if( preg_match( "/{$this->LeftTerminal}[a-z][a-z0-9\\-\\_]*{$this->RightTerminal}/i" , $Return ) )
		{
			foreach( $Params AS $Key => $Value )
			{
				$Return = str_replace( "{$this->LeftTerminal}{$Key}{$this->RightTerminal}" , $Value , $Return );
			}
		}
		return	$Return;
	}
	/**
	 * 設定語言資料
	 * @param	string	$LangCode
	 * @param	mixed	$Value
	 * @return	Language
	 */
	public	function	&SetLanguage( $LangCode , $Value )
	{
		$this->Content[$this->Class][$LangCode] = $Value;
		$CodeArray[] = "<?php";
		$CodeArray[] = "\$_LANG['{$this->Class}'] = unserialize( '" . addslashes( serialize( $this->Content[$this->Class] ) ) . "' );";
		$CodeArray[] = "?>";
		file_put_contents( $this->Path , implode( "\n" , $CodeArray ) );
		return	$this->BeforeConstruct();
	}
	/**
	 * 取得物件裡的子屬性
	 * @param	$PropertyName1	屬性名稱(可輸入多個)
	 * @throws	ErrorException
	 * @return	boolean|unknown
	 */
	public	function	GetProperty()
	{
		$PropertieNames = func_get_args();
		if( count( $PropertieNames ) <= 0 )
		{
			throw new ErrorException( "You don't request any property." , $code, $severity, $filename, $lineno, $previous);
			return	false;
		}elseif( count( $PropertieNames ) == 1 )
		{
			return	$this->{$PropertieNames[0]};
		}
		foreach( $PropertieNames AS $PropertyName )
		{
			$Return[$PropertyName] = $this->{$PropertyName};
		}
		return	$Return;
	}
	/**
	 * 設定屬性
	 * @param	string	$PropertyName	屬性名稱
	 * @param	unknown	$PropertyValue	屬性值
	 * @return	Language
	 */
	public	function	&SetProperty( $PropertyName , $PropertyValue )
	{
		$this->{$PropertyName} = $PropertyValue;
		return	$this;
	}
}
?>