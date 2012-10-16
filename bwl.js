var bwl;
var url;
var page;
var username;

//window.addEvent('domready', init);

function isLogin()
{
	if(Cookie.read("uid"))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function init()
{
	bwl = $("bwl_tbl");
	url = "bwl.php";
	username = Cookie.read("uchome_loginuser");
	
	if(username)
	{
		$("bwl_user").set("text", "欢迎您 " + username);
	}

	$('bwl_form_login').addEvent('submit', function(e){userLogin(e);});
	$('bwl_form_new').addEvent('submit', function(e){addNew(e);});
	$('bwl_add').addEvent('click', function(){showAddNew();});
	$('bwl_delete').addEvent('click', function(){delRow();});
	$('bwl_download').addEvent('click', function(){downloadText();});
	$('bwl_logout').addEvent('click', function(){userLogout();});

	$("bwl_main").setStyle("display", "block");
	$("bwl_new").setStyle("display", "none");
	$("bwl_login").setStyle("display", "none");
	
	var word;
	if(location.search.split("=").length < 2)
	{
		word = "";
	}
	else
	{
		word = decodeURIComponent(location.search.split("#")[0].split("=")[1]);
	}

	if(word != "")
	{
		addWord(word);	//添加生词
	}
	else
	{
		showBwl();	//显示界面
	}
}

function userLogin(e)
{
	e.stop();	//防止刷新页面
	var params = $("bwl_form_login").toQueryString();
	$("bwl_main").setStyle("display", "block");
	$("bwl_login").setStyle("display", "none");
	$("bwl_user").set("text", "欢迎您 " + $("bwl_form_login").getElement("input[name=username]").get("value"));
	sendRequest(params);
}

function userLogout()
{
	var params = "type=logout";
	sendRequest(params);
	Cookie.write("uid", "-1", {domain:"", path:"/"});
	Cookie.dispose("uchome_loginuser");
	showLogin();
	//window.location.href = "logout.htm";
}

function addNew(e)
{
	e.stop();	//防止刷新页面
	$("bwl_new").setStyle("display", "none");
	var form = $("bwl_form_new");
	var word = form.getElement("input[name=bwl_word]").get("value");
	var comment = form.getElement("textarea[name=bwl_comment]").get("value");
	addWord(word, comment);
}

function showAddNew()
{
	$("bwl_new").setStyle("display", "block");
}

function addWord(word, comment)
{
	if(comment == undefined)
	{
		comment = "";
	}

	if(isLogin())	//用户已经登录
	{
		var params = "word=" + word + "&comment=" + encodeChar(comment) + "&type=insert";
		sendRequest(params);
	}
	else
	{
		showLogin(word, comment);	//显示登录界面
	}
}

function showBwl(page)
{
	if(isLogin())	//用户已经登录
	{
		var params = "";
		if(page >= 1)
		{
			params += "page=" + page + "&";
		}

		params += "type=query";
		sendRequest(params);
	}
	else
	{
		showLogin("", "", page);
	}
}

function sendRequest(params)
{
	var req = new Request
	(
		{
			url: url,
			onSuccess: function(txt){showPannel(txt);},
			onFailure: function(txt){$('bwl_debug').set("html", 'The request failed.' + txt);}
		}
	);
	req.send(params);
}

function showLogin(word, comment, page)
{
	$("bwl_main").setStyle("display", "none");
	$("bwl_login").setStyle("display", "block");
	
	var form = $("bwl_form_login");
	if(typeof(word) != "undefined")
	{
		form.getElement("input[name=word]").set("value", word);
	}
	if(typeof(comment) != "undefined")
	{
		form.getElement("input[name=comment]").set("value", comment);
	}
	if(typeof(page) != "undefined")
	{
		form.getElement("input[name=page]").set("value", page);
	}
}

function showPannel(txt)
{
	var row;
	bwls = JSON.decode(txt, true);
	if(bwls == null)	//错误处理
	{
		$('bwl_debug').set("html", txt);
	}
	else if(bwls.status == "OK")
	{
		if(bwls.data)
		{
			clearTable();	//清空表格
			for(var i = 0; i < bwls.data.length; i++)
			{
				row = bwls.data[i];
				createRow(row.id, row.word, row.comment, row.adddate);	//插入行
			}
			setEvents();	//设置事件和CSS
			setPage(bwls.page, bwls.totalpage);	//设置页码
			page = bwls.page;
		}
	}
	else
	{
		if(bwls.errormessage.indexOf("登录") >= 0)	//登录失败
		{
			$("bwl_main").setStyle("display", "none");
			$("bwl_login").setStyle("display", "block");
		}
	}
	$('bwl_debug').set("html", bwls.errormessage);
}

function clearTable()
{
	if(bwl)
	{
		bwl.getChildren().dispose();
	}
}

function setPage(page, totalpage)
{
	var div = $("bwl_page");
	var str = new Array;
	for(var i = 1; i <= totalpage; i++)
	{
		if(page == i)
		{
			str.push("<span class='current'>"+ i +"</span>");
		}
		else
		{
			str.push("<a href='#' onclick='showBwl(" + i + ")'>" + i + "</a>");
		}
	}
	div.set("html", str.join(" "));
}

function showCommentText(cell)	//显示文本框
{
//debugger;
	var text = cell.getElement("textarea");
	var div = cell.getElement("div");
	div.setStyle("display", "none");
	text.setStyle("display", "block");
	text.focus();
}

function saveComment(cell)	//更新备注
{
	var text = cell.getElement("textarea");
	var div = cell.getElement("div");
	var id = text.id.split("_")[2];
	var comment = encodeChar(encodeURIComponent(text.value));
	var params = "id=" + id + "&comment=" + comment + "&type=update";
	var strDisplay = text.value.replace(/[\r\n]+/g, " ");
	if(strDisplay != "")
	{
		div.set("text", strDisplay.trim());
	}
	else
	{
		div.set("html", "点此输入备注");
	}
	text.setStyle("display", "none");
	div.setStyle("display", "block");
	sendRequest(params);
}



function createRow(id, word, comment, date)	//添加行
{
	if(bwl)	//备忘录有效时
	{
		var dl = new Element("dl");
		var dd = new Element("dd");
		var strong = new Element("strong");
		strong.set("html", "·");

		var divContent = new Element("div");
		var h4 = new Element("h4");
		var a = new Element("a");
		a.set("text", word);
		
		var span = new Element("span");
		span.set("html", date);
		var chk = new Element("input");
		chk.set("type", "checkbox").set("id", "bwl_chk_" + id);
		chk.set("type", "checkbox").set("name", "delete_list");

		var divCommentGroup = new Element("div");
		var divComment = new Element("div");
		var strDisplay = comment.replace(/[\r\n]+/g, " ");
		if(strDisplay != "")
		{
			divComment.set("text", strDisplay.trim());
		}
		else
		{
			divComment.set("html", "点此输入备注");
		}

		var text = new Element("textarea");
		text.set("id", "bwl_text_" + id).set("value", comment);
		
		dd.inject(dl);	//将dd放入dl中
		strong.inject(dd);
		divContent.inject(dd);
		h4.inject(divContent);
		a.inject(h4);
		span.inject(h4);
		chk.inject(span);
		divCommentGroup.inject(divContent);
		divComment.inject(divCommentGroup);
		text.inject(divCommentGroup);
		
		dl.inject(bwl);
	}
}

function setEvents()
{
	if(bwl && bwl.get("html") != "")
	{
		bwl.getElements("dl").each(
			function(e, index)
			{
				e.addEvent("mouseover", function(){bwl.getElements("dl").removeClass("over");this.addClass("over")});
				if(index == 0)
				{
					e.addClass("over");	//初始化，选中第一行
				}
				
				var a = e.getElement("a");
				a.set("href", "http://www.zdic.net/search/?q=" + encodeURIComponent(a.get("text")));
				a.set("target", "_blank");
				
				var divCommentGroup = e.getElement("div").getElement("div");
				var divComment = divCommentGroup.getElement("div");
				divComment.addEvent("click", function(){showCommentText(divCommentGroup)});
				divComment.addClass("bz");
				
				var text = divCommentGroup.getElement("textarea");
				text.setStyle("display", "none");
				text.addEvent("blur", function(){saveComment(divCommentGroup)});

			}
		);
	}
}

function delRow()	//删除行
{
	var chks;
	var id = new Array();
	var params;
	chks = bwl.getElements("input[checked]").get("id");
	for(var i = 0; i < chks.length; i++)
	{
		id.push(chks[i].split("_")[2]);
	}
	if(page > 0)
	{
		params = "id=" + id.join("|") + "&page=" + page + "&type=delete";
	}
	else
	{
		params = "id=" + id.join("|") + "&page=1&type=delete";
	}

	sendRequest(params);
}

function test()
{
	$("bwl_main").setStyle("display", "block");
	$("bwl_new").setStyle("display", "block");
	$("bwl_login").setStyle("display", "block");
}

function encodeChar(str)
{
	return str.replace(/'/g, "''");
}

function selectAllList()
{
	bwl.getElements("input").each
	(
		function(e, index)
		{
			if (e.name == "delete_list")
            {
				e.checked = $('control_all').checked;
            }
        }
	);
}

function downloadText()
{
	window.location.href = "download.php";
}