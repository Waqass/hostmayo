<?php
$file = dirname(__FILE__).'/blesta2ce.php';
if ($fp = @fopen($file, 'rb')) {
    @set_time_limit(0);
    @ob_end_clean();
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"blesta2ce.php\"");
    header("Content-Length: ".filesize($file));

    // work-around IE bug when using SSL
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    header("Expires: 0");
    while (!feof($fp)) { echo fread($fp, 4096); }
    fclose($fp);
    exit;
}