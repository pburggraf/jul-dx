<?php

declare(strict_types=1);

function report($type, $msg)
{
    if (!defined('BOARD_REPORTING_URL') || !function_exists('get_discord_webhook')) {
        return;
    }

    $wh_url = get_discord_webhook($type, null);

    if (!$wh_url) {
        return;
    }

    discord_send($wh_url, $msg);
}

// general purpose report function, now with discord!
function xk_ircout($type, $user, $in)
{
    // gone
    // return;
    // and back

    $dest = min(1, max(0, $in['pow']));
    if ($in['fid'] == 99) {
        $dest = 6;
    } elseif ($in['fid'] == 98) {
        $dest = 7;
    }

    global $x_hacks;

    if ($type == 'user') {
        if ($in['pmatch']) {
            $color = [8, 7];
            if ($in['pmatch'] >= 3) {
                $color = [7, 4];
            } elseif ($in['pmatch'] >= 5) {
                $color = [4, 5];
            }
            $extra = ' ('. xk($color[1]) .'Password matches: '. xk($color[0]) . $in['pmatch'] . xk() .')';
            $extradiscord = ' (**Password matches**: ' . $in['pmatch'] . ')';
        }

        $out = '1|New user: #'. xk(12) . $in['id'] . xk(11) ." $user ". xk() .'(IP: '. xk(12) . $in['ip'] . xk() .")$extra: ". BOARD_REPORTING_URL .'?u='. $in['id'];
        $outdiscord = 'New user: **#' . $in['id'] . '** '. $user . ' (IP: ' . $in['ip'] . ")$extra: <". BOARD_REPORTING_URL .'?u=' . $in['id'] . '>';
    } else {
        //			global $sql;
        //			$res	= $sql -> resultq("SELECT COUNT(`id`) FROM `posts`");
        $out = "$dest|New $type by ". xk(11) . $user . xk() .' ('. xk(12) . $in['forum'] .': '. xk(11) . $in['thread'] . xk() .'): '. BOARD_REPORTING_URL .'?p='. $in['pid'];
        $outdiscord = "New $type by **" . $user . '** (' . $in['forum'] . ': **' . $in['thread'] . '**): <'. BOARD_REPORTING_URL .'?p='. $in['pid'] . '>';
    }

    xk_ircsend($out);

    // discord part

    // logic to decide where the message goes based on info provided
    if (!function_exists('get_discord_webhook')) {
        return;
    }

    $wh_url = get_discord_webhook($type, $in);

    if (!$wh_url) {
        return;
    }

    discord_send($wh_url, $outdiscord);
}

function xk_ircsend($str)
{
    $str = str_replace(['%10', '%13'], ['', ''], rawurlencode($str));

    $str = html_entity_decode($str);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://treeki.rustedlogic.net:5000/reporting.php?t=$str");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // <---- HERE
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // <---- HERE
    $file_contents = curl_exec($ch);
    curl_close($ch);

    return true;
}

function discord_send($url, $msg)
{
    // stripped down from https://gist.github.com/Mo45/cb0813cb8a6ebcd6524f6a36d4f8862c
    $json_data = json_encode([
        'content' => $msg,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    // echo $response;
    curl_close($ch);

    return true;
}

function xk($n = -1)
{
    if ($n == -1) {
        $k = '';
    } else {
        $k = str_pad($n, 2, 0, STR_PAD_LEFT);
    }

    return "\x03". $k;
}

function ircerrors($type, $msg, $file, $line, $context)
{
    global $loguser;

    // They want us to shut up? (@ error control operator) Shut the fuck up then!
    if (!error_reporting()) {
        return true;
    }

    switch ($type) {
        case E_USER_ERROR:		$typetext = xk(4) . '- Error';
            break;
        case E_USER_WARNING:	$typetext = xk(7) . '- Warning';
            break;
        case E_USER_NOTICE:		$typetext = xk(8) . '- Notice';
            break;
        default: return false;
    }

    // Get the ACTUAL location of error for mysql queries
    if ($type == E_USER_ERROR && substr($file, -9) === 'mysql.php') {
        $backtrace = debug_backtrace();
        for ($i = 1; isset($backtrace[$i]); ++$i) {
            if (substr($backtrace[$i]['file'], -9) !== 'mysql.php') {
                $file = $backtrace[$i]['file'];
                $line = $backtrace[$i]['line'];
                break;
            }
        }
    }
    // Get the location of error for deprecation
    elseif ($type == E_USER_NOTICE && substr($msg, 0, 10) === 'Deprecated') {
        $backtrace = debug_backtrace();
        $file = $backtrace[2]['file'];
        $line = $backtrace[2]['line'];
    }

    $errorlocation = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file) ." #$line";

    xk_ircsend('102|'.($loguser['id'] ? xk(11) . $loguser['name'] .' ('. xk(10) . $_SERVER['REMOTE_ADDR'] . xk(11) . ')' : xk(10) . $_SERVER['REMOTE_ADDR']) .
               " $typetext: ".xk()."($errorlocation) $msg");

    return true;
}
