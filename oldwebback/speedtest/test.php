<?php

$link = 'http://speed.bezeqint.net/big.zip';
$start = time();
$size = filesize($link);
$file = file_get_contents($link);
$end = time();

$time = $end - $start;

$size = $size / 1048576;

$speed = $size / $time;

echo "Server's speed is: $speed MB/s";


?>