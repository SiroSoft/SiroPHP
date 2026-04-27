<?php
$url = 'http://localhost:8080/';
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$resp = @file_get_contents($url, false, $ctx);
echo "Response:\n";
echo $resp;
echo "\n\nParsed:\n";
print_r(json_decode($resp, true));
