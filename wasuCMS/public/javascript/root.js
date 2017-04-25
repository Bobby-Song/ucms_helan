	
	/*******************ADMINNODE****************************/
	function creatTree( _module ){
		$.each( node_data , function( i , n ){
			$('#accordion_menu').accordion('add',{
				id : n.id,
				title : n.text,
				selected : i == 0?true:false,
				iconCls : n.attributes.icon,
				content : "<div style='padding:10px;overflow:auto;'><ul name='"+n.text+n.id+"'></ul></div>"
			});
			
			$("ul[name='"+n.text+n.id+"']").tree({
				data : node_data[i].children,
				lines : true,
				animate : true,
				onClick : function ( node ){
					if( node.attributes.methods != '' ){
						var path = _module + n.attributes.controller + '/' + node.attributes.methods;
						var tabs = $('#tree_tabs').tabs("tabs");
						removeTabs( $('#tree_tabs') );
						showTabs( $('#tree_tabs') , node.text , path );
					}
				}
			});
		});
	}
	
	function removeTabs( _tabSelector ){
		var tab_arr = _tabSelector.tabs("tabs");
		if( tab_arr.length > 1 ){
			var arr = [];
			$.each( tab_arr , function(i,n){
				arr[i] = tab_arr[i].panel( 'options' ).tab.text();
			});
			$.each( arr , function(i,n){
				if(i!=0)
				_tabSelector.tabs( "close" , arr[i] );
			});
		}
	}
	
	function showTabs( _tabSelector , _text , _path ){
		if( _tabSelector.tabs( 'exists' , _text ) ){
			_tabSelector.tabs( 'select' , _text );
		}else{
			_tabSelector.tabs('add',{
				title : _text,
				cache : false,
				href : _path,
				closable : true,
				bodyCls : 'pass'
			});
		}		
	}
	
	/*******************CMSNODE****************************/	
	
	var node_id;
	var node_code;
	var node_name;
	var node_type;
	var node_level;
	var region_name;
	function buildRegionTree( _module ){
		$("#tree").tree({
			data : node_data,
			lines : true,
			animate : true,
			onClick : function ( node ){
				node_id = node.id;
				node_code = node.attributes.node_code;
				node_name = node.text;
				node_type = node.attributes.type;
				node_level = node.attributes.level;

				if( node.attributes.is_leaf == 'yes' ){
					var b = getPermissions( nodes , node_id );
					if( !b ){
						$.messager.show({
							title:"系统提示" , 
							msg:"您无此栏目操作权限！"
						});	
						return;						
					}
					region_name = node_data[0].text;
					if( node_level != 0 && node_level != code_level ){
						var l;
						if( node_level == 1 ) l = '市';
						if( node_level == 2 ) l = '县/区/市';
						if( node_level == 3 ) l = '乡镇/街道';
						if( node_level == 4 ) l = '社区/行政村';
						if( !getRegion() ){
							$.messager.show({
								title:"系统提示" , 
								msg:"当前栏目属"+l+"级栏目，请操作桌面/区域选择"+l
							});
							return;
						} 
					}else{
						region_id = rid;
					}
					var f = getLevel();
					if( !f ){
						$.messager.show({
							title:"系统提示" , 
							msg:"当前栏目区域层级与用户所属区域不匹配！"
						});
						return;						
					}

					var type = node.attributes.type;
					console.log(type);
					if( type == 'column' || type == 'listContent' ) {
						var path = _module + 'Content/dataHtml';
					} else if (type == 'activeNode') {
                        var path = _module + 'Content/activeHtml';
                    } else {
                        var path = _module + 'Content/homeHtml';
                    }
					removeTabs( $('#tree_tabs') );
					showTabs( $('#tree_tabs') , node.text , path );
				}
			}
		});		
	}
	
	function getRegion(){
		var reg_flag = true;
		$.ajax({
			url: '/wasuCMS/public/user/'+user_id+'.json?'+Math.random(),
			dataType: 'json',
			async: false,
			success: function( result ){
				if( result.text ){
					region_name = result.parent.text + result.text;
					if( node_level == result.level ){
						region_id = result.code;
					}else if( node_level == result.parent.level ){
						region_id = result.parent.code;
                        region_name = result.parent.text
					}else{
						reg_flag = false;
					}
				}else{
					reg_flag = false;
				}
			}
		});	
		return reg_flag;
	}
	
	//获取用户权限操作对应栏目版块
	function getPermissions( _nodes , _node_id ){
		if( _nodes == 0 || _nodes == '' ){
			return true;
		}else{
			_node_id = _node_id.toString();
			var _arr = _nodes.split(",");
			var _num = _arr.indexOf( _node_id );
			if( _num != -1 )
			return true;
			else
			return false;
		}
	}
	
	function getLevel(){
		if( code_level == 0 ){
			return true;
		}else{
			if( code_level != node_level){
				return false;
			}else{
				region_name = code_name;
				region_id = code;
				return true;				
			}
		}
	}