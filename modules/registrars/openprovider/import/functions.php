<?php

/**
 * Function show status bar in console.
 *
 * @param $done
 * @param $total
 * @param int $size
 */
function show_status($done, $total, $size=30): void
{
    static $start_time;

    // if we go over our bound, just ignore it
    if($done > $total) return;

    if(empty($start_time)) $start_time=time();
    $now = time();

    $perc=(double)($done/$total);

    $bar=floor($perc*$size);

    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }

    $disp=number_format($perc*100, 0);

    $status_bar.="] $disp%  $done/$total";

    $rate = ($now-$start_time)/$done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

    echo "$status_bar  ";

    flush();

    // when done, send a newline
    if($done == $total) {
        echo "\n";
    }
}

/**
 * Function return lines number in file.
 *
 * @param $file
 * @return int
 */
function lines($file): int
{
    if(!file_exists($file)) exit("File not exist!");
    $file_arr = file($file);
    return count($file_arr);
}