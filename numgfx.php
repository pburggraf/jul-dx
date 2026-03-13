<?php

declare(strict_types=1);
$n = $_GET['n'];
$l = $_GET['l'];
$f = $_GET['f'];
$len = strlen($n .'');
if ($len < $l) {
    $ofs = $l - $len;
    $len = $l;
}
if (!$f) {
    $f = 'numnes';
}
$gfx = imagecreatefrompng("numgfx/$f.png");
$img = imagecreate($len * 8, 8);
imagecopy($img, $gfx, 0, 0, 104, 0, 1, 1);
for ($i = 0; $i < $len; ++$i) {
    switch ($n[$i]) {
        case '/': $d = 10;
            break;
        case 'N': $d = 11;
            break;
        case 'A': $d = 12;
            break;
        case '-': $d = 13;
            break;
        default: $d = $n[$i];
    }
    imagecopy($img, $gfx, ($i + $ofs) * 8, 0, $d * 8, 0, 8, 8);
}
header('Content-type:image/png');
if ($f == 'numdeath') {
    $ctp = 1;
} else {
    $ctp = 0;
}
imagecolortransparent($img, $ctp);
imagepng($img);
imagedestroy($img);
