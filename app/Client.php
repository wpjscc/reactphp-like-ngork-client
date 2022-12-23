<?php

namespace App;

use App\Client\ConnectManager;
use App\Output\StdoutStreamManager;
use App\Helper\Helper;

class Client
{
    public function run($localHost, $token, $isWebsocket)
    {
        $startTime = time();
        \React\EventLoop\Loop::get()->addPeriodicTimer(3, function() use ($startTime){
            ConnectManager::createManager()->retryConnnect();
            // StdoutStreamManager::createManager()->write(sprintf('%s-%s-%s', Helper::formatTime(time() - $startTime),'after_memory',Helper::formatMemory(memory_get_usage(true))));
            $numBytes = gc_mem_caches();
            // StdoutStreamManager::createManager()->write(sprintf('%s-%s-%s-%s', Helper::formatTime(time() - $startTime),'after_memory',Helper::formatMemory(memory_get_usage(true)), $numBytes));
        });
        ConnectManager::$localHost = $localHost;
        ConnectManager::$token = $token;
        ConnectManager::$isWebsocket = $isWebsocket;
        ConnectManager::createManager()->connect();
    }
}