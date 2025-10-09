<?php
	require 'lib/function.php';

	$user	= @$sql->fetchq("SELECT * FROM `users` WHERE `id`='$id'");

	if (!$user) {
		require 'lib/layout.php';
		// @todo i'm pretty sure we have a function for this, somewhere
		print "$header<br>$tblstart$tccell1>The specified user doesn't exist.$tblend$footer";
		printtimedif($startingtime);
		die();
	}

	$windowtitle	= "$boardname -- Profile for $user[name]";
	require 'lib/layout.php';

	// Get various stats ...

	// the highest post count of any user on the forum
	$maxposts		= $sql->resultq("SELECT MAX(`posts`) FROM `users`");

	// guh
	$userrank		= getrank($user['useranks'],$user['title'],$user['posts'],$user['powerlevel']);
	$threadsposted	= $sql->resultq("SELECT COUNT(`id`) AS cnt FROM `threads` WHERE `user` = '$id'");

	// Last post, and a link to the post itself
	$lastpostdate	= "None";
	$lastpostlink	= "";
	if ($user['posts']) {
		$lastpostdate	= date($dateformat, $user['lastposttime'] + $tzoff);

		$post			= @$sql->fetchq("SELECT `id`, `thread` FROM `posts` WHERE `user`='$id' AND `date`='$user[lastposttime]'");

		// @TODO refactor into join
		if ($post && $thread = $sql->fetchq("SELECT `title`, `forum` FROM `threads` WHERE `id` = '$post[thread]'")) {
			$forum		= $sql->fetchq("SELECT `id`, `title`, `minpower` FROM `forums` WHERE `id` = '$thread[forum]'");
			$thread['title']	= htmlspecialchars($thread['title']);
			if ($forum['minpower'] > 0 && $forum['minpower'] > $loguser['powerlevel']) {
				$lastpostlink	= ", in a restricted forum";
			} else {
				$lastpostlink	= ", in <a href=thread.php?pid=$post[id]#$post[id]>$thread[title]</a> (<a href=forum.php?id=$forum[id]>$forum[title]</a>)";
			}

		}
	}

	// Logged in users get a private message link
	$sendpmsg			= $log ? " | <a href='sendprivate.php?userid=$id'>Send private message</a>" : "";
	$adminopts			= "";
	$lastip				= "";

	if ($isadmin) {
		$lastip			= $user['lastip'] ? " <br>with IP: <a href='ipsearch.php?ip={$user['lastip']}' style='font-style:italic;'>$user[lastip]</a>" : "";

		$adminopts		= "<tr>$tccell1s colspan=2><a href='private.php?id={$id}' style='font-style:italic;'>View private messages</a> |"
			." <a href='forum.php?fav=1&user={$id}' style='font-style:italic;'>View favorites</a> |"
			." <a href='edituser.php?id={$id}' style='font-style:italic;'>Edit user</a>";
	}

	$aim		= str_replace(" ", "+", $user['aim']);
	$schname	= $sql->resultq("SELECT `name` FROM `schemes` WHERE `id`='$user[scheme]'");
	$numdays	= (ctime() - $user['regdate']) / 86400;

	$user['signature']	= doreplace($user['signature'], $user['posts'], $numdays, $user['name']);
	$user['postheader']	= doreplace($user['postheader'], $user['posts'], $numdays, $user['name']);

	$picture		= $user['picture']	? "<img src=\"$user[picture]\">" : "";
	$moodavatar		= $user['moodurl'] ? " | <a href='avatar.php?id=$id' class=\"popout\" target=\"_blank\">Preview mood avatar</a>" : "";

	$icqicon	= $user['icq'] ? htmlspecialchars($user['icq']) : "";


	$tccellha	= "<td bgcolor=$tableheadbg";
	$tccellhb	= "><center>$fonthead";


	$tzoffset	= $user['timezone'];
	$tzoffrel	= $tzoffset - $loguser['timezone'];
	$tzdate		= date($dateformat, ctime() + $tzoffset * 3600);

	$isbirthday	= false;
	$birthday	= "";
	$age		= "";
	if ($user['birthday']) {
		// Todo: This is a clear hack. Remove it.
		//            -- you, 10 years ago
		$isbirthday	= (date('m-d', $user['birthday']) == date('m-d', ctime() + $tzoff));

		$birthday	= date("l, F j, Y",$user['birthday']);
		$age		= "(". floor((ctime() - $user['birthday']) / 86400 / 365.2425) ." years old)";
	}

	$namecolor	= getnamecolor($isbirthday ? 255 : $user['sex'], $user['powerlevel'], false);

	// RPG fun shit
	$exp=calcexp($user['posts'],(ctime()-$user['regdate'])/86400);
	$lvl=calclvl($exp);
	$expleft=calcexpleft($exp);
	$expstatus="Level: $lvl<br>EXP: $exp (for next level: $expleft)";
	if($user['posts'] > 0) $expstatus.="<br>Gain: ".calcexpgainpost($user['posts'],(ctime()-$user['regdate'])/86400)." EXP per post, ".calcexpgaintime($user['posts'],(ctime()-$user['regdate'])/86400)." seconds to gain 1 EXP when idle";
	$postavg=sprintf("%01.2f",$user['posts']/(ctime()-$user['regdate'])*86400);
	$totalwidth=116;
	$barwidth=$user['posts'] == 0 ? 0 : @floor(($user['posts']/$maxposts)*$totalwidth);
	if($barwidth<0) $barwidth=0;
	if($barwidth) $baron="<img src=images/$numdir"."bar-on.gif width=$barwidth height=8>";
	if($barwidth<$totalwidth) $baroff="<img src=images/$numdir"."bar-off.gif width=".($totalwidth-$barwidth)." height=8>";
	$bar="<img src=images/$numdir"."barleft.gif>$baron$baroff<img src=images/$numdir"."barright.gif><br>";
	if(!$topposts) $topposts=5000;

	if($user['posts']) $projdate=ctime()+(ctime()-$user['regdate'])*($topposts-$user['posts'])/($user['posts']);
	if(!$user['posts'] or $user['posts']>=$topposts or $projdate>2000000000 or $projdate<ctime()) $projdate="";
	else $projdate=" -- Projected date for $topposts posts: ".date($dateformat,$projdate+$tzoff);

	if($user['minipic']) $minipic="<img src=\"". htmlspecialchars($user['minipic']) ."\" width=16 height=16 align=absmiddle> ";
	$homepagename=$user['homepageurl'];
	if($user['homepagename']) $homepagename="$user[homepagename]</a> - $user[homepageurl]";
	if($user['postbg']) $postbg="<div style='background:url($user[postbg]);' height=100%>";

	loadtlayout();
	$user['headtext']=$user['postheader'];
	$user['signtext']=$user['signature'];
	$user['text'] = "Sample text. [quote=fhqwhgads]A sample quote, with a <a href=about:blank>link</a>, for testing your layout.[/quote]This is how your post will appear.";
	$user['uid']	= $_GET['id'];
	$user['date']	= ctime();

	// so that layouts show up regardless of setting (for obvious reasons)
	$loguser['viewsig'] = 1;

	// shop/rpg such
	$shops=$sql->query('SELECT * FROM itemcateg ORDER BY corder');
	$eq=$sql->fetchq("SELECT * FROM users_rpg WHERE uid=$id");
	$itemids=array_unique(array($eq['eq1'], $eq['eq2'], $eq['eq3'], $eq['eq4'], $eq['eq5'], $eq['eq6'], $eq['eq7']));
	$itemids=implode(',', $itemids);
	$eqitems=$sql->query("SELECT * FROM items WHERE id IN ({$itemids})");
	while($item=$sql->fetch($eqitems)) $items[$item['id']]=$item;
	while($shop=$sql->fetch($shops))
		$shoplist.="
			<tr>
			$tccell1s>$shop[name]</td>
			$tccell2s width=100%>".$items[$eq['eq'.$shop['id']]]['name']."&nbsp;</td>
		";

	/* extra munging for whatever reason */
	$user['email'] = urlencode($user['email']);

	// AKA
	if ($user['aka'] && $user['aka'] != $user['name'])
		$aka = "$tccell1l width=150><b>Also known as</td>			$tccell2l>$user[aka]<tr>";
	else $aka='';

  print "
	$header
	<div>$fonttag Profile for <b>$minipic<span style='color:#{$namecolor}'>$user[name]</span></b></div>
