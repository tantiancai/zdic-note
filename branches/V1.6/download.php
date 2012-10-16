<?php
require_once '../include/common.inc.php';
require_once DISCUZ_ROOT.'./uc_client/client.php';

//获取用户登录信息
if(!empty($_DCOOKIE['auth']))
{
	list( , , $uid) = explode("\t", authcode($_DCOOKIE['auth'], 'DECODE'));
}
else if(!empty($_DCOOKIE['uid']))
{
	$uid = $_DCOOKIE['uid'];
}
else
{
	$uid = '';
}

function main()
{
	$downloadfile = 'memorandum.txt';
	if( $GLOBALS['uid'] > 0 )
	{
		$table = sqlQuery();
		header("Content-type:text/plain; charset=utf-8");
		header("Content-disposition: attachment; filename=$downloadfile");
		while( $row = mysql_fetch_row($table) )
		{
			echo "词条：$row[0]\r\n";
			echo "备注：$row[1]\r\n";
			echo "时间：$row[2]\r\n";
			echo "---------------\r\n";
		}
	}
	else
	{
		echo '请登录后使用';
	}
}

function sqlQuery()
{
	$db = $GLOBALS['db'];
	$uid = $GLOBALS['uid'];

	if(($page == "") || ($page <= 0 ))	//没指定页码或页码有误
	{
		$GLOBALS['page'] = $page = 1;	//显示第一页
	}

	if($uid > 0)
	{	
		$sql = "SELECT
					bwcy as word,
					bwbz as comment,
					DATE_FORMAT(bwsj, '%y/%c/%e') as adddate
				FROM	zdic_bwl
				WHERE	userid = '$uid' ORDER BY	bwsj DESC ";
		$result = $db->query($sql);
		return $result;
	}
	else
	{
		throw new Exception('请登录后查询。');
		exit;
	}
}

main();
?>
