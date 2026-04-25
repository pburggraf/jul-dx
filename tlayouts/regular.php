<?php

declare(strict_types=1);

function userfields()
{
    return 'posts,sex,powerlevel,birthday,aka,picture,moodurl,title,useranks,location,lastposttime,lastactivity,imood,pronouns';
}

function postcode($post, $set)
{
    global $tzoff, $smallfont, $ip, $quote, $edit, $dateshort, $dateformat, $tlayout, $textcolor, $numdir, $numfil, $tblstart, $hacks, $x_hacks, $loguser;

    $tblend = '</table>';
    $exp = calcexp($post['posts'], (ctime() - $post['regdate']) / 86400);
    $lvl = calclvl($exp);
    $expleft = calcexpleft($exp);
    $set['userpic'] = $set['userpic'] ?? ''; // please stop being undefined
    $post['num'] = $post['num'] ?? null;

    if ($tlayout == 1) {
        $level = "Level: $lvl";
        $poststext = 'Posts: ';
        $postnum = "$post[num]/";
        $posttotal = $post['posts'];
        $experience = "EXP: $exp<br>For next: $expleft";
        $totalwidth = 96;
        $barwidth = $totalwidth - round(@($expleft / totallvlexp($lvl)) * $totalwidth);

        if ($barwidth < 1) {
            $barwidth = 0;
        }

        $baron = '';
        $baroff = '';
        if ($barwidth > 0) {
            $baron = "<img src=images/$numdir"."bar-on.gif width=$barwidth height=8>";
        }

        if ($barwidth < $totalwidth) {
            $baroff = "<img src=images/$numdir".'bar-off.gif width='.($totalwidth - $barwidth).' height=8>';
        }
        $bar = "<br><img src=images/$numdir"."barleft.gif height=8>$baron$baroff<img src=images/$numdir".'barright.gif height=8>';
    } else {
        $level = "<img src=images/$numdir"."level.gif width=36 height=8><img src=numgfx.php?n=$lvl&l=3&f=$numfil height=8>";
        $experience = "<img src=images/$numdir"."exp.gif width=20 height=8><img src=numgfx.php?n=$exp&l=5&f=$numfil height=8><br><img src=images/$numdir"."fornext.gif width=44 height=8><img src=numgfx.php?n=$expleft&l=2&f=$numfil height=8>";
        $poststext = "<img src=images/_.gif height=2><br><img src=images/$numdir".'posts.gif width=28 height=8>';
        $postnum = "<img src=numgfx.php?n=$post[num]/&l=5&f=$numfil height=8>";
        $posttotal = "<img src=numgfx.php?n=$post[posts]&f=$numfil".($post['num'] ? '' : '&l=4').' height=8>';
        $totalwidth = 56;
        $barwidth = $totalwidth - round(@($expleft / totallvlexp($lvl)) * $totalwidth);

        if ($barwidth < 1) {
            $barwidth = 0;
        }

        if ($barwidth > 0) {
            $baron = "<img src=images/$numdir"."bar-on.gif width=$barwidth height=8>";
        }

        if ($barwidth < $totalwidth) {
            $baroff = "<img src=images/$numdir".'bar-off.gif width='.($totalwidth - $barwidth).' height=8>';
        }
        $bar = "<br><img src=images/$numdir"."barleft.gif width=2 height=8>$baron$baroff<img src=images/$numdir".'barright.gif width=2 height=8>';
    }

    if (!$post['num']) {
        $postnum = '';

        // ????
        // if($postlayout==1) $posttotal="<img src=numgfx.php?n=$post[posts]&f=$numfil&l=4 height=8>";
    }

    $reinf = syndrome(filter_int($post['act']));

    if ($post['lastposttime']) {
        $sincelastpost = 'Since last post: '.timeunits(ctime() - $post['lastposttime']);
    }
    $lastactivity = 'Last activity: '.timeunits(ctime() - $post['lastactivity']);
    $since = 'Since: '.@date($dateshort, $post['regdate'] + $tzoff);
    $postdate = date($dateformat, $post['date'] + $tzoff);

    $threadlink = '';
    if (filter_string($set['threadlink'])) {
        $threadlink = ", in $set[threadlink]";
    }

    $post['edited'] = filter_string($post['edited']);
    if ($post['edited']) {
        //		.="<hr>$smallfont$post[edited]";
    }

    $sidebars = [1, 3, 19, 89, 387, 45, 92, 47];

    $sidebars = [19, 89, 387, 45, 92, 47, 1420, 1090, 2100, 2069];

    // Large block of user-specific hacks follows //

    if (false && $post['uid'] == 1 && true) {
        global $numdir;
        $numdir_ = $numdir;
        $numdir = 'num3/';

        if ($post['num']) {
            $numtext = generatenumbergfx($post['num'], 1, true) .'<br>'. generatenumbergfx($post['posts']);
        } else {
            $numtext = generatenumbergfx($post['posts'], 1, true);
        }
        $numdir = $numdir_;

        return "<div class='post'>
	$tblstart
	$set[tdbg] rowspan=2 style='padding: 5px 1px 5px 1px;'>
	  <center>$set[userlink]$smallfont<br>
	  $set[userrank]
		$reinf
		<br>
		<br>$set[userpic]
		<br><br>$numtext</center>
	  <br><img src=images/_.gif width=200 height=1>
	</td>
	$set[tdbg] height=1 width=100%>
	  <table cellspacing=0 cellpadding=2 width=100% class=fonts>
	    <td>Posted on $postdate$threadlink$post[edited]</td>
	    <td width=255><nobr>$quote$edit$ip
	  </table><tr>
	$set[tdbg] height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
	$tblend
	</div>";
    }

    // Inu's sidebar
    // (moved up here for to display for everyone during doomclock mode!)
    if ($post['uid'] == '2100') {
        $posttable = '<table style="border:none;border-spacing:0px;">';
        // doomclock
        $doomclock_time = mktime(3, 0, 0, 5, 19) - cmicrotime();
        if ($doomclock_time < 0 && $doomclock_time >= 86400) {
            $doomclock_time = 0;
        }
        if ($doomclock_time >= 0 && $doomclock_time < 360000) {
            $doomclock_secs = (int) ($doomclock_time % 60);
            $doomclock_mins = (int) (($doomclock_time % 3600) / 60);
            $doomclock_hrs = (int) ($doomclock_time / 3600);
            $doomclock_str = sprintf(' %d=%02d=%02d', $doomclock_hrs, $doomclock_mins, $doomclock_secs);
            $doomclock_desc = "{$doomclock_hrs} hours, {$doomclock_mins} minutes, {$doomclock_secs} seconds";
            $posttable .= "<tr><td><img src=\"images/inu/cifont/d.gif\" title=\"Hacker's Day\"></td><td align='right'>";
            $posttable .= inu_hexclock($doomclock_desc, $doomclock_time);
            $posttable .= "</td><td align='right'><img src=\"/images/inu/7sd.php?s=>FFF{$doomclock_str}\"></td></tr>";
        }
        if ($post['num']) {
            $posttable .= '<tr><td><img src="images/inu/cifont/p.gif" title="Post Number"></td><td>';
            $posttable .= inu_binaryposts($post['num'], 'images/dot3.gif', 'images/dot1.gif', $post['posts']);
            $posttable .= "</td><td align='right'><img src=\"/images/inu/7sd.php?s=".sprintf('%4d', $post['num']).'"></td></tr>';
        }
        $posttable .= '<tr><td><img src="images/inu/cifont/t.gif" title="Total Posts"></td><td>';
        $posttable .= inu_binaryposts($post['posts'], 'images/dot5.gif', 'images/dot1.gif');
        $posttable .= "</td><td align='right'><img src=\"/images/inu/7sd.php?s=>F90".sprintf('%4d', $post['posts']).'"></td></tr></table>';

        /*
            $lp = ((!$post['lastposttime']) ? "" : 'Last posted >fff'.timeunits(ctime()-$post['lastposttime']).' >0f0ago');
            $la ='Last active >fff'.timeunits(ctime()-$post['lastactivity']).' >0f0ago';
            $jd ='Joined >f11'.@date("m.d.Y",$post['regdate']+$tzoff);

            return "$tblstart
                ". str_replace('valign=top', 'valign=top', $set['tdbg']) ." rowspan=2 align=center style=\"font-size: 12px;\">
                    ".inu_hexclock()."
                     <a name=$post[id]></a><a href=\"profile.php?id=2100\"><img src=\"/images/inu/7sd.php?s=- >EC1Inuyasha>0f0 -\"></a>
                    $smallfont
                    <br><marquee scrolldelay=250 scrollamount=30 width=30 height=8 behavior=alternate><img src=\"/images/inu/7sd.php?s=>f0012=00\"><img src=\"/images/inu/7sd.php?s=>f00  =%20%20\"></marquee>
                    <br>$reinf
                    $set[userpic]
                    <br>
                    <br>". ($hacks['noposts'] ? "" : "$posttable") ."
                    <br>
                    <br><img src=\"/images/inu/7sd.php?s=$jd\">
                    <br>
                    <br><img src=\"/images/inu/7sd.php?s=$lp\">
                    <br><img src=\"/images/inu/7sd.php?s=$la\"></font>
                    <br><img src=images/_.gif width=200 height=1>
                </td>
            $set[tdbg] height=1 width=100%>
                <table cellspacing=0 cellpadding=2 width=100% class=fonts>
                    <td>Posted on $postdate$threadlink$post[edited]</td>
                    <td width=255><nobr>$quote$edit$ip
                </table><tr>
            $set[tdbg] height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
            $tblend"; */

        // non-image old version
        $lp = ((!$post['lastposttime']) ? '' : 'Last posted '.timeunits(ctime() - $post['lastposttime']).' ago');
        $la = 'Last active '.timeunits(ctime() - $post['lastactivity']).' ago';
        $jd = 'Joined '.@date('m.d.Y', $post['regdate'] + $tzoff);

        $dstyle = '';

        // [D]Inuyasha
        if ($post['moodid'] == 5) {
            $post['headtext'] = str_replace(
                ['class="inu-bg"', 'class="inu-tx"'],
                ['class="inu-dbg"', 'class="inu-dtx"'], $post['headtext']);
            $set['userlink'] = ' <a name='.$post['id'].'></a><a class="url2100" href="profile.php?id=2100"><font color="FF0202">[d]</font></a>';
            $set['userrank'] = 'Darkness upon darkness awaits you...';
            $set['userpic'] = '';
            $set['pronouns'] = 'Pronouns: they/them';
            $dstyle = ' style="color:#b671e8;background:black;"';
        }
        $prn = (isset($set['pronouns']) ? $set['pronouns'] : '');

        return "$tblstart
			". str_replace('valign=top', 'valign=top', $set['tdbg']) ."{$dstyle} rowspan=2 align=center style=\"font-size: 12px;\">
				 &mdash; $set[userlink] &mdash;
				$smallfont
				". ($set['userrank'] ? '<br>'. $set['userrank'] .'<br>' : '') ."
				$reinf
				<br>
				$set[userpic]
				<br>
				<br>". ($hacks['noposts'] ? '' : "$posttable") ."
				<br>$prn
				<br>$jd
				<br>
				<br>$lp
				<br>$la</font>
				<br><img src=images/_.gif width=200 height=1>
			</td>
		$set[tdbg]{$dstyle} height=1 width=100%>
			<table cellspacing=0 cellpadding=2 width=100% class=fonts{$dstyle}>
				<td>Posted on $postdate$threadlink$post[edited]</td>
				<td width=255><nobr>$quote$edit$ip
			</table><tr>
		$set[tdbg]{$dstyle} height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
		$tblend
		</div>";
    }
    // End Inu's sidebar

    if (($post['uid'] == 18) && $x_hacks['mmdeath'] >= 0 && !$_GET['test2']) {
        return "
	<table style=\"background: #f00 url('numgfx/red.gif');\" cellpadding=3 cellspacing=1>
	$set[tdbg] style='background: #000;' rowspan=2>
		<br><center class='stupiddoomtimerhack'><img src='numgfx.php?f=numdeath&n=". $x_hacks['mmdeath'] ."' height=32 style=\"background: #f00 url('numgfx/red.gif');\" title=\"Doom.\"></center>
		<br>
	  <center>$set[userlink]$smallfont<br>
		<br>
		<br>$set[userpic]
		</center>

		<br><img src=images/_.gif width=194 height=1>
	</td>
	$set[tdbg] style='background: #000;'height=1 width=100%>
	  <table cellspacing=0 cellpadding=2 width=100% class=fonts>
	    <td>Posted on $postdate$threadlink$post[edited]</td>
	    <td width=255><nobr>$quote$edit$ip
	  </table><tr>
	$set[tdbg] style='background: #000;' height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
	$tblend
	</div>";
    }

    // Default layout
    if (!in_array($post['uid'], $sidebars) || $loguser['viewsig'] == 0) {
        return "
	<div class='post'>
	$tblstart
	$set[tdbg] rowspan=2>
	  $set[userlink]$smallfont<br>
	  $set[userrank]$reinf<br>
        $level$bar<br>
	  $set[userpic]<br>
	  ". (filter_bool($hacks['noposts']) ? '' : "$poststext$postnum$posttotal<br>") ."
	  $experience<br><br>
	  $since<br>
	  ". (isset($set['pronouns']) ? '<br>'.$set['pronouns'] : '').'
	  '. (isset($set['location']) ? '<br>'.$set['location'] : '')."
	  <br>
	  <br>
	  $sincelastpost<br>$lastactivity<br>
	  </font>
	  <br><img src=images/_.gif width=200 height=1>
	</td>
	$set[tdbg] height=1 width=100%>
	  <table cellspacing=0 cellpadding=2 width=100% class=fonts>
	    <td>Posted on $postdate$threadlink$post[edited]</td>
	    <td width=255><nobr>$quote$edit$ip
	  </table><tr>
	$set[tdbg] height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
	$tblend
	</div>";
    }

    // Non-defined / Blank
    // (Adelheid uses this)

    return "
	<div class='post'>
	$tblstart
	$set[tdbg] rowspan=2>
	  $set[userlink]$smallfont<br>
	  $set[userrank]$reinf<br>
	  <br><img src=images/_.gif width=200 height=1>
	</td>
	$set[tdbg] height=1 width=100%>
	  <table cellspacing=0 cellpadding=2 width=100% class=fonts>
	    <td>Posted on $postdate$threadlink$post[edited]</td>
	    <td width=255><nobr>$quote$edit$ip
	  </table><tr>
	$set[tdbg] height=220 id=\"post". $post['id'] ."\">$post[headtext]$post[text]$post[signtext]</td>
	$tblend</div>";
}

