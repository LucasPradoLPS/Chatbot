<?php

$fp = @fsockopen('127.0.0.1', 8000);
if ($fp) {
    fwrite($fp, "GET /api/ping HTTP/1.1\r\nHost: 127.0.0.1:8000\r\nConnection: close\r\n\r\n");
    $headers_done = false;
    $body = '';
    while (!feof($fp)) {
        $line = fgets($fp, 128);
        if (!$headers_done) {
            echo "HEADER: " . trim($line) . "\n";
            if (trim($line) === '') {
                $headers_done = true;
                echo "---BODY START---\n";
            }
        } else {
            $body .= $line;
        }
    }
    echo "Body content (" . strlen($body) . " bytes): [$body]\n";
    fclose($fp);
} else {
    echo "Connection failed\n";
}

?>
