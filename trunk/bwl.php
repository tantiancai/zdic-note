<?php
require_once '../include/common.inc.php';
require_once DISCUZ_ROOT.'./uc_client/client.php';

include("mysql2json.class.php");

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

function main()
{
	$err = "";
	$data;
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
					$data = sqlQuery();	//查询
				}
				else
				{
					$err = "登录失败，请检查用户名和密码是否正确。";
				}
				break;

			case "query":	//查询
				$data = sqlQuery();
				break;

			case "insert":	//插入
				sqlInsert();	//插入
				$data = sqlQuery();		//查询
				break;

			case "update":	//更新
				sqlUpdate();	//更新
				break;

			case "delete":	//删除
				sqlDelete();	//删除
				$data = sqlQuery();		//查询
				break;
		}
	}
	catch(Exception $e)
	{
		$err = $e->getMessage();
	}

	if($err == "")
	{
		$status = '"status" : "OK"';
	}
	else
	{
		$status = '"status" : "fail"';
		$GLOBALS['errormessage'] = $err;
	}
//	if($GLOBALS['errormessage'] == "")
//	{
//		$GLOBALS['errormessage'] = "查询成功。";
//	}
	$errormessage = '"errormessage" : "'.$GLOBALS['errormessage'].'"';
	$page = '"page" : "'.$GLOBALS['page'].'"';
	$totalpage = '"totalpage" : "'.$GLOBALS['totalpage'].'"';
	if($data != "")
	{
		echo "{".$status.",".$errormessage.",".$page.",".$totalpage.",".$data."}";
	}
	else
	{
		echo "{".$status.",".$errormessage.",".$page.",".$totalpage."}";
	}
}

function login()
{
	list($uid, $username, $password, $email) = uc_user_login($GLOBALS['username'], $GLOBALS['password']);
	$db = $GLOBALS['db'];
	if($uid > 0)
	{
		$member = $db->fetch_first("SELECT m.uid AS discuz_uid, m.username AS discuz_user, m.password AS discuz_pw, m.secques AS discuz_secques
				FROM ".$GLOBALS['tablepre']."members m
				WHERE m.uid='$uid'");

		if(!$member)
		{
			throw new Exception('您需要需要激活该帐号，请进入论坛激活。');
			exit;
		}

		extract($member);

		$cookietime = 2592000;
		dsetcookie('cookietime', $cookietime, 31536000);
		dsetcookie('auth', authcode("$discuz_pw\t$discuz_secques\t$discuz_uid", 'ENCODE'), $cookietime, 1, true);
		dsetcookie('uid', $uid, $cookietime);

		//生成同步登录的代码
		$ucsynlogin = uc_user_synlogin($uid);
		$GLOBALS['uid'] = $uid;
		return true;
	}
	else
	{
		return false;
	}
}



function getTotalPage($uid)
{
	$db = $GLOBALS['db'];
	$result = $db->query("SELECT count(*) FROM zdic_bwl WHERE userid = '$uid'");
	$row = mysql_fetch_array($result);
	return ceil($row[0] / $GLOBALS['rows']);
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
					bwbz as comment,
					userid
				FROM	zdic_bwl
				WHERE	userid = '$uid' ORDER BY	bwsj DESC
				LIMIT	".($page - 1) * $GLOBALS['rows'].", ".$GLOBALS['rows'];
		$result = $db->query($sql);
		$num = mysql_affected_rows();
		$objJSON = new mysql2json();
		return ' "data" : '.$objJSON->getJSON($result, $num);
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
				$db->query($sql);
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
	$result = $db->query("SELECT count(*) FROM zdic_bwl WHERE userid='$uid' AND bwcy='$word'");
	$row = mysql_fetch_array($result);
	return ($row[0] > 0);
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
		$db->query($sql);
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
			$db->query($sql);
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


//echo date('Y-m-d H:i:s');
main();

function test()
{
}
?>
