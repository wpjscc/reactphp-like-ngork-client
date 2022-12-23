<?php

require __DIR__ . '/vendor/autoload.php';

$localHost = $argv[1] ?? '';
if (!$localHost) {
    exit('请输入本地服务例如:'.
        "\n"."127.0.0.1"."\n".
        "\n"."192.168.1.8"."\n".
        "\n"."local.test"."\n"
    );
}
$token = $argv[2] ?? '';

if (!$token) {
    exit('请输入token');
}

$isWebsocket = $argv[3] ?? false;

(new App\Client())->run($localHost, $token, $isWebsocket);
