<?php
/**
 * Recordset Cache運行規則
 * 根資料
 *	1.檔案命名：[sha1( $strSQL )].php
 *	2.記錄資料:
 *		2.1.$intLastUpdate	最後更新時間
 *		2.2.$intRecordCount	資料筆數
 *		2.3.$intColumnCount	欄位數目
 *		2.4.$aryRecords	每一筆資料的sha1的值(用以追踪資料)
 * 列資料
 *	1.檔案命名：[sha1( serialize( $aryRow ) )].php
 *	2.記錄資料：
 *		2.1.$aryRow	一列的資料
 *		2.2.$objRow
 *		2.2.$bolBOF	是否為BOF
 *		2.3.$bolEOF	是否為EOF
 */
/**
 * Enter description here ...
 * @author lamb100
 *
 */
class	PGADORecordset
{
	protected	$strSQL = '';
	protected	$resResult = NULL;
	protected	$resConnection = NULL;
	protected	$intCache = 0;
	protected	$strCacheFile = '';
	protected	$intLastUpdate = 0;
	protected	$intPointer = 0;	//目前資料游標所在位置
	protected	$aryCached = array();
	protected	$aryRows = array();
	protected	$intRows = 0;
	protected	$intCols = 0;
	protected	$intAffected = 0;
	protected	$bolEOF = true;
	protected	$bolBOF = true;

