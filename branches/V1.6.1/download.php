<?php
define('DB_CHARSET',                   'latin1');   //编码
include './MySql.php';
require_once '../config/config_global.php';
require_once '../config/config_ucenter.php';
require_once '../uc_client/client.php';

//获取用户登录信息
if(!empty($_COOKIE['auth']))
{
	list( , , $uid) = explode("\t", uc_authcode($_COOKIE[$config['cookiepre'].'auth'], 'DECODE'));
}
else if(!empty($_COOKIE['uid']))
{
	$uid = $_COOKIE['uid'];
}
else
{
	$uid = '';
}

$db = new MySql($_config['db']['1']['dbhost'], $_config['db']['1']['dbuser'], $_config['db']['1']['dbpw'], $_config['db']['1']['dbname']);

function main()
{
	$downloadfile = 'memorandum.txt';
	if( $GLOBALS['uid'] > 0 )
	{
		$table = sqlQuery();
		header("Content-type:text/plain; charset=utf-8");
		header("Content-disposition: attachment; filename=$downloadfile");
		foreach($table as $row)
		{
			echo "词条：$row->word\r\n";
			echo "备注：$row->comment\r\n";
			echo "时间：$row->adddate\r\n";
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
