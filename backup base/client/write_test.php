<?php
$path = __DIR__ . "/receipts/test.txt";
$ok = file_put_contents($path, "ok " . date("c"));
var_dump($ok, $path, file_exists($path));