	/*Magic Methods*/
	public	function	__construct()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$resResult = NULL;
			$resConnection = $aryParams[2];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$resResult = $aryParams[1];
			$resConnection = $aryParams[2];
		}
		session_start();
		if( $intCache > 0 )
		{
			return	$this->bolSetInitThis( $intCache , $strSQL , $resConnection );
		}else
		{
			return	$this->bolSetInitThis( $strSQL , $resResult , $resConnection );
		}
		$this->bolSetMoveNext();
		$this->bolSetMovePrevious();
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
				"class"	=>	__CLASS__
			)
		);
		return	serialize( $aryReturn );
	}
	public	function	__invoke(){}
	public	function	__set_state(){}
	public	function	__clone(){}

	public	function	aryGetFetchRow( $intRow = NULL )
	{
		if( $this->intCache > 0 )
		{
			return	$this->aryGetCacheFetchRow( $intRow );
		}else
		{
			if( is_null( $intRow ) )
			{
				$aryReturn = pg_fetch_assoc( $this->resResult , $this->intPointer );
			}else
			{
				if( $intRow >= $this->intRows )
				{
					$intRow = $this->intRows - 1;
				}
				$this->intPointer = $intRow;
				$aryReturn = pg_fetch_assoc( $this->resResult , $this->intPointer );
			}
			$intIndex = 0;
			foreach( $aryReturn AS $strK => $mixV )
			{
				$strType = pg_field_type( $this->resResult , $intIndex );
				if( preg_match( '/^bool/i' , $strType ) )
				{
					$aryReturn[$strK] = ( strtolower( $mixV ) == 't' ? true : false );
				}
				$intIndex++;
			}
			return	$aryReturn;
		}
	}
	public	function	objGetFetchObject( $intRow = NULL )
	{
		if( $this->intCache > 0 )
		{
			return	$this->objGetCacheFetchObject( $intRow );
		}else
		{
			if( is_null( $intRow ) )
			{
				$objReturn = pg_fetch_object( $this->resResult , $this->intPointer );
			}else
			{
				if( $intRow >= $this->intRows )
				{
					$intRow = $this->intRows - 1;
				}
				$this->intPointer = $intRow;
				$objReturn = pg_fetch_object( $this->resResult , $this->intPointer );
			}
			$aryProperties = get_class_vars( $objReturn );
			foreach( $aryProperties AS $intIndex => $strFields )
			{
				$strType = pg_field_type( $this->resResult , $intIndex );
				if( preg_match( '/^bool/i' , $strType ) )
				{
					$objReturn->{$strFields} =  ( strtolower( $objReturn->{$strFields} ) == 't' ? true : false );
				}
				$intIndex++;
			}
			return	$objReturn;
		}
	}
	public	function	aryGetFetchCol( $mixCol )
	{
		if( $this->intCache > 0 )
		{
			return	$this->aryGetFetchCol( $mixCol );
		}else
		{
			$intRows = $this->intGetRecordCount();
			for( $intAc = 0 ; $intAc < $intRows ; $intAc++ )
			{
				$aryRow = pg_fetch_array( $this->resResult , $intAc );
				$aryReturn[] = $aryRow[$mixCol];
			}
		}
		return	$aryReturn;
	}
	public	function	aryGetFetchOne( $intRow , $mixCol )
	{
		$aryRow = $this->aryGetFetchRow( $intRow );
		return	$aryRow[$mixCol];
	}
	public	function	aryGetFetchAll()
	{
		return	pg_fetch_all( $this->resResult );
	}

	public	function	bolGetEOF()
	{
		if( $this->intPointer >= $this->intRows )
		{
			$this->intPointer = $this->intRows - 1;
			$this->EOF = true;
			return	true;
		}else
		{
			$this->EOF = false;
			return	false;
		}
	}
	public	function	bolGetBOF()
	{
		if( $this->intPointer < 0 )
		{
			$this->intPointer = 0;
			$this->BOF = true;
			return	true;
		}else
		{
			$this->BOF = false;
			return	false;
		}
	}
	public	function	bolSetMove( $intCursor )
	{
		$this->intPointer = $intCursor;
		return	true;
	}
	public	function	bolSetMovePrevious()
	{
		$this->intPointer--;
		if( $this->intPointer < 0 )
		{
			$this->intPointer = 0;
			return	false;
		}
		return	true;
	}
	public	function	bolSetMoveNext()
	{
		$this->intPointer++;
		if( $this->intPointer >= $this->intRows )
		{
			$this->intPointer = $this->intRows - 1;
			return	false;
		}
		return	true;
	}
	public	function	bolSetMoveFirst()
	{
		$this->intPointer = 0;
		return	true;
	}
	public	function	bolSetMoveLast()
	{
		$this->intPointer = $this->intRows - 1;
		return	true;
	}

	public	function	intGetRecordCount()
	{
		if( $this->intCache > 0 )
		{
			require( $this->strCacheFile );
			$this->intRows = $intRecordCount;
			return	$intRecordCount;
		}else
		{
			$this->intRows = pg_num_rows( $this->resResult );
			return	$this->intRows;
		}
	}
	public	function	intGetAffectRows()
	{
		return	pg_affected_rows( $this->resResult );
	}
	public	function	intGetFiledCount()
	{
		if( $this->intCache > 0 )
		{
			require( $this->strCacheFile );
			$this->intCols = $intColumnCount;
			return	$intColumnCount;
		}else
		{
			$this->intCols = pg_num_fields( $this->resResult );
			return	$this->intCols;
		}
	}
	public	function	aryGetFieldsInfo()
	{
		if( $this->intCache > 0 )
		{
			require( $this->strCacheFile );
			return	$aryColumnInfo;
		}else
		{
			$intCols = $this->intGetFiledCount();
			$aryColumnInfo = array();
			for( $intAc = 0 ; $intAc < $intCols ; $intAc++ )
			{
				$aryColumnInfo[$intAc]["name"] = pg_field_name( $this->resResult , $intAc );
				$aryColumnInfo[$intAc]["prtln"] = (int)pg_field_prtlen( $this->resResult ,  $intAc );
				$aryColumnInfo[$intAc]["size"] = (int)pg_field_size( $this->resResult ,  $intAc );
				$aryColumnInfo[$intAc]["type"] = pg_field_type( $this->resResult , $intAc );
				$aryColumnInfo[$aryColumnInfo[$intAc]["name"]] = $aryColumnInfo[$intAc];
			}
		}
	}
	public	function	aryGetFieldsName()
	{
		$aryColumnInfo = $this->aryGetFieldsInfo();
		$aryReturn = array();
		foreach( $aryColumnInfo AS $mixK => $aryV )
		{
			$aryReturn[$mixK] = $aryV["name"];
		}
		return	$aryReturn;
	}

	public	function	bolSetClose()
	{
		return	(boolean)pg_free_result( $this->resResult );
	}

	protected	function	bolSetInitThis()
	{
		$aryParams = func_get_args();
		if( preg_match( '/^[0-9]+$/iu' , $aryParams[0] ) )
		{
			$intCache = (int)$aryParams[0];
			$strSQL = (string)$aryParams[1];
			$resResult = NULL;
			$resConnection = $aryParams[2];
		}else
		{
			$intCache = 0;
			$strSQL = (string)$aryParams[0];
			$resResult = $aryParams[1];
			$resConnection = $aryParams[2];
		}
		$this->intCache = $intCache;
		$this->strSQL = $strSQL;
		$this->resResult = $resResult;
		$this->resConnection = $resConnection;
		if( $intCache > 0 )
		{
			if( preg_match( '/^\s*select/imu' , $strSQL ) )
			{
				$this->intCache = $intCache;
				return	$this->bolSetInitCacheData();
			}
		}
		if( preg_match( '/^\s*select/imu' , $strSQL ) )
		{
			$this->intRows = pg_num_rows( $this->resResult );
			$this->intCols = pg_num_fields( $this->resResult );
		}else
		{
			$this->intAffected = pg_affected_rows( $this->resResult );
		}
		return	true;
	}

	protected	function	bolSetInitCacheData()
	{
		$this->intPointer = 0;
		$strCacheFile = sha1( $this->strSQL ) . ".php";
		$str1stLayerDir = strConfDBCacheDir . "/" . substr( $strCacheFile , 0 , 3 );
		if( file_exists( $str1stLayerDir ) )
		{
			if( ! is_dir( $str1stLayerDir ) )
			{
				unlink( $str1stLayerDir );
				mkdir( $str1stLayerDir );
			}
		}else
		{
			mkdir( $str1stLayerDir );
		}
		$str2ndLayerDir = $str1stLayerDir . "/" . substr( $strCacheFile , 3 , 3 );
		if( file_exists( $str2ndLayerDir ) )
		{
			if( ! is_dir( $str2ndLayerDir ) )
			{
				unlink( $str2ndLayerDir );
				mkdir( $str2ndLayerDir );
			}
		}else
		{
			mkdir( $str2ndLayerDir );
		}
		$str3rdLayerDir = $str2ndLayerDir . "/" . substr( $strCacheFile , 6 , 3 );
		if( file_exists( $str3rdLayerDir ) )
		{
			if( ! is_dir( $str3rdLayerDir ) )
			{
				unlink( $str3rdLayerDir );
				mkdir( $str3rdLayerDir );
			}
		}else
		{
			mkdir( $str3rdLayerDir );
		}
		$strCacheFile = $str3rdLayerDir . "/" . $strCacheFile;
		$this->strCacheFile = $strCacheFile;
		if( file_exists( $strCacheFile ) )
		{
			require( $strCacheFile );
		}
		if( time() - $intLastCache > $this->intCache )
		{
			$this->resResult = pg_query( $this->resConnection , $this->strS );
			if( is_array( $aryRecords ) )
			{
				foreach( $aryRecords AS $strFile )
				{
					$strUnlinkFile =
						strConfDBCacheDir . "/" .
						substr( $strFile , 0 , 3 ) . "/" .
						substr( $strFile , 3 , 3 ) . "/" .
						substr( $strFile , 6 , 3 ) . ".php";
					unlink( $strUnlinkFile );
				}
			}
			//進行cache
			$aryContent = array();
			$intRecordCount = pg_num_rows( $this->resResult );
			$intColumnCount = pg_num_fields( $this->resResult );
			$resFile = fopen( $strCacheFile , "w" );
			$aryContent[] = "<?php";
			$aryContent[] = '$intLastCache = ' . (int)time() . ';';
			$aryContent[] = '$intRecordCount = ' . (int)$intRecordCount . ';';
			$aryContent[] = '$intColumnCount = ' . (int)$intColumnCount . ';';
			for( $intAc = 0 ; $intAc < $intColumnCount ; $intAc++ )
			{
				$aryContent[] = '$aryColumnInfo[' . $intAc . ']["name"] = "' . ( $strFieldName = pg_field_name( $this->resResult , $intAc ) ) . '";';
				$aryContent[] = '$aryColumnInfo[' . $intAc . ']["prtln"] = ' . (int)pg_field_prtlen( $this->resResult , $intAc ) . ';';
				$aryContent[] = '$aryColumnInfo[' . $intAc . ']["size"] = ' . (int)pg_field_prtlen( $this->resResult , $intAc ) . ';';
				$aryContent[] = '$aryColumnInfo[' . $intAc . ']["type"] = "' . ( pg_field_type( $this->resResult , $intAc ) ) . '";';
				$aryContent[] = '$aryColumnInfo["' . $strFieldName . '"] = $aryColumnInfo[' . $intAc . '];';
			}
			$this->intPointer = 0;
			for( $intAc = 0 ; $intAc < $intRecordCount ; $intAc++ )
			{
				$aryContent[] = '$aryRecords[] = "' . $this->strSetCacheRows() . '";';
				$this->intPointer++;
			}
			$this->intPointer = 0;
			$aryContent[] = "?>";
			fwrite( $resFile , implode( "\n" , $aryContent ) );
			$resFile = fclose( $resFile );
			pg_free_result( $this->res );
			return	true;
		}else
		{
			return	true;
		}
	}
	protected	function	aryGetCacheFetchRow( $intRow = NULL )
	{
		require( $this->strCacheFile );
		if( is_null( $intRow ) )
		{
			require( $aryRecords[$this->intPointer] );
			return	$aryRow;
		}else
		{
			if( $intRow >= $intRecordCount )
			{
				$intRow = $intRecordCount - 1;
			}
			$this->intPointer = $intRow;
			require( $aryRecords[$this->intPointer] );
			return	$aryRow;
		}
	}
	protected	function	aryGetCacheFetchCol( $mixCol )
	{
		require( $this->strCacheFile );
		foreach( $aryRecords AS $strFile )
		{
			require( $strFile );
			$aryReturn[] = $aryRow[$mixCol];
		}
		return	$aryReturn;
	}
	protected	function	objGetCacheFetchObject( $intRow = NULL )
	{
		require( $this->strCacheFile );
		if( is_null( $intRow ) )
		{
			require( $aryRecords[$this->intPointer] );
			return	$objRow;
		}else
		{
			if( $intRow >= $intRecordCount )
			{
				$intRow = $intRecordCount - 1;
			}
			$this->intPointer = $intRow;
			require( $aryRecords[$this->intPointer] );
			return	$objRow;
		}
	}
	protected	function	mixGetCacheFetchOne( $intRow , $mixCol )
	{
		$aryRow = $this->aryGetCacheFetchRow( $intRow );
		return	$aryRow[$mixCol];
	}
	protected	function	aryGetCacheFetchAll()
	{
		require( $this->strCacheFile );
		$aryReturn = array();
		foreach( $aryRecords AS $strCacheFile )
		{
			require( $strCacheFile );
			$aryReturn[] = $aryRow;
		}
		return	$aryReturn;
	}

	protected	function	strSetCacheRows()
	{
		$aryRow = pg_fetch_all( $this->resResult , $this->intPointer );
		$objRow = pg_fetch_object( $this->resResult , $this->intPointer );
		$strCacheFile = sha1( serialize( $aryRow ) ) . ".php";
		$strReturn = $strCacheFile;
		$strCacheFile = $this->strGetCacheFileWholePath( $strCacheFile );
		$resFile = fopen( $strCacheFile , "w" );
		$aryContent = array();
		$aryContent[] = '<?php';
		$aryContent[] = '$aryRow = unserialize( "' . addslashes( serialize( $aryRow ) ) . '" );';
		$aryContent[] = '$objRow = unserialize( "' . addslashes( serialize( $objRow ) ) . '" );';
		$aryContent[] = '?>';
		fclose( $resFile );
		return	$strCacheFile;
	}

	protected	function	strGetCacheFileWholePath( $strCacheFile )
	{
		$str1stLayerDir = strConfDBCacheDir . "/" . substr( $strCacheFile , 0 , 3 );
		if( file_exists( $str1stLayerDir ) )
		{
			if( ! is_dir( $str1stLayerDir ) )
			{
				unlink( $str1stLayerDir );
				mkdir( $str1stLayerDir );
			}
		}else
		{
			mkdir( $str1stLayerDir );
		}
		$str2ndLayerDir = $str1stLayerDir . "/" . substr( $strCacheFile , 3 , 3 );
		if( file_exists( $str2ndLayerDir ) )
		{
			if( ! is_dir( $str2ndLayerDir ) )
			{
				unlink( $str2ndLayerDir );
				mkdir( $str2ndLayerDir );
			}
		}else
		{
			mkdir( $str2ndLayerDir );
		}
		$str3rdLayerDir = $str2ndLayerDir . "/" . substr( $strCacheFile , 6 , 3 );
		if( file_exists( $str3rdLayerDir ) )
		{
			if( ! is_dir( $str3rdLayerDir ) )
			{
				unlink( $str3rdLayerDir );
				mkdir( $str3rdLayerDir );
			}
		}else
		{
			mkdir( $str3rdLayerDir );
		}
		return	strConfDBCacheDir . "/" .
			substr( $strCacheFile , 0 , 3 ) . "/" .
			substr( $strCacheFile , 3 , 3 ) . "/" .
			substr( $strCacheFile , 6 , 3 ) . ".php";
	}
}
?>