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
	public	$LastResult = false;
	protected	$DB = array();
	protected	$View = array();
	protected	$_APPF = array();
	
	
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
	
}
?>