var url = 'bwl.php';
var page;

$(document).ready(function(){
	if(IsLogin()){
		$('#bwl_user').text('欢迎您 ' + $.cookie('uchome_loginuser'));
		ShowBwl();	//显示界面
	}
	else{
		ShowLogin('', '');
	}

	$('#bwl_new').hide();

	$('#bwl_form_new').bind('submit', function(){
		$('#bwl_new').hide();
		var form = $('#bwl_form_new');
		var word = $('#bwl_form_new input[name=bwl_word]').val();
		var comment = $('#bwl_form_new input[name=bwl_comment]').val();
		AddWord(word, comment);
		return false;
	});
	$('#bwl_add').bind('click', function(){ShowAddNew();});
	$('#bwl_delete').bind('click', function(){DelRow();});
	$('#bwl_download').bind('click', function(){DownloadText();});
	$('#bwl_logout').bind('click', function(){UserLogout();});
	
	var word = window.decodeURIComponent(window.location.search.split('=')[1]);

	if(word != 'undefined' && word != '')
	{
		AddWord(word);	//添加生词
	}

});

function IsLogin()
{
	return ($.cookie('uid')) ? true : false;
}

function UserLogout()
{
	var params = 'type=logout';
	SendRequest(params);
	$.removeCookie('uid');
	$.removeCookie('uchome_loginuser');
	//$.cookie('bwl_login', false);
	//ShowLogin();
}

function ShowAddNew()
{
	$('#bwl_new').show();
}

function AddWord(word, comment)
{
	if(comment == undefined)
	{
		comment = '';
	}

	if(IsLogin())	//用户已经登录
	{
		var params = 'word=' + word + '&comment=' + EncodeChar(comment) + '&type=insert';
		SendRequest(params);
	}
	else
	{
		ShowLogin(word, comment);	//显示登录界面
	}
}

function ShowBwl(page)
{
		var username = $.cookie('uchome_loginuser');
		if(username){
			$('#bwl_user').text('欢迎您 ' + username);
		}
		var params = '';
		if(page && page >= 1)
		{
			params += 'page=' + page + '&';
		}

		params += 'type=query';
		SendRequest(params);
}

function SendRequest(params)
{
	$.ajax({
		type: 'POST',
		url: url,
		data: params,
		success: function(txt){ShowPannel(txt);},
		error: function(XMLHttpRequest){
			$('#bwl_debug').html('The request failed.' + XMLHttpRequest.responseText);
		}
	});
}

function ShowLogin(word, comment, page)
{
	$('#bwl_main').hide();
	$('#bwl_login').show();
	
	var form = $('#bwl_form_login');
	if(typeof(word) != 'undefined')
	{
		form.getElement('input[name=word]').set('value', word);
	}
	if(typeof(comment) != 'undefined')
	{
		form.getElement('input[name=comment]').set('value', comment);
	}
	if(typeof(page) != 'undefined')
	{
		form.getElement('input[name=page]').set('value', page);
	}
}

function ShowPannel(txt)
{
	var row;
	try
	{
		bwls = $.parseJSON(txt);
	}
	catch(e)
	{
		//错误处理
		$('#bwl_debug').html(txt);
		return;
	}
	
	if(bwls.status == 'OK')
	{
		if(bwls.data)
		{
			ClearTable();	//清空表格
			for(var i = 0; i < bwls.data.length; i++)
			{
				row = bwls.data[i];
				CreateRow(row.id, row.word, row.comment, row.adddate);	//插入行
			}
			SetEvents();	//设置事件和CSS
			SetPage(bwls.page, bwls.totalpage);	//设置页码
			page = bwls.page;
		}
		$('#bwl_login').hide();
		$('#bwl_main').show();
	}
	else
	{
		if(bwls.errormessage.indexOf('登录') >= 0)	//登录失败
		{
			$('#bwl_main').hide();
			$('#bwl_login').show();
		}
	}
	$('#bwl_debug').html(bwls.errormessage);
}

