<?php
include( 'class.pg_ado_connector.php' );
include( 'class.pg_ado_recordset.php' );
include( 'class.smartruct.php' );

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
		"mem"	=>	array() ,
	);
	protected	$ParamsDefine = array();


	/*Magic Methods*/
	public	function	__construct()
	{
		session_start();
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

	protected	function	&SetTimeTrace()
	{
		list( $fltNow , $intNow ) = explode( ' ' , microtime() );
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
		return	$this;
	}
}