<table cellpadding=0 cellspacing=0 border=0>
<td width=100% valign=top>
$tblstart
	$tccellh colspan=2><center>General information<tr>
	<!-- $tccell1l width=150><b>Username</td>			$tccell2l>$user[name]<tr> -->
	$aka
	$tccell1l width=150><b>Total posts</td>			$tccell2l>$user[posts] ($postavg per day) $projdate<br>$bar<tr>
	$tccell1l width=150><b>Total threads</td>		$tccell2l>$threadsposted<tr>
	$tccell1l width=150><b>EXP</td>					$tccell2l>$expstatus<tr>
". (false ? "	$tccell1l width=150><b>User rating</td>			$tccell2l>$ratingstatus<tr>" : "") ."
	$tccell1l width=150><b>Registered on</td>		$tccell2l>".@date($dateformat,$user['regdate']+$tzoff)." (".floor((ctime()-$user['regdate'])/86400)." days ago)<tr>
	$tccell1l width=150><b>Last post</td>			$tccell2l>$lastpostdate$lastpostlink<tr>
	$tccell1l width=150><b>Last activity</td>		$tccell2l>".date($dateformat,$user['lastactivity']+$tzoff)."$lastip<tr>
$tblend
<br>$tblstart
	$tccellh colspan=2><center>Contact information<tr>
	$tccell1l width=150><b>Email address</td>		$tccell2l><a href='mailto:$user[email]'>$user[email]</a>&nbsp;<tr>
	$tccell1l width=150><b>Homepage</td>			$tccell2l><a href='$user[homepageurl]'>$homepagename</a>&nbsp;<tr>
	$tccell1l width=150><b>ICQ number</td>			$tccell2l>$user[icq] $icqicon&nbsp;<tr>
	$tccell1l width=150><b>AIM screen name</td>		$tccell2l><a href='aim:goim?screenname=$aim'>$user[aim]</a>&nbsp;<tr>
