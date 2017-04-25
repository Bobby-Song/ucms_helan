	function msgTips(errCode, title){
		var tle = title || '系统提示';
		$.messager.show({
			title:tle,
			msg:returnCodeMap(errCode)
		});
	}

	function returnCodeMap(code){
		var map = {
			"1000": "登录成功！",
			"-1000": "验证码错误！",
			"-1001": "接口错误！",
			"-1002": "用户名或密码错误！",
			"-1003": "用户已被删除！",
			"0": "操作成功",
			"10001": "登录成功",
			"20000": "登录失败",
			"20001": "操作失败",
			"20002": "手机号已被注册",
			"20003": "用户名或者密码错误",
			"20004": "验证码错误",
			"20005": "TOKEN错误或者已失效",
			"20006": "参数错误",
			"20007": "没有操作权限",
			"20008": "注册失败",
			"20009": "未知错误",
			"20010": "您已投过票，请勿重复投票",
			"20013": "未查询到相关数据",
			"20014": "数据重复",
			"20015": "数据未发生任何改变",
			"20016": "操作校验未通过",
			"20017": "用户名或者密码不能为空",
			"20018": "会话已过期或者无效",
			"20027": "请先选择小区，再操作",
		}  
		return map[code] || "未知";
	}
	function ajaxPage(jsonName){
		var realsrc = json_cache_path+jsonName+'.json';
		var xmlobj = new XMLHttpRequest();
		var listArray = '';
		xmlobj.onreadystatechange = function(){
			if(xmlobj.readyState == 4){
				if(xmlobj.status == 200 || xmlobj.status == 304){
					listArray = eval(xmlobj.responseText);
				}else{
					listArray = '';
				}
			}
		}
		
		xmlobj.open("GET", realsrc+"?"+Math.random(), false);
		xmlobj.send(null);
		
		return listArray;
	}

	//JS验证手机号
	function verifyDate(_str){
		var reg=/^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$/;
		if(!_str.match(reg)){
			return false;
		}else{
			return true; 
		}
	}
	function checkSubmitEmail(_email) {
		if (!_email.match(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) || _email == "") //验证email
		return false;
		else
		return true;
	} 

	function checkSubmitIp(_ip){     
		var reg =  /^([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/     
		return reg.test(_ip);     
	} 
	//IP解析为整型
	function _ip2int(ip) {
		var num = 0;
		ip = ip.split(".");
		num = Number(ip[0]) * 256 * 256 * 256 + Number(ip[1]) * 256 * 256 + Number(ip[2]) * 256 + Number(ip[3]);
		num = num >>> 0;
		return num;
	}
	//整型解析为IP地址
	function _int2iP(num) {
		var str;
		var tt = new Array();
		tt[0] = (num >>> 24) >>> 0;
		tt[1] = ((num << 8) >>> 24) >>> 0;
		tt[2] = (num << 16) >>> 24;
		tt[3] = (num << 24) >>> 24;
		str = String(tt[0]) + "." + String(tt[1]) + "." + String(tt[2]) + "." + String(tt[3]);
		return str;
	}	
	//创建datagrid数据表格
	function creatDataGrid(_selector,_pageSize,_toolbar,_url,_idField,_columns){
		_selector.datagrid({
			idField:_idField,
			fit: true,
			url:_url,
			pageSize:18,
			pageList:[18,30,45,60,75],
			fitColumns:true,
			loadMsg:'数据正在加载，请等待......',
			rownumbers:true,
			frozenColumns:[[
				{field:'chek',checkbox:true}
			]],
			columns:_columns,
			pagination:true,
			toolbar:_toolbar,
			onLoadSuccess:function(data){
				$("#Loadings").hide();
				gridData = data;
				if(data.total==0){
					$.messager.show({
						title:'提示信息',
						msg:'该条件下无数据信息'
					});
				}
			},
			onBeforeLoad: function () {
				$(this).datagrid('clearSelections');
			}
		});
	}
	
	//创建dialog对话框 
	function createDiaLog( _selector , _title , _width , _height , _iconCls , _buttons){
		_selector.dialog({
			modal : true,
			title : _title,
			width : _width,
			height : _height,
			iconCls : _iconCls,
			maximizable : true,
			closed : true,
			cache : false,
			bodyCls : "pass",
			buttons : _buttons
		});
	}

	// 数据提交
	function sendContent( _controller , _data ){
		$.post( _controller , _data , function(result){	
			$("#Loadings").hide();
			$.messager.show({
				title:result.title , 
				msg:result.message
			});
			closeTab( editsTitle );
		},'json');	
	}


	//数据增改载入TAB
	function edits(_title,_editsFlag){
		editsFlag = _editsFlag;
		editsTitle = _title;
		if( _editsFlag == 3 ){
			data_arr = selectorDataGrid.datagrid('getSelections');
			if(data_arr.length!=1){
				$.messager.show({
					title:'提示信息',
					msg:'请选择一条记录修改'
				});
				return;
			}
			if( data_arr[0].status == '上线' ){
				$.messager.show({
					title:'提示信息',
					msg:'该内容已在线，不能修改，请联系系统发布改为下线状态'
				});
				return;			
			}
		}
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==3){
			var onetab = tabs[2];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs( "close", titles );
		}
		$('#tree_tabs').tabs('add',{
			title : _title,
			href : editHtml,
			cache : false,
			closable : true,
			bodyCls : "pass"
		});
	}

	function activeEdits(_title,_editsFlag){
		editsFlag = _editsFlag;
		editsTitle = _title;
		if( _editsFlag == 3 ){
			data_arr = selectorDataGrid.datagrid('getSelections');
			if(data_arr.length!=1){
				$.messager.show({
					title:'提示信息',
					msg:'请选择一条记录修改'
				});
				return;
			}
		}
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==3){
			var onetab = tabs[2];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs( "close", titles );
		}
		$('#tree_tabs').tabs('add',{
			title : _title,
			href : activeNodeHtml,
			cache : true,
			closable : true,
			bodyCls : "pass"
		});
	}

    //数据增改载入TAB
    function editActive(_title,_editsFlag){
        editsFlag = _editsFlag;
        editsTitle = _title;
        if( _editsFlag == 3 ){
            active_arr = selectorDataGrids.datagrid('getSelections');
            if(active_arr.length!=1){
                $.messager.show({
                    title:'提示信息',
                    msg:'请选择一条记录修改'
                });
                return;
            }
            if( active_arr[0].status == '上线' ){
                $.messager.show({
                    title:'提示信息',
                    msg:'该内容已在线，不能修改，请联系系统发布改为下线状态'
                });
                return;
            }
        }
        var tree_tabs=$('#tree_tabs');
        var tabs=tree_tabs.tabs("tabs");
        /*if(tabs.length==3){
            var onetab = tabs[2];
            var titles = onetab.panel('options').tab.text();
            tree_tabs.tabs( "close", titles );
        }*/
        if(tabs.length==4){
            var onetab = tabs[3];
            var titles = onetab.panel('options').tab.text();
            tree_tabs.tabs( "close", titles );
        }
        $('#tree_tabs').tabs('add',{
            title : _title,
            href : activeEditHtml,
            cache : true,
            closable : true,
            bodyCls : "pass"
        });
    }


    function editComment(_title,_editsFlag){
        editsFlag = _editsFlag;
        editsTitle = _title;
        if( _editsFlag == 3 ){
            comment_arr = selectorDataGrid_comment.datagrid('getSelections');
            if(comment_arr.length!=1){
                $.messager.show({
                    title:'提示信息',
                    msg:'请选择一条记录修改'
                });
                return;
            }
            if( comment_arr[0].status == '1' ){
                $.messager.show({
                    title:'提示信息',
                    msg:'该评论已在展示，不能修改，请联系系统发布改为非展示状态'
                });
                return;
            }
        }
        var tree_tabs=$('#tree_tabs');
        var tabs=tree_tabs.tabs("tabs");
        if(tabs.length==4){
            var onetab = tabs[3];
            var titles = onetab.panel('options').tab.text();
            tree_tabs.tabs( "close", titles );
        }
        $('#tree_tabs').tabs('add',{
            title : _title,
            href : commentEditHtml,
            cache : true,
            closable : true,
            bodyCls : "pass"
        });
    }

    function listManagement(_title){
        editsTitle = _title;
        data_arr = selectorDataGrid.datagrid('getSelections');

        if(data_arr.length!=1){
            $.messager.show({
                title:'提示信息',
                msg:'请选择一条记录进行条目管理'
            });
            return;
        }
        var tree_tabs=$('#tree_tabs');
        var tabs=tree_tabs.tabs("tabs");
        if(tabs.length==3){
            var onetab = tabs[2];
            var titles = onetab.panel('options').tab.text();
            tree_tabs.tabs( "close", titles );
        }
        $('#tree_tabs').tabs('add',{
            title : _title,
            href : activeDataHtml,
            cache : true,
            closable : true,
            bodyCls : "pass"
        });
    }

	function commentManagement(_title){
		editsTitle = _title;
		data_arr = selectorDataGrid.datagrid('getSelections');

		if(data_arr.length!=1){
			$.messager.show({
				title:'提示信息',
				msg:'请选择一条记录进行评论管理'
			});
			return;
		}
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==3){
			var onetab = tabs[2];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs( "close", titles );
		}
		$('#tree_tabs').tabs('add',{
			title : _title,
			href : commentDataHtml,
			cache : true,
			closable : true,
			bodyCls : "pass"
		});
	}

	//数据审核
	function audits( _title , _auditType ){
		auditType =_auditType;
		editsTitle =_title;
		data_arr = selectorDataGrid.datagrid('getSelections');
		if(data_arr.length!=1){
			$.messager.show({
				title:'提示信息',
				msg:'请选择一条记录审核'
			});
			return;
		}
		if(data_arr[0].status != '已审核' && _auditType=='con_release'){
			$.messager.show({
				title:'提示信息',
				msg:'请选择状态为“已审核”的内容操作'
			});
			return;		
		}
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==3){
			var onetab = tabs[2];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs("close", titles);
		}
		$('#tree_tabs').tabs('add',{
			title:_title,
			href:controllerAuditHtml,
			cache:false,
			closable:true,
			bodyCls:"pass"
		});
	}

	function auditActive( _title , _auditType ){
		auditType =_auditType;
		editsTitle =_title;
		data_arr = selectorDataGrids.datagrid('getSelections');
		if(data_arr.length!=1){
			$.messager.show({
				title:'提示信息',
				msg:'请选择一条记录审核'
			});
			return;
		}
		if(data_arr[0].status != '已审核' && _auditType=='con_release'){
			$.messager.show({
				title:'提示信息',
				msg:'请选择状态为“已审核”的内容操作'
			});
			return;
		}
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==4){
			var onetab = tabs[3];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs("close", titles);
		}
		$('#tree_tabs').tabs('add',{
			title:_title,
			href:controllerAuditActiveHtml,
			cache:false,
			closable:true,
			bodyCls:"pass"
		});
	}


    function auditComment( _title ){
        var arr = selectorDataGrid_comment.datagrid('getSelections');
        if(arr.length<1){
            $("#Loadings").hide();
            $.messager.show({
                title:'提示信息',
                msg:'请至少选择一条记录操作'
            });
            return;
        }else{
            var str='';
            for(var i=0;i<arr.length;i++){
                str += arr[i].id+',';
            }
            str = str.substr(0,str.length-1);
            $.post(commentControllerAudits,{str:str},function( result ){
                $.messager.show({
                    title : result.title,
                    msg : result.message
                });
                selectorDataGrid_comment.datagrid('reload');
                selectorDataGrid_comment.datagrid('unselectAll');
                $("#Loadings").hide();
            },'json');
        }
    }

	//数据删除
	function deletes(){
		$("#Loadings").show();
		var arr = selectorDataGrid.datagrid('getSelections');
		if(arr.length<1){
			$("#Loadings").hide();
			$.messager.show({
				title:'提示信息',
				msg:'请至少选择一条记录操作'
			});
			return;
		}else{
			var f = true;
			$.each( arr , function( i , n ){
				if( arr[i].status == '上线' ){
					f = false;
					$("#Loadings").hide();
					return false;
				}
			});
			if( !f ){
				$.messager.show({
					title:'提示信息',
					msg:'选择中有在线播出内容，不能删除，请联系系统发布改为下线状态'
				});
				return;					
			}else{
				$.messager.confirm('系统提示','您确定要把选中条目删除吗?删除后将不可恢复！',function(b){
					if(b){
						var str='';
						for(var i=0;i<arr.length;i++){
							str += arr[i].id+',';
						}
						str = str.substr(0,str.length-1);
						$.post(controllerDelets,{str:str},function( result ){
							$.messager.show({
								title : result.title, 
								msg : result.message
							});
							selectorDataGrid.datagrid('reload');
							selectorDataGrid.datagrid('unselectAll');								
							$("#Loadings").hide();
						},'json');
					}
				});				
			}
		}
	}

    function activeDeletes(){
        $("#Loadings").show();
        var arr = selectorDataGrid.datagrid('getSelections');
        if(arr.length<1){
            $("#Loadings").hide();
            $.messager.show({
                title:'提示信息',
                msg:'请至少选择一条记录操作'
            });
            return;
        }else{
            var f = true;
            $.each( arr , function( i , n ){
                if( arr[i].content_ids != '' ){
                    f = false;
                    $("#Loadings").hide();
                    return false;
                }
            });
            if( !f ){
                $.messager.show({
                    title:'提示信息',
                    msg:'选择中有条目存在，不能删除，请先将条目清空'
                });
                return;
            }else{
                $.messager.confirm('系统提示','您确定要把该选项删除吗?删除后将不可恢复！',function(b){
                    if(b){
                        var str='';
                        for(var i=0;i<arr.length;i++){
                            str += arr[i].id+',';
                        }
                        str = str.substr(0,str.length-1);
                        $.post(activeControllerDeletes,{str:str},function( result ){
                            $.messager.show({
                                title : result.title,
                                msg : result.message
                            });
                            selectorDataGrid.datagrid('reload');
                            selectorDataGrid.datagrid('unselectAll');
                            $("#Loadings").hide();
                        },'json');
                    }
                });
            }
        }
    }


    function deleteActive(){
        $("#Loadings").show();
        var arr = selectorDataGrids.datagrid('getSelections');
        if(arr.length<1){
            $("#Loadings").hide();
            $.messager.show({
                title:'提示信息',
                msg:'请至少选择一条记录操作'
            });
            return;
        }else{
            var f = true;
            $.each( arr , function( i , n ){
                if( arr[i].status == '上线' ){
                    f = false;
                    $("#Loadings").hide();
                    return false;
                }
            });
            if( !f ){
                $.messager.show({
                    title:'提示信息',
                    msg:'选择中有在线播出内容，不能删除，请联系系统发布改为下线状态'
                });
                return;
            }else{
                $.messager.confirm('系统提示','您确定要把选中条目删除吗?删除后将不可恢复！',function(b){
                    if(b){
                        var str='';
                        for(var i=0;i<arr.length;i++){
                            str += arr[i].id+',';
                        }
                        str = str.substr(0,str.length-1);
                        $.post(controllerDeletActive,{str:str},function( result ){
                            $.messager.show({
                                title : result.title,
                                msg : result.message
                            });
                            selectorDataGrids.datagrid('reload');
                            selectorDataGrids.datagrid('unselectAll');
                            $("#Loadings").hide();
                        },'json');
                    }
                });
            }
        }
    }

    function deleteComment(){
        $("#Loadings").show();
        var arr = selectorDataGrid_comment.datagrid('getSelections');
        if(arr.length<1){
            $("#Loadings").hide();
            $.messager.show({
                title:'提示信息',
                msg:'请至少选择一条记录操作'
            });
            return;
        }else{
            var f = true;
            $.each( arr , function( i , n ){
                if( arr[i].status == '1' ){
                    f = false;
                    $("#Loadings").hide();
                    return false;
                }
            });
            if( !f ){
                $.messager.show({
                    title:'提示信息',
                    msg:'选择中有在线展示内容，不能删除，请联系系统发布改为非展示状态'
                });
                return;
            }else{
                $.messager.confirm('系统提示','您确定要把选中评论删除吗?删除后将不可恢复！',function(b){
                    if(b){
                        var str='';
                        for(var i=0;i<arr.length;i++){
                            str += arr[i].id+',';
                        }
                        str = str.substr(0,str.length-1);
                        $.post(controllerDeleteComment,{str:str},function( result ){
                            $.messager.show({
                                title : result.title,
                                msg : result.message
                            });
                            selectorDataGrid_comment.datagrid('reload');
                            selectorDataGrid_comment.datagrid('unselectAll');
                            $("#Loadings").hide();
                        },'json');
                    }
                });
            }
        }
    }

	//页面预览
	function previews(_selector){
        var _data_arr = _selector.datagrid('getSelections');
		if (_data_arr.length != 1) {
			$.messager.show({
				title:'提示信息',
				msg:'请选择一条记录预览'
			});
		} else {
			if (_data_arr[0].user_src == 'mobile') {
                $.messager.show({
                    title:'提示信息',
                    msg:'手机端添加的内容暂时无法预览'
                });
                return;
            }
            var htm = '#';
            var con_type = _data_arr[0].type;
            var url_path;
            var temp_url;
            var temp_dir = '/webRoot/330185000000/templet/';

            if (con_type == '监控') {
                $.messager.show({
                    title:'提示信息',
                    msg:'监控暂不支持在线预览'
                });
                return;
            } else if (con_type == '全屏视频') {
                if (_data_arr[0].video_path != '') {
                    url_path = '/video/'+_data_arr[0].video_path;
                } else {
                    $.messager.show({
                        title:'提示信息',
                        msg:'本视频非MP4文件，无法预览'
                    });
                    return;
                }
            } else {
                switch(con_type){
                    case "图文": // 图文
                        htm = 'content.htm';
                        break;
                    case "相册": // 相册
                        htm = 'album.htm';
                        break;
                    case "有图征询": // 有图征询
                        htm = 'consultPic.htm';
                        break;
                    case "无图征询": // 无图征询
                        htm = 'consult.htm';
                        break;
                    case "答题": // 答题
                        htm = 'answer.htm';
                        break;
                    case "窗口视频": // 窗口视频
                        htm = 'video2c.htm';
                        break;
                }
                temp_url = temp_dir + htm;
                if(htm == '#'){
                    return;
                }

                if(con_type == '有图征询' ||con_type == '无图征询' || con_type == '答题'){
                    url_path = temp_url+'?conId='+_data_arr[0].id;
                }else{
                    var js_path = '/webRoot/330185000000/preview_data/'+_data_arr[0].region_id+'/'+_data_arr[0].md5_id+'.js';
                    url_path = temp_url+'?jsPath='+encodeURIComponent(js_path);
                }
            }

			window.open(url_path);
		}
	}

	/**
	 * 根据条件跳转到索引对应的内容页
	 *
	 * @param data     当前点击焦点的对象数据
	 * @param backurl  相对于模板文件夹的当前页面地址
	 *
	 * @return void
	 */
	function goContentPage(data, backurl){
		var htm = '#';
		var type = data.type;
		var js_path = data.js_path;
        var temp_dir = '/webRoot/330185000000/templet/';

		if(type == '2'){
			//监控
			location.href = data.tv_play_url;
		}else if(type == '7'){
			//TODO 如何播放视频
			location.href = data.tv_play_url;
		}else{
			switch(type){
				case "1": // 图文
					htm = 'content.htm';
					break;
				case "3": // 相册
					htm = 'album.htm';
					break;
				case "4": // 有图征询
					htm = 'consultPic.htm';
					break;
				case "5": // 无图征询
					htm = 'consult.htm';
					break;
				case "6": // 答题
					htm = 'answer.htm';
					break;
				case "8": // 窗口视频
					htm = 'video2c.htm';
					break;
			}

			var url = '/webRoot/330185000000/templet/'+htm;

			if(htm == '#'){
				return;
			}

			if(type == 4 ||type == 5 || type == 6){
				location.href = url+'?conId='+data.id+'&backurl='+encodeURIComponent(backurl);
			}else{
				location.href = url+'?jsPath='+encodeURIComponent(js_path)+'&backurl='+encodeURIComponent(backurl);
			}
		}

	}

	//数据排序编辑
	function lineMove(_upOrDown){
		var row = selectorDataGrid.datagrid('getSelected');
		var froms = row.sort;
		var fromId = row.id;
		var index = selectorDataGrid.datagrid('getRowIndex', row);
		if(_upOrDown=='up' || _upOrDown=='down'){
			if(_upOrDown=='up'){
				if(index!=0){
					var todown = selectorDataGrid.datagrid('getData').rows[index-1];
				}else{
					$.messager.show({
						title:'系统提示' , 
						msg:'已处在顶端，不能上移'
					});
					return;
				}
			}else if(_upOrDown=='down'){
				var nums = selectorDataGrid.datagrid('getRows').length;
				if (index != nums - 1){
					var todown = selectorDataGrid.datagrid('getData').rows[index+1];
				}else{
					$.messager.show({
						title:'系统提示' , 
						msg:'已处在底端，不能下移'
					});
					return;
				}
			}
			var to = todown.sort;
			var toId = todown.id;
			$.post(controllerLineMove,{to:to,toId:toId,froms:froms,fromId:fromId},function(result){
				selectorDataGrid.datagrid('reload');
				selectorDataGrid.datagrid('unselectAll');
				$.messager.show({
					title:result.title , 
					msg:result.message
				});
			},'json');			
		}else if(_upOrDown=='top'){
			var nums = selectorDataGrid.datagrid('getRows').length;
			if(index != 0){
				var arr=selectorDataGrid.datagrid('getSelected');
				var id=arr.id;
				$.post(controllerTopMove,{id:id},function(result){
					selectorDataGrid.datagrid('reload');
					selectorDataGrid.datagrid('unselectAll');
					$.messager.show({
						title:result.title , 
						msg:result.message
					});
				},'json');
			}else{
				$.messager.show({
					title:'系统提示' ,  
					msg:'已处在顶端，不能置顶'
				});
				return;
			}
		}	
	}

	//查询
	function creatSearchBox( _dataGridSelector ){
		selectorSearBox.searchbox({   
			width:200,   
			searcher:function(value,name){ 
				if(name == "operate_time"){
					if(!verifyDate(value)){
						$.messager.alert('输入格式错误','请输入类似"2016-08-11"时间格式');
						return;
					}
				}else if(name=="status"){
					var state;
					var arr=['待审核','已审核','驳回','上线','下线'];
					var flag=false;
					$.each(arr,function(i,n){
						if(arr[i]==value){
							state=i+1;
							flag=true;
							return false;
						}
					});
					if(!flag){
						$.messager.alert('输入格式错误',"请选择输入'待审核','已审核','驳回','上线','下线'六种类型");
						return;
					}else{
						value = state;
					}
				}
				_dataGridSelector.datagrid('load',{name:name,value:value});
			},   
			menu:'#mm',   
			prompt:'请输入查询字段'
		});	
	}

	//建立次节点树
	function crearTree(){
		selectorTree.tree({
			lines:true,
			animate:true,
			url: urlTree,
			onClick:function(node){
				selector.datagrid('unselectAll');
				$(this).tree('toggle', node.target);
				types=node.attributes.types;
				var id=node.id;
				if(types=='reg'){
					var arr=$(this).tree('getChildren', node.target);
					if(arr.length==0){
						insertFlag=true;
					}
					regId=id;
					selector.datagrid('load' ,{regname:'con_area',regvalue:id});
					regName=node.text;
					selectorConManage.panel('setTitle',regName+" 内容管理");
					
				}else if(types=='node'){
					insertFlag=true;
					regNodeId=id;
					var arr=$(this).tree('getParent', node.target);
					nodeName=node.text;
					regName=arr.text;
					regId=arr.id;
					selector.datagrid('load',
						{rName:'con_area',rValue:regId,nName:'node_extend',nValue:id}
					);
					selectorConManage.panel('setTitle',regName+"-"+nodeName+" 内容管理");
				}
			}
		});
	}

	function onOrDownLine( _flag ){
        var arr = selectorDataGrid.datagrid('getSelections');
        if( arr.length!=1 ){
            $.messager.show({
                title:'提示信息',
                msg:'请选择一条记录操作'
            });
            return;
        }else{
            if( _flag=="on" ){
                if( arr[0].status == '上线' || arr[0].status == '已审核' ){
                    $.messager.show({
                        title:'提示信息',
                        msg:'该内容已经上线或未经过发布审核，不能上线操作'
                    });
                    return;
                }
                var status = '上线';
            }else if( _flag=="down" ){
                if( arr[0].status == '下线' || arr[0].status == '已审核' ){
                    $.messager.show({
                        title:'提示信息',
                        msg:'该内容已经下线或未经过发布审核，不能下线操作'
                    });
                    return;
                }
                var status = '下线';
            }
            var id = arr[0].id;
            $.post(controllerOnOrDownLine,
                {id:id,status:status},
                function(result){
                    selectorDataGrid.datagrid('reload');
                    selectorDataGrid.datagrid('unselectAll');
                    $.messager.show({
                        title:result.title ,
                        msg:result.message
                    });
                },
                'json');
        }
    }

    function onOrDownLineActive( _flag ){
        var arr = selectorDataGrids.datagrid('getSelections');
        if( arr.length!=1 ){
            $.messager.show({
                title:'提示信息',
                msg:'请选择一条记录操作'
            });
            return;
        }else{
            if( _flag=="on" ){
                if( arr[0].status == '上线' || arr[0].status == '已审核' ){
                    $.messager.show({
                        title:'提示信息',
                        msg:'该内容已经上线或未经过发布审核，不能上线操作'
                    });
                    return;
                }
                var status = '上线';
            }else if( _flag=="down" ){
                if( arr[0].status == '下线' || arr[0].status == '已审核' ){
                    $.messager.show({
                        title:'提示信息',
                        msg:'该内容已经下线或未经过发布审核，不能下线操作'
                    });
                    return;
                }
                var status = '下线';
            }
            var id = arr[0].id;
            $.post(controllerOnOrDownLineActive,
                {id:id,status:status},
                function(result){
                    selectorDataGrids.datagrid('reload');
                    selectorDataGrids.datagrid('unselectAll');
                    $.messager.show({
                        title:result.title ,
                        msg:result.message
                    });
                },
                'json');
        }
    }

	function createTab(_title,_controller){
		var tree_tabs=$('#tree_tabs');
		var tabs=tree_tabs.tabs("tabs");
		if(tabs.length==2){
			var onetab = tabs[1];
			var titles = onetab.panel('options').tab.text();
			tree_tabs.tabs("close", titles);
		}
		$('#tree_tabs').tabs('add',{
			title:_title,
			href:_controller,
			cache:false,
			closable:true,
			bodyCls:"pass"
		});	
	}

	function closeTab( _title ){
		$('#tree_tabs').tabs("close", _title);
		selectorDataGrid.datagrid('reload');
		selectorDataGrid.datagrid('unselectAll');
	}

	var chang_pw_html = "<table class='browser'><tr><td class='sidebar' colspan='2'>请输入8~15位数字特殊字符字母组合密码</td></tr><tr><td class='label'>初始密码</td><td><input id='oldPassWord' onpaste='return false;' type='password' name='oldPassWord' style='ime-mode:disabled'  class='myInp'>&nbsp;&nbsp;<span style='color:red'>*</span></td></tr><tr><td class='label'>输入新密码</td><td><input id='newPassWord' onpaste='return false;' type='password' name='newPassWord' style='ime-mode:disabled'  class='myInp'>&nbsp;&nbsp;<span style='color:red'>*</span></td></tr><tr><td class='label'>确认新密码</td><td><input id='aginPassWord' onpaste='return false;' type='password' name='aginPassWord' style='ime-mode:disabled'  class='myInp'>&nbsp;&nbsp;<span style='color:red'>*</span></td></tr></table>";
	
	// 更改密码
	function changePw( _controller , _logout , _dialogSelector , _flag ){
		_dialogSelector.dialog({
			modal : true,
			title : "更改密码",
			width : 480,
			height : 230,
			iconCls : "icon-edit",
			maximizable:true,
			closed : true,
			closable : _flag == 0 ? false : true ,
			cache:false,
			bodyCls:"pass",
			buttons:[
				{text:'提交',id:'regSubmit',handler:function(){
					var reg = /(?=.*[a-z])(?=.*\d)(?=.*[#@!~%^&*])[a-z\d#@!~%^&*]{8,16}/i;
					var newPassWord = $('#newPassWord').val();
					var aginPassWord = $('#aginPassWord').val();
					var oldPassWord = $('#oldPassWord').val();
					if( !newPassWord.match(reg)){
						$.messager.show({
							title: "系统提示", 
							msg: "请输入8~16位数字字母特殊字符组合密码"
						});
						return;						
					}
					if( newPassWord != aginPassWord ){
						$.messager.show({
							title: "系统提示", 
							msg: "两次密码输入不一致"
						});
						return;						
					}
					if( oldPassWord == "" ){
						$.messager.show({
							title: "系统提示", 
							msg: "请输入初始密码"
						});
						return;
					}
					$.post( _controller , {
						newPassWord:newPassWord,
						aginPassWord:aginPassWord,
						oldPassWord:oldPassWord
					},function(re){
						$.messager.show({
							title:re.status , 
							msg:re.message
						});
						if(re.error==1){
							$('#dialog').dialog('close');
							setTimeout('window.location.href ="' + _logout +'"', 2000);
						}
						
					},'json');
				}},
				{text:'重置',id:'regSubmits',handler:function(){
				}}
			]
		});
		_dialogSelector.dialog('open');		
	}
