<?php
/*======================================================================*\
|| #################################################################### ||
|| # vt.Lai vBB TopX 4.2                                              # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2010-2012 Vu Thanh Lai. All Rights Reserved.          # ||
|| # Please do not remove this comment lines.                         # ||
|| # -------------------- LAST MODIFY INFOMATION -------------------- # ||
|| # Last Modify: 03-10-2012 03:00:00 AM by: Vu Thanh Lai             # ||
|| # Please do not remove these comment line if use my code or a part # ||
|| #################################################################### ||
\*======================================================================*/
	
//--Get from cache
$cachefilename='vtlai_cache/vtlai_topx_'.md5($_REQUEST['forumids'].$_REQUEST['size']).'.txt';
if($_REQUEST['forumids']!='mypost')
{
	if($_REQUEST['cachesystem']=='1')
	{
		$unserialize=@file_get_contents($cachefilename);
		if($unserialize)
		{
			$cachedata=unserialize($unserialize);
			$time=time();
			if($cachedata && ($cachedata['exptime']>$time))
			{
				exit($cachedata['content']."<!-- Get at: $time, Cache to: {$cachedata['exptime']} by vt.lai TopX 4.3-->");
			}
		}
	}
	elseif($_REQUEST['cachesystem']=='2')
	{
		exit('Tạm thời chưa hỗ trợ Memcache');
	}
}
//--If from database
define('THIS_SCRIPT', 'vtlai_topx');
define('CSRF_PROTECTION', true);
define('LOCATION_BYPASS', 1);
define('NOPMPOPUP', 1);
define('NONOTICES', 1);
define('NOHEADER', 1);
define('NOSHUTDOWNFUNC', 1);

//define('NOCHECKSTATE', 1);
//define('SKIP_SESSIONCREATE', 1);

include 'global.php';

function cutString($noidung,$num)
{
	$limit = $num - 1 ;
	$str_tmp = '';
	$arrstr = explode(' ', $noidung);
	if (count($arrstr) <= $num )
		return $noidung;
	if (!empty($arrstr))
	{
		for ( $j=0; $j< $num ; $j++)
		{
			$str_tmp .= " " . $arrstr[$j];
		}
	}
	return trim($str_tmp).'...';
} 

if($_REQUEST['do']=='vtlai_topx')
{
	//--------
	$forumids=&$_REQUEST['forumids'];
	if(!$forumids)
	{
		print_output(json_encode(array('status'=>0,'message'=>'Không xác định được nội dung cần lấy')));
	}
	
	$fields='t.*';
	if($_REQUEST['size']=='small')
		$limit=intval($vbulletin->options['vtlai_topx_count']);
	else
		$limit=intval($vbulletin->options['vtlai_topx_count_large']);
	
	if(!$limit)
		$limit=10;
	
	if($forumids=='all')
	{
		$blackforum=$vbulletin->options['vtlai_topx_blackforum'];
		if($blackforum)
		{
			$blackforum="AND t.forumid NOT IN ($blackforum)";
		}
		
		$sql="SELECT $fields FROM ".TABLE_PREFIX."thread t  WHERE t.visible=1 $blackforum ORDER BY lastpost DESC LIMIT $limit";
	}
	elseif($forumids=='mypost')
	{
		if(!$vbulletin->userinfo['userid'])
		{
			exit('Bạn chưa đăng nhập');
		}
		elseif(!$vbulletin->userinfo['posts'])
		{
			exit('Bạn chưa có bài viết nào :D');
		}
		else
		{
			$sql="SELECT t.* 
			FROM ".TABLE_PREFIX."thread t
			WHERE threadid
			IN (
				SELECT DISTINCT threadid
				FROM ".TABLE_PREFIX."post
				WHERE userid ='{$vbulletin->userinfo['userid']}'
				ORDER BY postid DESC
			)
			ORDER BY lastpost DESC 
			LIMIT $limit";
			//$sql="SELECT $fields FROM ".TABLE_PREFIX."thread t  WHERE t.visible=1 AND lastposterid='{$vbulletin->userinfo['userid']}' ORDER BY lastpost DESC LIMIT $limit";
		}
	}
	else
	{
		$arr=explode(',',$forumids);
		foreach($arr as &$forumid)
		{
			$forumid=intval($forumid);
		}
		if(count($arr))
		{
			$forumids=implode(',',$arr);
			$sql="SELECT $fields FROM ".TABLE_PREFIX."thread t WHERE t.visible=1 AND t.forumid IN ($forumids) ORDER BY lastpost DESC LIMIT $limit";
		}
		else
		{
			print_output(json_encode(array('status'=>0,'message'=>'Không xác định được nội dung cần lấy')));
		}
	}
	$result=$db->query($sql);
	if($db->num_rows($result))
	{
		if($_REQUEST['size']=='small')
			$tem=vB_Template::create('vtlai_topx_postbit');
		else
			$tem=vB_Template::create('vtlai_topx_postbit_large');
			
		$ajaxout='';
		while($thread=$db->fetch_array($result))
		{
			$thread['ftitle']=&$vbulletin->forumcache[$thread['forumid']]['title'];
			$thread['detailtime']=vbdate('h:m:i A',$thread['lastpost']);
			//--Get mUsername
			$postuser = $vbulletin->db->query_first("SELECT usergroupid,displaygroupid,username FROM ". TABLE_PREFIX ."user WHERE userid = '$thread[lastposterid]' LIMIT 1");
			//$thread['musername'] = str_replace(array('<b>','</b>'),'',fetch_musername($postuser));
			$thread['musername'] = fetch_musername($postuser);
			
			
			if($forumids=='mypost' && ($thread['lastposterid']==$vbulletin->userinfo['userid']))
			{
				$poster=array('username'=>$thread['susername'],'usergroupid'=>$vbulletin->userinfo['usergroupid']);
				$thread['susername']=fetch_musername($poster);
			}
			$page=ceil(($thread['replycount']+1)/$vbulletin->options['maxposts']);
			$thread['link']=vB_Friendly_Url::fetchLibrary($vbulletin, 'thread', $thread, array('page'=>$page))->get_url();
			$thread['flink']=vB_Friendly_Url::fetchLibrary($vbulletin, 'forum', $vbulletin->forumcache[$thread['forumid']], null)->get_url();
			$thread['ulink']=vB_Friendly_Url::fetchLibrary($vbulletin, 'member', array('userid'=>$thread['lastposterid'],'username'=>$thread['lastposter']), null)->get_url();
			$tem->register('thread',$thread);
			$ajaxout.=$tem->render();
		}
		$db->free_result($result);
		//--Save to cache
		if($vbulletin->options['vtlai_topx_cachesystem']==1)
		{
			$f=@fopen($cachefilename,'w+');
			if($f)
			{
				fwrite($f,serialize(array('exptime'=>(time()+intval($vbulletin->options['vtlai_topx_cachetime'])),'content'=>$ajaxout)));
				fclose($f);
			}
		}
		//--
		print_output($ajaxout);
	}
}