$tblend
<br>$tblstart
	$tccellh colspan=2><center>User settings<tr>
	$tccell1l width=150><b>Timezone offset</td>		$tccell2l>$tzoffset hours from the server, $tzoffrel hours from you (current time: $tzdate)<tr>
	$tccell1l width=150><b>Items per page</td>		$tccell2l>". $user['threadsperpage'] ." threads, ". $user['postsperpage'] ." posts<tr>
	$tccell1l width=150><b>Color scheme</td>		$tccell2l>".$schname."<tr>
$tblend
</td><td>&nbsp;&nbsp;&nbsp;</td><td valign=top>
$tblstart
	$tccellh><center>RPG status<tr>
	$tccell1l><img src='status.php?u=$id'>
$tblend
<br>$tblstart
	$tccellh colspan=2><center>Equipped Items<tr>
	$shoplist
$tblend
</td></table>
<br>$tblstart
	$tccellh colspan=2><center>Personal information<tr>
	$tccell1l width=150><b>Real name</td>			$tccell2l>$user[realname]&nbsp;<tr>
	$tccell1l width=150><b>Pronouns</td>			$tccell2l>". htmlspecialchars($user['pronouns']) ."&nbsp;<tr>
	$tccell1l width=150><b>Location</td>			$tccell2l>$user[location]&nbsp;<tr>
	$tccell1l width=150><b>Birthday</td>			$tccell2l>$birthday $age&nbsp;<tr>
	$tccell1l width=150><b>User bio</td>			$tccell2l>". dofilters(doreplace2(doreplace($user['bio'], $user['posts'], (ctime()-$user['regdate'])/86400, $user['name']))) ."&nbsp;<tr>
$tblend
<br>$tblstart
	$tccellh colspan=2><center>Sample post<tr>
$tblend
	". threadpost($user, 1) ."
<br>$tblstart
	$tccellhs colspan=2><center>Options<tr>
	$tccell2s colspan=2>
	<a href=thread.php?user=$id>Show posts</a> |
	<a href=forum.php?user=$id>View threads by this user</a>
	$sendpmsg
  $ratelink
  $moodavatar
  <tr>
	$tccell2s colspan=2>
	<a href=postsbyuser.php?id=$id>List posts by this user</a> |
	<a href=postsbytime.php?id=$id>Posts by time of day</a> |
	<a href=postsbythread.php?id=$id>Posts by thread</a> |
	<a href=postsbyforum.php?id=$id>Posts by forum</td>$adminopts
	$tblend$footer
  ";

  printtimedif($startingtime);
?>
