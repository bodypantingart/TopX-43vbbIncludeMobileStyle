/*======================================================================*\
|| #################################################################### ||
|| # vt.Lai vBB TopX 4.3                                              # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2010-2012 Vu Thanh Lai. All Rights Reserved.          # ||
|| # Please do not remove this comment lines.                         # ||
|| # -------------------- LAST MODIFY INFOMATION -------------------- # ||
|| # Last Modify: 03-10-2012 03:00:00 AM by: Vu Thanh Lai             # ||
|| # Please do not remove these comment line if use my code or a part # ||
|| #################################################################### ||
\*======================================================================*/

var vtlai_topx_curids='all';
var vtlai_expand_status=0;

function backgroundAlert(r,g,b)
{
	var next=false;
	if(r<250)
	{
		r++;
		next=true;
	}
	if(g<250)
	{
		g++;
		next=true;
	}
	if(b<250)
	{
		b++;
		next=true;
	}
	jQuery('#vtlai_topx_list').css('background-color','rgb('+r+','+g+','+b+')');
	if(next)
	{
		setTimeout('backgroundAlert('+r+','+g+','+b+')',10);
	}
}

function loadTopX()
{
	jQuery('#vtlai_topx_status').removeClass('vtlai_stopload');
	jQuery('#vtlai_topx_status').addClass('vtlai_loading');
	jQuery.ajax({
	  type: 'POST',
	  url: 'vtlai_topx.php',
	  data: {'do':'vtlai_topx',size:TOPX_DEFAULTSIZE,cachesystem:TOPX_CACHESYSTEM,'forumids':vtlai_topx_curids,'securitytoken':SECURITYTOKEN},
	  success: function(data){
		jQuery('#vtlai_topx_list').html(data);
		jQuery('#vtlai_topx_status').removeClass('vtlai_loading');
		jQuery('#vtlai_topx_status').addClass('vtlai_stopload');
		backgroundAlert(222,246,246);
	  },
	  dataType: 'html'
	});
}

function collapseTopX()
{
	jQuery('#vtlai_topx.topx .tab').animate({
			width:'652'
		},200,function(){
			jQuery('#vtlai_topx.topx .content').animate({
				width:'652'
			},200);
			jQuery('#topx_bighead').fadeOut('fast',function(){
				jQuery('#vtlai_topx_list td.posttime,#vtlai_topx_list td.views,#vtlai_topx_list td.replycount').css('display','none');
				jQuery('#topx_smallhead').fadeIn('fast',function(){
					jQuery('#vtlai_topx').css('float','left');
					jQuery('#vtlai_topx').css('display','block');
					jQuery('#vtlai_topx').css('width','652px');
					
					jQuery('.largebanner').fadeIn('fast',function(){
						TOPX_DEFAULTSIZE='small';
						document.cookie=TOPX_COOKIEPREFIX+"topx_expand=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";
						loadTopX();
					});
				});
			});
	});
	
	jQuery('#vtlai_topx .collapse').remove();
	jQuery('#vtlai_topx').append('<a class="expand" title="Mở rộng khung thống kê"></a>');
	jQuery('#vtlai_topx .expand').click(function(){
		expandTopX();
	});
}

function expandTopX()
{
	jQuery('.largebanner').fadeOut('fast',function(){
		jQuery('#vtlai_topx').css('float','none');
		jQuery('#vtlai_topx').css('display','block');
		jQuery('#vtlai_topx').css('width','auto');
		
		jQuery('#topx_smallhead').fadeOut('fast',function(){
			jQuery('#topx_bighead').fadeIn('fast');
		});
		jQuery('#vtlai_topx .expand').fadeOut('fast');
		jQuery('#vtlai_topx.topx .content').animate({
			width:'100%'
		},200);
		jQuery('#vtlai_topx.topx .tab').animate({
			width:'100%'
		},200,function(){
			//--Thêm nút thu gọn
			jQuery('#vtlai_topx .expand').remove();
			jQuery('#vtlai_topx').append('<a class="collapse" title="Thu gọn khung thống kê"></a>');
			jQuery('#vtlai_topx .collapse').click(function(){
				collapseTopX();
			});
			//--set Cookie & Load TopX
			TOPX_DEFAULTSIZE='big';
			document.cookie=TOPX_COOKIEPREFIX+"topx_expand=1; expires=0; path=/";
			loadTopX();
		});
	});
}

jQuery(document).ready(function(){
	//--Add My Post Button
	if(SECURITYTOKEN!='guest')
	{
		jQuery('#vtlai_topx .tab #vtlai_topx_status').before('<a title="Xem các bài viết bạn vừa trả lời" id="mypostbtn" class="vtlai_topx_tabitem" href="#mypost">MyPost</a>');
		jQuery('#mypostbtn').css('color','#F00');
		//jQuery('#mypostbtn').click(function(){
		//	alert('Chức năng này sẽ hoạt động muộn nhất vào sáng mai');
		//	return false;
		//});
	}
	//--Tab Click Event
	jQuery('#vtlai_topx .tab .vtlai_topx_tabitem').click(function(){
		jQuery('#vtlai_topx .tab .vtlai_topx_tabitem').removeClass('active');
		jQuery(this).addClass('active');
		vtlai_topx_curids=jQuery(this).attr('href').replace('#','');
		loadTopX();
		return false;
	});
	//--Tab Reload Click Event
	jQuery('#vtlai_topx_status').click(function(){
		loadTopX();
	});
	
	//--Load TopX
	if(document.cookie.indexOf(TOPX_COOKIEPREFIX+'topx_expand')!=-1)
	{
		expandTopX();
	}
	else
	{
		loadTopX();
	}
	//--Set Interval
	setInterval('loadTopX()',TOPX_RELOADTIME*1000);
	
	//--Add Expand Button
	if(TOPX_DEFAULTSIZE=='small')
	{
		jQuery('#vtlai_topx').append('<a class="expand" title="Mở rộng khung thống kê"></a>');
		jQuery('#vtlai_topx .expand').click(function(){
			expandTopX();
		});
	}
});