function kittynekomeowmeow($p)
{
    global $loguser;
    $kitty = ['meow', 'mrew', 'mew', 'mrow', 'mrrrr', 'mrowl', 'rrrr', 'mrrrrow', 'mreeeew'];
    $punc = [',', '.', '!', '?'];
    $p = preg_replace('/\s\s+/', ' ', $p);

    $c = substr_count($p, ' ');
    for ($i = 0; $i < $c; ++$i) {
        $mi = array_rand($kitty);
        $m .= ($m ? ' ' : '') . $kitty[$mi];
        $l = false;
        if (mt_rand(0, 7) == 7) {
            $pi = array_rand($punc);
            $m .= $punc[$pi];
            $l = true;
        }
    }

    if ($l != true) {
        $pi = array_rand($punc);
        $m .= $punc[$pi];
    }

    // if ($loguser['id'] == 1)
    return $m .' :3';
}

// For Inu's layout
function inu_binaryposts($n, $timg, $fimg, $min = 0)
{
    $tx = "<span title=\"$n\">";
    if ($n > $min) {
        $min = $n;
    }
    for ($i = 1; $i <= $min; $i <<= 1) {
        $bits[] = '<img src="' . (($n & $i) ? $timg : $fimg) . '">';
    }
    $tx .= implode('', array_reverse($bits));
    $tx .= '</span>';

    return $tx;
}

function inu_hexclock($n, $time)
{
    $tx = "<span title=\"$n\">";
    $time = (($time * 65536) / 86400);
    $hex = str_split(dechex($time));
    foreach ($hex as $letter) {
        $tx .= "<img src=\"images/inu/cifont/{$letter}.gif\">";
    }
    $tx .= '</span>';

    return $tx;
}
// End random shit for Inu's layout
