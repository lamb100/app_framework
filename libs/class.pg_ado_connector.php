<?php
define( "intBPACSelectPageMode" , 1 );
define( "intBPACSelectOffsetMode" , 0 );

define( "intBPACTransIsoLevelSerializable" , 1 );
define( "intBPACTransIsoLevelRepeatableRead" , 2 );
define( "intBPACTransIsoLevelReadCommited" , 3 );
define( "intBPACTransIsoLevelReadUncommited" , 4 );
define( "intBPACTransReadWrite" , 1 );
define( "intBPACTransReadOnly" , 0 );

define( "intBPACMetaDataCache" , 15 );

class	PGADOConnector
{
	/**
	 * 連線資訊
	 * @var	array
	 */
	protected	$aryConnectionInfo = array();
	/**
	 * 是否連線
	 * @var	boolean
	 */
	protected	$bolConnected = false;
	/**
	 * 連線的handle(resource)
	 * @var	resource
	 */
	protected	$resConnection = NULL;
	/**
	 * 目前的連線是否有transaction
	 * @var	boolean
	 */
	protected	$bolInTransaction = false;
	/**
	 * 是否是常效性連線
	 * @var	boolean
	 */
	protected	$bolPersistent = false;
	/**
	 * 是否記錄資料修改記錄
	 * @var boolean
	 */
	protected	$bolSaveChangeLog = false;
	/**
	 * 儲存所記錄的儲存點(Save Point)
	 * @var	array
	 */
	protected	$aryTransSavePoints = array();
	/**
	 * 預存的一些系統相關的SQL指令
	 * @var	array
	 */
	protected	$aryPredefineSQL = array(
		//取得系統內的所有資料庫
		"DB"	=>	"SELECT datname AS \"name\" FROM pg_database WHERE datname not IN ('template0','template1') ORDER BY datname" ,
		//取得資料庫內所有的資料表
		"TABLE"	=>	"SELECT tablename AS \"name\",'T' AS \"type\" FROM pg_tables WHERE tablename NOT LIKE 'pg\_%'
	AND tablename NOT IN ('sql_features', 'sql_implementation_info', 'sql_languages',
	 'sql_packages', 'sql_sizing', 'sql_sizing_profiles')
	UNION
        SELECT viewname AS \"name\",'V' AS \"type\" FROM pg_views WHERE viewname NOT LIKE 'pg\_%'" ,
		//取得某一個資料表內所有的欄位
		"FIELD"	=>	"SELECT a.attname AS \"name\", t.typname AS \"type\", a.attlen AS int_len, a.atttypmod, a.attnotnull, a.atthasdef, a.attnum
FROM pg_class c, pg_attribute a, pg_type t, pg_namespace n
WHERE relkind in ('r','v') AND (c.relname='{TABLE}' or c.relname = lower('{TABLE}'))
 AND c.relnamespace=n.oid and n.nspname='{DB}'
	AND a.attname not like '....%%' AND a.attnum > 0
	AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum" ,
		//取得某一個資料表內所有的鍵值
		"KEYS"	=>	"SELECT ic.relname AS index_name, a.attname AS column_name,i.indisunique AS unique_key, i.indisprimary AS primary_key
	FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a WHERE bc.oid = i.indrelid AND ic.oid = i.indexrelid AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum) AND a.attrelid = bc.oid AND bc.relname = '{TABLE}'" ,
		//取得某一個資料表內所有的外鍵值
		"FOREIGN_KEY" => "SELECT
    tc.constraint_name, tc.table_name, kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM
    information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
WHERE constraint_type = 'FOREIGN KEY' AND lower('{TABLE}') IN ( tc.table_name , ccu.table_name ) ORDER BY tc.table_name , kcu.column_name , ccu.table_name , ccu.column_name",
	);
	/**
	 * 當物件在被實作時，曾經執行過的SQL指令
	 * @var	array
	 */
	protected	$aryExecutedSQL = array();
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
	 * 是否要儲存異動經歷
	 * @param string $bolStatus
	 */
	public	function	bolSetSaveChangeLog( $bolStatus = NULL )
	{
		if( is_null( $bolStatus ) )
		{
			$this->bolSaveChangeLog = ! $this->bolSaveChangeLog;
		}else
		{
			$this->bolSaveChangeLog = (boolean)$bolStatus;
		}
		return	true;
	}
	/**
	 * 設定連線
	 * @param	string	$strDBHost	資料庫主機
	 * @param	string	$strDBUser	資料庫連線用使用者
	 * @param	string	$strDBPass	資料庫連線用密碼
	 * @param	string	$strDBName	資料庫名稱
	 * @param	integer	$intDBPort	資料庫連線埠號
	 * @param	boolean	$bolNew		是否新建連線	optional
	 * @param	boolean	$bolPersistent	是否為長效型連線	optional
	 * @throws	ErrorException
	 * @return	boolean	連線是否成功
	 */
	public	function	bolSetConnect( $strDBHost , $strDBUser , $strDBPass , $strDBName , $intDBPort , $bolNew = false , $bolPersistent = false )
	{
		try
		{
			$strConnectionString = '';
			$aryConnectionString = array();
			if( $strDBHost )
			{
				$aryConnectionString[] = "host={$strDBHost}";
			}
			if( $strDBUser )
			{
				$aryConnectionString[] = "user={$strDBUser}";
				if( $strDBPass )
				{
					$aryConnectionString[] = "password={$strDBPass}";
				}
			}
			if( $strDBName )
			{
				$aryConnectionString[] = "dbname={$strDBName}";
			}
			if( $intDBPort != 5432 || $intDBPort >= 0 )
			{
				$aryConnectionString[] = "port={$intDBPort}";
			}
			$strConnectionString = implode( ' ' , $aryConnectionString );
			if( $bolPersistent )
			{
				if( $bolNew )
				{
					$this->resConnection = pg_pconnect( $strConnectionString , PGSQL_CONNECT_FORCE_NEW );
				}else
				{
					$this->resConnection = pg_pconnect( $strConnectionString );
				}
				$this->bolPersistent = true;
				$this->bolConnected = (bool)( pg_connection_status( $this->resConnection ) == PGSQL_CONNECTION_OK );
			}else
			{
				if( $bolNew )
				{
					$this->resConnection = pg_connect( $strConnectionString , PGSQL_CONNECT_FORCE_NEW );
				}else
				{
					$this->resConnection = pg_connect( $strConnectionString );
				}
				$this->bolPersistent = false;
				$this->bolConnected = (bool)( pg_connection_status( $this->resConnection ) == PGSQL_CONNECTION_OK );
			}
		}catch ( ErrorException $objE )
		{
			throw	$objE;
			exit;
			return	false;
		}
		$this->aryConnectionInfo["strHost"] = $strDBHost;
		$this->aryConnectionInfo["strUser"] = $strDBUser;
		$this->aryConnectionInfo["strPass"] = strGetEncrypt( $strDBPass , true );
		$this->aryConnectionInfo["strDB"] = $strDBName;
		$this->aryConnectionInfo["intPort"] = $intDBPort;
		$this->aryConnectionInfo["strConnectionString"] = $strConnectionString;
		return	true;
	}
	/**
	 * 關閉連線
	 * @throws	ErrorException
	 * @return	boolean	關閉是否成功
	 */
	public	function	bolSetClose()
	{
		if( $this->bolConnected() )
		{
			try
			{
				pg_close( $this->resConnection );
			}catch( ErrorException $objE )
			{
				throw	$objE;
				return	false;
			}
			return	true;
		}else
		{
			return	true;
		}
	}
	/**
	 * 是否已連線
	 * @throws	ErrorException
	 * @return	boolean
	 */
	public	function	bolGetConnected()
	{
		try
		{
			$this->bolConnected = (bool)( pg_connection_status( $this->resConnection ) == PGSQL_CONNECTION_OK );
			return	$this->bolConnected;
		}catch( ErrorException $objE )
		{
			throw	$objE;
			return	false;
		}
	}
	/**
	 * 重新連線
	 * @throws	ErrorException
	 * @return boolean
	 */
	public	function	bolSetReconnect()
	{
		try
		{
			pg_connection_reset( $this->resConnection );
		}catch( ErrorException $objE )
		{
			throw	$objE;
			exit;
			return	false;
		}
	}
	//以下所有和SELECT相關的，除了一個引數外，第一個數值引數都將被視為快取時間
	/**
	 * 執行SQL指令
	 * @param	integer	$intCache	快取時間	optional
	 * @param	string	$strSQL	SQL指令
	 * @throws	ErrorException
	 * @return	PGADORecordset	執行結果物件
	 */
	public	function	objSetExecute()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
		}
		$this->aryExecutedSQL[] = $strSQL;
		if( preg_match( '/^select/imu' , $strSQL ) )
		{
			if( $intCache > 0 )
			{
				return	new	PGADORecordset( $intCache , $strSQL , $this->resConnection );
			}else
			{
				try
				{
					$resResult = pg_query( $strSQL );
				}catch( ErrorException $objE )
				{
					throw	$objE;
					return	false;
				}
				return	new	PGADORecordset( $strSQL , $resResult , $this->resConnection );
			}
		}else
		{
			try
			{
				$resResult = pg_query( $strSQL );
			}catch( ErrorException $objE )
			{
				throw	$objE;
				return	false;
			}
			return	(boolean)$resResult;
		}
	}
	/**
	 * 取得最後一次執行的SQL指令
	 * @return	string
	 */
	public	function	strGetLastSQL()
	{
		$arySQLS = array_values( $this->aryExecutedSQL );
		return	$arySQLS[ count( $arySQLS ) - 1 ];
	}
	/**
	 * 擷取資料
	 * @param	integer	$intCache	快取時間	optional
	 * @param	string	$strSQL	SQL指令(只限select指令)
	 * @param	integer	$intMode	執行SQL的模式
	 * @param	integer	$intCount	單一筆資料筆數
	 * @param	integer	$intPageOffset	如果$intMode==intBPACSelectPageMode:頁數<br/>$intMode==intBPACSelectOffsetMode:資料開始的位置
	 * @throws	ErrorException
	 * @return	boolean|PGADORecordset
	 */
	public	function	objGetSelect()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$intMode = (int)$aryParams[2];
			$intCount = (int)$aryParams[3];
			$intPageOffset = (int)$aryParams[4];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$intMode = (int)$aryParams[1];
			$intCount = (int)$aryParams[2];
			$intPageOffset = (int)$aryParams[3];
		}
		if( ! preg_match( '/^\s*select/imu' , $strSQL ) )
		{
			throw	new	ErrorException( __METHOD__ . " is just for the select SQL command." ,  __CLASS__ . ":1" , 0 , __FILE__ , __LINE__ );
			return	false;
		}
		switch( $intMode )
		{
			case	intBPACSelectOffsetMode:
				$intOffset = $intPageOffset;
			break;
			case	intBPACSelectPageMode:
				$intOffset = ( $intPageOffset - 1 ) * $intCount;
			break;
		}
		if( $intCount > 0 )
		{
			if( preg_match( '/LIMIT\s+[0-9]+(\s+OFFSET\s+[0-9]+|\s*\,\s*[0-9]+)\s*$/imu' , $strSQL ) )
			{
				$strSQL = preg_replace( '/LIMIT\s+[0-9]+(\s+OFFSET\s+[0-9]+|\s*\,\s*[0-9]+)\s*/imu' , "LIMIT {$intCount} OFFSET " . (int)$intOffset , $strSQL );
			}else
			{
				$strSQL = $strSQL . " LIMIT {$intCount} OFFSET " . (int)$intOffset;
			}
		}
		if( $intCache > 0 )
		{
			return	$this->objSetExecute( $intCache , $strSQL );
		}
		else
		{
			return	$this->objSetExecute( $strSQL );
		}
	}
	/**
	 * 抓取單(第)一筆的指定欄位
	 * @param	integer	$intCache	快取時間	optional
	 * @param	string	$strSQL	SQL指令
	 * @param	integer	$intRow	指定資料筆數
	 * @param	mixed	$mixCol	指定欄位順序或欄位名稱
	 * @throws	ErrorException
	 * @return	mixed
	 */
	public	function	mixGetOne()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$intRow = (int)$aryParams[2];
			$mixCol = $aryParams[3];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$intRow = (int)$aryParams[1];
			$mixCol = $aryParams[2];
		}
		if( ! preg_match( '/^\s*select/imu' , $strSQL ) )
		{
			throw	new	ErrorException( __METHOD__ . " is just for the select SQL command." ,  __CLASS__ . ":1" , 0 , __FILE__ , __LINE__ );
			return	false;
		}
		if( $intCache > 0 )
		{
			$objRS = $this->objSetExecute( $intCache , $strSQL );
			$mixReturn = $objRS->aryGetCacheOne( $intRow , $mixCol );
		}else
		{
			$resRS = pg_query( $strSQL );
			$aryRow = pg_fetch_array( $resRS , $intRow );
			if( preg_match( '/^[t|f]$/iu' , $aryRow[$mixCol] ) )
			{
				$aryRow[$mixCol] = ( $aryRow[$mixCol] == 't' );
			}
			return	$aryRow[$mixCol];
		}
	}
	/**
	 * 取得某一筆的資料
	 * @param	integer	$intCache	快取時間	optional
	 * @param	string	$strSQL	SQL指令
	 * @param	integer	$intRows	資料所在筆數
	 * @throws	ErrorException
	 * @return	array
	 */
	public	function	aryGetRow()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$intRows = (int)$aryParams[2];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$intRows = (int)$aryParams[1];
		}
		if( ! preg_match( '/^\s*select/imu' , $strSQL ) )
		{
			throw	new	ErrorException( __METHOD__ . " is just for the select SQL command." ,  __CLASS__ . ":1" , 0 , __FILE__ , __LINE__ );
			return	false;
		}
		if( $intCache > 0 )
		{
			$objRS = $this->objSetExecute( $intCache , $strSQL );
			return	$objRS->aryGetFetchRow( $intRows );
		}else
		{
			$resRS = pg_query( $strSQL );
			$aryRow = pg_fetch_array( $resRS , (int)$intRows );
			return	$aryRow;
		}
	}
	/**
	 * 取得單一欄的全部資料
	 * @param	integer	$intCache	快取時間	optional
	 * @param	string	$strSQL	SQL指令
	 * @param	mixed	$mixCol	指定欄位順序或欄位名稱
	 * @throws	ErrorException
	 * @return	array
	 */
	public	function	aryGetCol()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$mixCol = $aryParams[2];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$mixCol = $aryParams[1];
		}
		if( ! preg_match( '/^\s*select/imu' , $strSQL ) )
		{
			throw	new	ErrorException( __METHOD__ . " is just for the select SQL command." ,  __CLASS__ . ":1" , 0 , __FILE__ , __LINE__ );
			return	false;
		}
		if( $intCache > 0 )
		{
			$objRS = $this->objSetExecute( $intCache , $strSQL );
			$objRS->bolSetPointer( $intRows );
			return	$objRS->aryGetCacheFetchCol( $mixCol );
		}else
		{
			$resRS = pg_query( $strSQL );
			//$aryRow = pg_fetch_array( $resRS , $intRow );
			if( preg_match( '^[0-9]+$' , $mixCol ) )
			{
				$aryRow = pg_fetch_all_columns( $resRS , $mixCol );
			}else
			{
				$aryRow = pg_fetch_all_columns( $resRS , pg_field_num( $resRS , $mixCol ) );
			}
			return	$aryRow;
		}
	}
	//以下為Transcation
	/**
	 * 設定Transcation的模式
	 * @param	$mixIsoLevel	設定Transcation等級
	 * @param	$mixRead	唯讀或讀寫皆可
	 * @throws ErrorException
	 * @return boolean
	 */
	public	function	bolSetTransMode( $mixIsoLevel , $mixRead )
	{
		if( preg_match( '/^[1-4]$/imu' , $mixIsoLevel ) )
		{
			switch( $mixIsoLevel )
			{
				case	intBPACTransIsoLevelReadCommited:
					$mixIsoLevel = "Read Commited";
				break;
				case	intBPACTransIsoLevelReadUncommited:
					$mixIsoLevel = "Read Uncommited";
				break;
				case	intBPACTransIsoLevelRepeatableRead:
					$mixIsoLevel = "Repeatable Read";
				break;
				case	intBPACTransIsoLevelSerializable:
					$mixIsoLevel = "Serializable";
				break;
			}
		}
		$aryAvailableIsoLevel =
			array(
				strtoupper( "Read Commited" ) ,
				strtoupper( "Read Uncommited" ) ,
				strtoupper( "Repeatable Read" ) ,
				strtoupper( "Serializable" )
			);
		if( ! in_array( strtoupper( $mixIsoLevel ) , $aryAvailableIsoLevel ) )
		{
			throw	new	ErrorException( "{$mixIsoLevel} is not supported transaction isolation level for postgresql." , __CLASS__ . ":2" , 0 , __FILE__ , __LINE__ );
			exit;
		}
		unset( $aryAvailableIsoLevel );
		if( preg_match( '/^[1-2]$/imu' , $mixRead ) )
		{
			switch( $mixIsoLevel )
			{
				case	intBPACTransReadOnly:
					$mixRead = "Read Only";
				break;
				case	intBPACTransReadWrite:
					$mixRead = "Read Write";
				break;
			}
		}
		$aryAvailableRead = array( strtoupper( "Read Only" ) , strtoupper( "Read Write" ) );
		if( ! in_array( strtoupper( $mixRead ) , $aryAvailableRead ) )
		{
			throw	new	ErrorException( "{$mixRead} is not supported transaction read mode for postgresql." , __CLASS__ . ":3" , 0 , __FILE__ , __LINE__ );
			exit;
		}
		if( $this->bolInTransaction )
		{
			throw	new	ErrorException( "As the transaction was started, you can not set the transaction mode." , __CLASS__ . ":4" , 0 , __FILE__ , __LINE__ );
			exit;
		}
		return	(boolean)$this->objSetExecute( "SET TRANSACTION {$mixIsoLevel} {$mixRead}" );
	}
	/**
	 * 開始Transcation
	 * @return boolean
	 */
	public	function	bolSetBeginTrans()
	{
		$bolReturn = (boolean)$this->objGetSelect( "BEGIN TRANSACTION" );
		if( $bolReturn )
		{
			$this->bolInTransaction = true;
		}
		return	$bolReturn;
	}
	/**
	 * 設定Transcation的儲存點
	 * @param	string	$strSavePointName	儲存點名稱
	 * @return boolean
	 */
	public	function	bolSetSavePoint( $strSavePointName )
	{
		$strSavePointName = strtolower( $strSavePointName );
		$bolReturn = (boolean)$this->objGetSelect( "SAVEPOINT {$strSavePointName}" );
		if( $bolReturn )
		{
			$this->aryTransSavePoints[] = $strSavePoint;
		}
		return	$bolReturn;
	}
	/**
	 * Transaction的回復
	 * @param string $strSavePointName	回到的儲存點名稱
	 * @return boolean
	 */
	public	function	bolSetRollbackTrans( $strSavePointName = '' )
	{
		if( $strSavePointName )
		{
			$strSavePointName = strtolower( $strSavePointName );
			if( ! in_array( $strSavePointName , $this->aryTransSavePoints ) )
			{
				return	false;
			}
			$strSQL = "ROLLBACK TRANSACTION TO {$strSavePointName}";
			do
			{
				$strPop = array_pop( $this->aryTransSavePoints );
			}while( $strPop != $strSavePointName );
		}else
		{
			$strSQL = "ROLLBACK TRANSACTION";
			$this->aryTransSavePoints = array();
		}
		if( count( $this->aryTransSavePoints ) <= 0 )
		{
			$this->bolInTransaction = false;
		}
		$bolReturn = (boolean)$this->objGetSelect( $strSQL );
		return	$bolReturn;
	}
	/**
	 * Transaction的確認
	 * @return boolean
	 */
	public	function	bolSetCommitTrans()
	{
		$bolReturn = (boolean)$this->objGetSelect( "COMMIT TRANSACTION" );
		if( $bolReturn )
		{
			$this->bolInTransaction = false;
			$this->aryTransSavePoints = array();
		}
		return	$bolReturn;
	}

	/**
	 * 為字詞加引號
	 * @param unknown $strPreQuote
	 * @return string
	 */
	static	public	function	strGetQuoted( $strPreQuote )
	{
		return	"'" . pg_escape_bytea( $strPreQuote ) . "'";
	}
	//以下為資料庫資料
	/*以下取得資料的SQL，參考ADOdb裡的SQL*/
	/**
	 * 取得所有資料庫
	 * @return	array
	 */
	public	function	aryGetDatabases()
	{
		$strSQL = $this->aryPredefineSQL["DB"];
		$objRS = $this->objSetExecute( intBPACMetaDataCache , $strSQL );
		$aryReturn = array();
		do
		{
			$aryRow = $objRS->aryGetFetchRow();
			$aryReturn[] = $aryRow["name"];
		}while( $objRS->bolSetMoveNext() );
		return	$aryReturn;
	}
	/**
	 * 取得某一資料庫的資料表
	 * @param	boolean	$bolWithoutView	是否含View
	 * @return	array
	 */
	public	function	aryGetTables( $bolWithoutView = false )
	{
		$strSQL = $this->aryPredefineSQL["TABLE"];
		$objRS = $this->objSetExecute( intBPACMetaDataCache , $strSQL );
		$aryReturn = array();
		do
		{
			if( $bolWithoutView )
			{
				if( $aryRow["type"] == "V" )
				{
					if( $objRS->bolSetMoveNext() )
						continue;
					else
						break;
				}
			}
			$aryRow = $objRS->aryGetFetchRow();
			$aryReturn[] = $aryRow["name"];
		}while( $objRS->bolSetMoveNext() );
		return	$aryReturn;
	}
	/**
	 * 取得某一個資料表內的欄位資訊
	 * @param	string	$strTable	資料表名稱
	 * @return	array
	 */
	public	function	aryGetFieldsInfo( $strTable )
	{
		$strSQL = $this->aryPredefineSQL["FIELD"];
		$strSQL = str_replace(
			array( "{DB}" , "{TABLE}" ) ,
			array( $this->aryConnectionInfo["strDB"] ,
			$strTable ) , $strSQL );
		$aryKeys = $this->aryGetKeys( $strTable );
		$objRS = $this->objSetExecute( intBPACMetaDataCache , $strSQL );
		$aryReturn = array();
		do
		{
			$aryRow = $objRS->aryGetFetchRow();
			$aryRow["pk"] = in_array( $aryRow["name"] , $aryKeys["primary_key"] );
			$aryRow["uk"] = in_array( $aryRow["name"] , $aryKeys["unique_key"] );
			$aryReturn[] = $aryRow;
		}while( $objRS->bolSetMoveNext() );
		return	$aryReturn;
	}
	/**
	 * 取得某一資料表內各欄位的名稱
	 * @param	string	$strTable	資料表名稱
	 * @return	array
	 */
	public	function	aryGetFieldsName( $strTable )
	{
		$aryFields = $this->aryGetFieldsInfo( $strTable );
		$aryReturn = array();
		foreach( $aryFields AS $aryField )
		{
			$aryReturn[] = $aryField["name"];
		}
		return	$aryReturn;
	}
	/**
	 * 取得某一資料表的主鍵
	 * @param	string	$strTable	資料表名稱
	 * @return	array
	 */
	public	function	aryGetPrimaryKey( $strTable )
	{
		$aryKeys = $this->aryGetKeys( $strTable );
		return	$aryKeys["primary_key"];
	}
	/**
	 * 取得某一資料表的外鍵
	 * @param	string	$strTable	資料表名稱
	 * @return	array
	 */
	public	function	aryGetForeignKey( $strTable )
	{
		$aryKeys = $this->aryGetKeys( $strTable );
		return	$aryKeys["unique_key"];
	}
	/**
	 * 取得某一資料表的諸鍵
	 * @param	string	$strTable	資料表名稱
	 * @return	array
	 */
	protected	function	aryGetKeys( $strTable )
	{
		$strSQL = $this->aryPredefineSQL["KEYS"];
		$strSQL = str_replace(
			array( "{TABLE}" ) ,
			array( $strTable ) , $strSQL );
		$objRS = $this->objSetExecute( intBPACMetaDataCache , $strSQL );
		$aryReturn = array();
		do
		{
			$aryRow = $objRS->aryGetFetchRow();
			$aryReturn["full"][] = $aryRow;
			if( $aryRow["primary_key"] )
			{
				$aryReturn["primary_key"] = $aryRow;
			}
			if( $aryRow["unique_key"] )
			{
				$aryReturn["unique_key"] = $aryRow;
			}
		}while( $objRS->bolSetMoveNext() );
		//取得外鍵
		$strSQL = $this->aryPredefineSQL["FOREIGN_KEY"];
		$strSQL = str_replace(
			array( "{TABLE}" ) ,
			array( $strTable ) , $strSQL );
		$objRS = $this->objSetExecute( intBPACMetaDataCache , $strSQL );
		do
		{
			$aryReturn["foreign_key"][$aryRow["constraint_name"]]["base"]["table"] = $aryRow["table_name"];
			$aryReturn["foreign_key"][$aryRow["constraint_name"]]["base"]["columns"][] = $aryRow["column_name"];
			$aryReturn["foreign_key"][$aryRow["constraint_name"]]["reference"]["table"] = $aryRow["foreign_table_name"];
			$aryReturn["foreign_key"][$aryRow["constraint_name"]]["reference"]["columns"][] = $aryRow["foreign_column_name"];
		}while( $objRS->bolSetMoveNext() );
		return	$aryReturn;
	}
	/**
	 * 輸入異動記錄
	 * @param	string	$strSQL	異動的SQL
	 * @return boolean
	 */
	protected	function	bolSetChangeLog( $strSQL )
	{
		$this->bolSetBeginTrans();
		/*Check tb_change_log exist or not*/
		$aryTables = $this->aryGetTables();
		if( ! in_array( 'tb_change_log' , $aryTables ) )
		{
			try
			{
				$strSQL = "CREATE TABLE(
						num_chl_time	NUMERIC( 20 , 7 ) NOT NULL  DEFAULT 0.0,
						net_chl_ip	INET NOT NULL DEFAULT '127.0.0.1'::INET ,
						chr_chl_session	CHAR(127) NOT NULL ,
						vch_chl_angent	VARCHAR(255) NOT NULL ,
						vch_chl_table	VARCHAR(255)	NOT	NULL,
						vch_chl_database VARCHAR(255) NOT NULL,
						vch_chl_ddl_type VARCHAR(15) NOT NULL ,
						txt_chl_sql	TEXT	NOT NULL,
						chr_chl_sha1	CHAR(40) NOT NULL ,
						PRIMARY KEY ( chr_chl_sha1 )
				)";
				$this->bolSetExecute( $strSQL );
			}catch( ErrorException	$objE )
			{
				$this->bolSetRollbackTrans();
				return	false;
			}
		}

		if( ! preg_match( '/^(insert\s+into|update|delete\s+from|create\s+table|alter\s+table|drop\s+table)/im' , $strSQL , $aryRegs ) )
		{
			return	false;
		}
		if( preg_match( '/^(insert\s+into\s+tb_change_log|update\s+tb_change_log|delete\s+from\s+tb_change_log)/im' , $strSQL ) )
		{
			return	false;
		}
		preg_match( '/^(create\s+from|alter\s+table|drop\s+table|insert\s+into|update|delete\s+from)' .
		'([\_a-z][\_a-z0-9]*\.)?([\_a-z][\_a-z0-9]*)/im' , $strSQL , $aryRegs );
		$strTable = $aryRegs[3];
		$strDatabase = $aryRegs[2];
		$strDDLType = strtoupper( preg_replace( '\s+' , ' ' , $aryRegs[1] ) );
		list( $intNow , $fltNow ) = explode( " " , microtime() );
		$strUser = $_SESSION["user"]["strUserID"];
		$aryInput["num_chl_time"] = "{$fltNow} + {$intNow}";
		$aryInput["net_chl_ip"] = $this->strGetQuoted( strGetRealIP() );
		$aryInput["chr_chl_session"] = $this->strGetQuoted( session_id() );
		$aryInput["vch_chl_agent"] = $this->strGetQuoted( $_SERVER["HTTP_USER_AGENT"] );
		$aryInput["vch_chl_table"] = $this->strGetQuoted( $strTable );
		$aryInput["vch_chl_ddl_type"] = $this->strGetQuoted( $strDDLType );
		$aryInput["vch_chl_database"] = $this->strGetQuoted( ( $strDatabase ? $strDatabase : $this->aryConnectionInfo["strDB"] ) );
		$aryInput["txt_chl_sql"] = $this->strGetQuoted( $strSQL );
		$aryInput["vch_chl_executor"] = $this->strGetQuoted( $strUser );
		$aryInput["chr_chl_sha1"] = $this->strGetQuoted( sha1( serialize( $aryInput ) ) );
		$strSQL = "INSERT INTO (" . implode( "," , array_keys( $aryInput ) ) . ") VALUES (" . implode( "," , $aryInput ) . ")";
		unset( $fltNow , $intNow , $strCommandType , $strTable , $strDatabase , $strUser );
		return	(boolean)$this->objSetExecute( $strSQL );
	}
}
?>