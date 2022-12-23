<?php

namespace App\Client;

use App\Output\StdoutStreamManager;

class TunnelManager {

    private static $manager;

    public $clients;
    public $httpclients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

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
        return self::$manager = new static;
    }

    public function handle($conn, $msg)
    {
        StdoutStreamManager::createManager()->write($msg);

        try {
            $payload = json_decode($msg);
            if (method_exists($this, $payload->event ?? '')) {
                call_user_func([$this, $payload->event], $conn, $payload->data);
            }
        } catch (\Throwable $th) {
            // 处理失败，给服务端发送失败信息，在关闭
            $conn->write($th->getMessage());
            $conn->close();
        }

    }

    public function storeClientConnect($conn)
    {
        $this->clients->attach($conn);
    }

    public function removeClientConnectByCon($conn)
    {
        foreach ($this->clients as $client) {
            if ($conn === $client) {
                $this->clients->detach($conn);
                break;
            }
        }
    }

    public function getclientsCount()
    {
        return  $this->clients->count();
    }


    // event

    public function authenticated($conn, $data)
    {

        $conn->authenticate = true;
        // 客户端链接
        $conn->data = $data;
        $status = $data->status ?? '';

        if ($status == 'success') {
            StdoutStreamManager::createManager()->write("您的访问链接为:\n". $data->host);
        }
        $this->storeClientConnect($conn);
    }


    public function createProxy($conn, $data)
    {
        \Ratchet\Client\connect('wss://reactphp-server.reactphp.wpjs.cc/proxy')->then(function($conn) use ($data) {
            $conn->data = $data;
            $conn->on('message', function($msg) use ($conn) {
                ProxyManager::createManager()->handle($conn, $msg);
            });
            $conn->on('close', function($msg) use ($conn) {
                ProxyManager::createManager()->removeClientProxy($conn);
            });

            ProxyManager::createManager()->storeClientProxy($conn);
            
            // 给服务端发送创建代理链接,接下来服务端就可以开始发送请求了
            $conn->send(json_encode([
                'event' => 'createProxy',
                'data' => $data
            ]));
            ProxyManager::createManager()->request($conn);
        }, function ($e) use ($conn) {
            StdoutStreamManager::createManager()->write("Could not connect: {$e->getMessage()}\n");
            // $this->conn->data->data = $e->getMessage();
            // $conn->clientConnect->write(json_encode([
            //     'event' => 'create_proxy_fail',
            //     'data' => $this->conn->data,
            // ]));
        });
    }

}