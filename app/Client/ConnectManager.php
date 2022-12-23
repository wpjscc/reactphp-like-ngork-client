<?php 

namespace App\Client;

use App\Output\StdoutStreamManager;

class ConnectManager {
    public $connected;

    private static $manager;

    public static $localHost;
    public static $token;
    public static $isWebsocket;

    /**
     * Undocumented function
     *
     * @return self
     */
    public static  function createManager()
    {
        if (self::$manager) {
            return self::$manager;
        }
        return self::$manager = new self();
    }

    public function connect(){
        $this->connected = 1;
        \Ratchet\Client\connect('wss://reactphp-server.reactphp.wpjs.cc/tunnel')->then(function($conn) {
            $conn->authenticate = false;
            
            $conn->on('open', function($msg) use ($conn) {
                StdoutStreamManager::createManager()->write('client open');
            });
            $conn->on('message', function($msg) use ($conn) {
                StdoutStreamManager::createManager()->write('client message');
                TunnelManager::createManager()->handle($conn, $msg);
            });
            $conn->on('close', function($msg) use ($conn) {
                StdoutStreamManager::createManager()->write('client close');
                StdoutStreamManager::createManager()->write($msg);
                // ClientManager::createManager()->removeClientConnectByCon($conn);
                $this->connected = 0;
            });
            $conn->on('error', function() use ($conn) {
                StdoutStreamManager::createManager()->write('client error');
                // ClientManager::createManager()->removeClientConnectByCon($conn);
                $this->connected = 0;
            });
        
            $authenticate = json_encode([
                'event' => 'authenticate',
                'data' => [
                    // 'host' => 'hello-world.reactphp.wpjs.cc',
                    'host' => 'hello-reactphp-server.reactphp.wpjs.cc',
                    // 'host' => '47.96.15.116:8088',
                    // 'local_host' => '47.96.15.116:7777'
                    // 'local_host' => '47.96.15.116'
                    'local_host' => self::$localHost,
                    // 'local_host' => 'wintercms-example.test',
                    // 'local_host' => 'ws://47.96.15.116:8088/test',
                    // 'local_host' => '47.96.15.116:8089',
                  
                    // 'local_host' => 'jc91715.top'
                    // 'local_host' => 'k8s-wintercms.test'
                    // 'local_host' => '47.96.15.116:8888'
                    // 'local_host' => '192.168.1.3:8888',
                    // 'local_host' => '47.96.15.116:8089',
                    'is_websocket' => self::$isWebsocket ? true : false,
                    'token' => self::$token,
                ]
            ]);
            StdoutStreamManager::createManager()->write($authenticate);
            $conn->send($authenticate);
        }, function ($e) {
            StdoutStreamManager::createManager()->write("Could not connect: {$e->getMessage()}\n");
            $this->connected = 0;
        });
    }
    public function retryConnnect()
    {
        if (!$this->connected) {
            $this->connect();
        }
    }
}