function ClearTable()
{
	$('#bwl_table').empty();
}

function SetPage(page, totalpage)
{
	var div = $('#bwl_page');
	var str = [];
	for(var i = 1; i <= totalpage; i++)
	{
		if(page == i)
		{
			str.push('<span class="current">'+ i +'</span>');
		}
		else
		{
			str.push('<a href="#" onclick="ShowBwl(' + i + ')">' + i + '</a>');
		}
	}
	$('#bwl_page').html(str.join(' '));
}

function ShowCommentText(cell)	//显示文本框
{
//debugger;
	var text = cell.getElement('textarea');
	var div = cell.getElement('div');
	div.setStyle('display', 'none');
	text.setStyle('display', 'block');
	text.focus();
}

function SaveComment(cell)	//更新备注
{
	var text = cell.getElement('textarea');
	var div = cell.getElement('div');
	var id = text.id.split('_')[2];
	var comment = EncodeChar(encodeURIComponent(text.value));
	var params = 'id=' + id + '&comment=' + comment + '&type=update';
	var strDisplay = text.value.replace(/[\r\n]+/g, ' ');
	if(strDisplay != '')
	{
		div.set('text', strDisplay.trim());
	}
	else
	{
		div.set('html', '点此输入备注');
	}
	text.setStyle('display', 'none');
	div.setStyle('display', 'block');
	SendRequest(params);
}



function CreateRow(id, word, comment, date)	//添加行
{
	var strHTML = '<dl>';
	strHTML += '<dd>';
	strHTML += '  <span class="strong">·</span>';
	strHTML += '  <div><h4>';
	strHTML += '	<a href="http://www.zdic.net/search/?q=' + window.encodeURIComponent(word) + '" target="_blank">' + word + '</a>';
	strHTML += '	<span>';
	strHTML += date + '<input type="checkbox" id="bwl_chk_' + id + '" name="delete_list" />';
	strHTML += '	</span>';
	strHTML += '  </h4></div>';
	strHTML += '  <div>';
	strHTML += '    <div class="bz" id="bwl_text_' + id + '">' + comment + '</div>';
	strHTML += '  </div>';
	strHTML += '</dd>';
	strHTML += '</dl>';

	$('#bwl_table').append(strHTML);
}

function SetEvents()
{
	$('#bwl_table dl').hover(
		function(){
			$(this).addClass('over');
		},
		function(){
			$(this).removeClass('over');
		}
	);

	//正文区域
	$('#bwl_table dl dd div div.bz').each(function(index){
		$(this).click(function(){
			//设置为可编辑
			$(this).attr('contenteditable', 'true');
			$(this).addClass('editable');
		});
		$(this).blur(function(){
			//改为显示状态，并提交数据
			$(this).attr('contenteditable', 'false');
			$(this).removeClass('editable');
		});
	});
}

function DelRow()	//删除行
{
	var chks;
	var id = new Array();
	var params;
	chks = bwl.getElements('input[checked]').get('id');
	for(var i = 0; i < chks.length; i++)
	{
		id.push(chks[i].split('_')[2]);
	}
	if(page > 0)
	{
		params = 'id=' + id.join('|') + '&page=' + page + '&type=delete';
	}
	else
	{
		params = 'id=' + id.join('|') + '&page=1&type=delete';
	}

	SendRequest(params);
}

function Test()
{
	$('#bwl_main').setStyle('display', 'block');
	$('#bwl_new').setStyle('display', 'block');
	$('#bwl_login').setStyle('display', 'block');
}

function EncodeChar(str)
{
	return str.replace(/'/g, '""');
}

function SelectAllList()
{
	bwl.getElements('input').each
	(
		function(e, index)
		{
			if (e.name == 'delete_list')
            {
				e.checked = $('#control_all').checked;
            }
        }
	);
}

function DownloadText()
{
	window.location.href = 'download.php';
}

//javascript:void((function(){UserLogout()})())