<?php
define('DB_CHARSET',                   'latin1');   //编码
include './MySql.php';
require_once '../config/config_global.php';
require_once '../config/config_ucenter.php';
require_once '../uc_client/client.php';

//获取用户登录信息
if(!empty($_COOKIE[$config['cookiepre'].'auth']))
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

//将传递过来的参数进行赋值
$id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : "";
$word = isset($_REQUEST["word"]) ? $_REQUEST["word"] : "";
$comment = isset($_REQUEST["comment"]) ? $_REQUEST["comment"] : "";
$page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : "";
$username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : "";
$password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "";
$totalpage;
$rows = 20;	//每页数据数目
$errormessage;
$db = new MySql($_config['db']['1']['dbhost'], $_config['db']['1']['dbuser'], $_config['db']['1']['dbpw'], $_config['db']['1']['dbname']);

//返回结果类
class MsgReturn
{
    var $status = '';               //状态
    var $errormessage = '';         //提示信息内容
    var $page = -1;              	//页码
    var $totalpage = -1;			//总页数
    var $data;						//数据
}

function main()
{
	$err = "";
	$msg = new MsgReturn();
	try
	{
		switch($GLOBALS['type'])
		{
			case "login":	//登录
				if(login() == true)
				{
					if($GLOBALS['word'] != "")
					{
						sqlInsert();	//插入
					}
					//$msg->data = sqlQuery();	//查询
					return;
				}
				else
				{
					$err = "登录失败，请检查用户名和密码是否正确。";
				}
				break;

			case "logout":	//退出
				logout();
				break;

			case "query":	//查询
				$msg->data = sqlQuery();
				break;

			case "insert":	//插入
				sqlInsert();	//插入
				$msg->data = sqlQuery();		//查询
				break;

			case "update":	//更新
				sqlUpdate();	//更新
				break;

			case "delete":	//删除
				sqlDelete();	//删除
				$msg->data = sqlQuery();		//查询
				break;
			default:
				$err = 'type错误：'.$GLOBALS['type'];
		}
	}
	catch(Exception $e)
	{
		$err = $e->getMessage();
	}

	if($err == "")
	{
		$msg->status = 'OK';
	}
	else
	{
		$msg->status = 'fail';
		$GLOBALS['errormessage'] = $err;
	}
	$msg->errormessage = $GLOBALS['errormessage'];
	$msg->page = $GLOBALS['page'];
	$msg->totalpage = $GLOBALS['totalpage'];
	echo json_encode($msg);
}

function login()
{
	list($uid, $username, $password, $email) = uc_user_login($GLOBALS['username'], $GLOBALS['password']);
	if($uid > 0)
	{
		$cookietime = 2592000;
		dsetcookie('cookietime', $cookietime, 31536000);
		dsetcookie('auth', uc_authcode("$discuz_pw\t$discuz_secques\t$discuz_uid", 'ENCODE'), $cookietime, 1, false);
		dsetcookie('uid', $uid, $cookietime, 0);
		dsetcookie('uchome_loginuser', $username, $cookietime, 0);

		//生成同步登录的代码
		$ucsynlogin = uc_user_synlogin($uid);
		$strHTML = '<!DOCTYPE html>
		<html>
		<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta charset="utf-8">
		<meta http-equiv="refresh" content="5; url=index.htm" />
		<title>正在登录</title>
		'.$ucsynlogin.'
		<script type="text/javascript">
			window.onload = function(){
				window.location.href = "index.htm";
			}
		</script>
		</head>
		<body>
			登录完成，正在跳转。如果无法自动跳转，请点击如下链接：<br />
			<a href="index.htm">index.htm</a>
		</body>
		</html>';
		echo $strHTML;
		$GLOBALS['uid'] = $uid;
		return true;
	}
	else
	{
		return false;
	}
}

function logout()
{

	uc_user_synlogout();
	dsetcookie('auth', '', -1, 1, false);
	dsetcookie('uid', '', -1, 0, false);
	dsetcookie('uchome_loginuser', '', -1, 0, false);
	$strHTML = '<!DOCTYPE html>
	<html>
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta charset="utf-8">
	<meta http-equiv="refresh" content="5; url=index.htm" />
	<title>正在退出</title>
	<script type="text/javascript">
		window.onload = function(){
			window.location.href = "index.htm";
		}
	</script>
	</head>
	<body>
		退出成功，正在跳转。如果无法自动跳转，请点击如下链接：<br />
		<a href="index.htm">index.htm</a>
	</body>
	</html>';
	echo $strHTML;
}

function getTotalPage($uid)
{
	$db = $GLOBALS['db'];
	$result = $db->query("SELECT count(*) AS rows FROM zdic_bwl WHERE userid = '$uid'");
	$row = $result[0]->rows;
	return ceil($row / $GLOBALS['rows']);
}


function sqlQuery()
{
	$db = $GLOBALS['db'];
	$uid = $GLOBALS['uid'];
	$page = $GLOBALS['page'];

	if(($page == "") || ($page <= 0 ))	//没指定页码或页码有误
	{
		$GLOBALS['page'] = $page = 1;	//显示第一页
	}

	if($uid > 0)
	{	
		$GLOBALS['totalpage'] = getTotalPage($uid);
		$num=0;
		$sql = "SELECT	id,
					bwcy as word,
					DATE_FORMAT(bwsj, '%y/%c/%e') as adddate,
					REPLACE(bwbz, '\"', '\\\\\"') as comment,
					userid
				FROM	zdic_bwl
				WHERE	userid = '$uid' ORDER BY	bwsj DESC
				LIMIT	".($page - 1) * $GLOBALS['rows'].", ".$GLOBALS['rows'];
		$result = $db->query($sql);
		return $result;
	}
	else
	{
		throw new Exception('请登录后查询。');
		exit;
	}
}

function sqlInsert()
{
	$db = $GLOBALS['db'];
	$uid = $GLOBALS['uid'];
	$word = $GLOBALS['word'];
	$comment = $GLOBALS['comment'];

	if($uid > 0)
	{
		$words = explode("|", $word);
		$insertSuccess = 0;
		foreach($words as $tword)
		{
			if(isExist($uid, $tword) != true)
			{
				$sql = "INSERT INTO	zdic_bwl(bwcy, bwsj, bwbz, userid)
						VALUES(
							'".$tword."',
							'".date('Y-m-d H:i:s')."',
							'".$comment."',
							'".$uid."')";
				$db->insert($sql);
				$insertSuccess++;
			}
		}
		if($insertSuccess == 0)
		{
			$GLOBALS['errormessage'] = '该词条已存在。';
		}
		else if($insertSuccess == sizeof($words))
		{
			$GLOBALS['errormessage'] = '插入成功。';
		}
		else
		{
			$GLOBALS['errormessage'] = '有若干词条已存在。';
		}
	}
	else
	{
		throw new Exception('请登录后插入。');
		exit;
	}
}

function isExist($uid, $word)
{
	$db = $GLOBALS['db'];
	$result = $db->query("SELECT count(*) AS num FROM zdic_bwl WHERE userid='$uid' AND bwcy='$word'");
	return ($result[0]->num > 0);
}

function sqlUpdate()
{
	$db = $GLOBALS['db'];
	$id = $GLOBALS['id'];
	$uid = $GLOBALS['uid'];
	$word = $GLOBALS['word'];
	$comment = $GLOBALS['comment'];

	if($uid > 0)
	{
		$sql = "UPDATE zdic_bwl
				SET
					bwbz='".$comment."'
				WHERE
					(id='".$id."'
				OR	bwcy='".$word."')
				AND	userid='".$uid."'";
		$db->update($sql);
		$GLOBALS['errormessage'] = '更新成功。';
	}
	else
	{
		throw new Exception('请登录后更新。');
		exit;
	}
}

function sqlDelete()
{
	$db = $GLOBALS['db'];
	$id = $GLOBALS['id'];
	$uid = $GLOBALS['uid'];

	if($uid > 0)
	{
		if($id != "")
		{
			$ids = explode("|", $id);
			$sql = 'DELETE FROM zdic_bwl
					WHERE
						id IN (';
			foreach($ids as $tid)
			{
				$sql .= "'".$tid."',";
			}
			$sql .= "'-1')
					AND	userid='".$uid."'";
			//echo $sql;
			$db->delete($sql);
			$GLOBALS['errormessage'] = '删除成功。';
		}
		else
		{
			throw new Exception('删除失败，未找到id。');
			exit;
		}
	}
	else
	{
		throw new Exception('请登录后删除。');
		exit;
	}
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {

	global $_config;

	$config = $_config['cookie'];

	$_config['cookie'][$var] = $value;
	$var = ($prefix ? $config['cookiepre'] : '').$var;
	$_COOKIE[$var] = $value;

	if($value == '' || $life < 0) {
		$value = '';
		$life = -1;
	}

	if(defined('IN_MOBILE')) {
		$httponly = false;
	}

	$life = $life > 0 ? time() + $life : ($life < 0 ? time() - 31536000 : 0);
	$path = $httponly && PHP_VERSION < '5.2.0' ? $config['cookiepath'].'; HttpOnly' : $config['cookiepath'];

	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
	if(PHP_VERSION < '5.2.0') {
		setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure);
	} else {
		setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure, $httponly);
	}

}


//echo date('Y-m-d H:i:s');
main();


function test()
{
	echo $GLOBALS['type'];
}
?>
