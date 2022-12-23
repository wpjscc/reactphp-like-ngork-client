<?php

namespace App\Client;

use App\Helper\Helper;
use App\Output\StdoutStreamManager;
use RingCentral\Psr7;

class ProxyManager {

    private static $manager;

    public $proxys;

    public $proxyCount=0;

    public function __construct()
    {
        $this->proxys = new \SplObjectStorage;
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
            } else {
                if  ($payload->event ?? ''){
                    StdoutStreamManager::createManager()->write('['.$payload->event.']');
                    $conn->emit($payload->event, [$payload->data]);
                }
            }
        } catch (\Throwable $th) {
            //todo 处理错误信息
            $conn->send($th->getMessage());
            $conn->close();
            StdoutStreamManager::createManager()->write($th->getMessage());

        }
    } 

    public function storeClientProxy($from)
    {
        $this->proxyCount++;
        // $this->proxys->attach($from);
    }

    public function removeClientProxy($from)
    {
        // $this->proxys->detach($from);
        $this->proxyCount--;
    }

    public function getClientProxysCount()
    {
        return $this->proxyCount;
        return  $this->proxys->count();
    }

    // event 

    public function request_message($conn, $data)
    {
        $conn->emit('request_message', [Helper::decode($data)]);
    }
    public function websocket_request_message($conn, $data)
    {
        $conn->emit('websocket_request_message', [Helper::decode($data)]);
    }


    public function request($conn)
    {
        StdoutStreamManager::createManager()->write('request-1');

        $buffer = '';
        $conn->on('request_header', $fn = function($message) use (&$fn, $conn, $buffer) {
            StdoutStreamManager::createManager()->write('request-2');

            $buffer .= $message;
            $pos = strpos($buffer, "\r\n\r\n");
            if ($pos !== false) {
                $conn->removeListener('request_header', $fn);
                $fn = null;
                // try to parse headers as response message
                try {
                    $request = Psr7\parse_request(substr($buffer, 0, $pos));
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    return;
                }

                $buffer = (string)substr($buffer, $pos + 4);
                $this->connectLocal($conn, $request, $buffer);

            }
        });
    }

    public function connectLocal($conn, $request, $buffer)
    {
        StdoutStreamManager::createManager()->write('request-3232');
        StdoutStreamManager::createManager()->write(json_encode($conn->data));
        if ($conn->data->is_websocket ?? false) {
            $this->connectWebsocket($conn, $request, $buffer);
        } else {
            $this->connectHttp($conn, $request, $buffer);
        }
        return ;
      
    }

    public function connectHttp($conn, $request, $buffer)
    {

        $client = new \React\Http\Browser();

        $body = new \React\Stream\ThroughStream();
        $conn->on('request_message', function($data) use ($body){
            $body->write($data);
        });

        $conn->on('request_end', function() use ($body) {
            $body->end();
        });

        
        StdoutStreamManager::createManager()->write('request-uri:'.(string) $request->getUri());
        
        $client->withFollowRedirects(false)->withRejectErrorResponse(false)->withTimeout(true)->requestStreaming($request->getMethod(), (string) $request->getUri(), $request->getHeaders(), in_array($request->getMethod(), ['get', 'head']) ?"":$body)
        ->then(function (\Psr\Http\Message\ResponseInterface $response) use ($conn){
            StdoutStreamManager::createManager()->write('request-5:');
            
            $this->handleHttpResponse($conn, $response);

        }, function (\Exception $e) use ($conn) {
            if (method_exists($e, 'getResponse')) {
                StdoutStreamManager::createManager()->write('request-4:'.$e->getMessage());

                $response = $e->getResponse();
                $this->handleHttpResponse($conn, $response);
            } else {
                // $this->conn->data->data = $e->getMessage();
                // $e->getTraceAsString()
                // $this->conn->data->code = $e->getCode();
                // $this->write(json_encode([
                //     'event' => 'proxy_response_fail',
                //     'data' => $this->conn->data,
                // ]));
            }
           
        });
        
        

        if ($buffer !== '') {
            $body->write($buffer);
            $buffer = '';
        }
    }


    protected function handleHttpResponse($conn, $response)
    {
        // 把header发送出去
        $conn->send(json_encode([
            'event' => 'response_header',
            'data' => Helper::toString($response)
        ]));
        $body = $response->getBody();
        assert($body instanceof \Psr\Http\Message\StreamInterface);
        assert($body instanceof \React\Stream\ReadableStreamInterface);

        $body->on('data', function ($chunk) use ($response, $conn) {
            // if (($acceptRanges = $response->getHeader('Accept-Ranges')) && in_array('bytes', $acceptRanges)) {
            //     $chunk = base64_encode($chunk);
            // }
            $conn->send(json_encode([
                'event' => 'response_message',
                'data' => Helper::encode($chunk),
            ]));
            // $conn->send($chunk);
            StdoutStreamManager::createManager()->write('[DATA]');
        });

        $body->on('error', function (\Exception $e) use ($body, $conn) {
            $conn->send(json_encode([
                'event' => 'response_message',
                'data' => $e->getMessage(),
            ]));
            $body->close();
        });

        $body->on('close', function ($data='') use ($conn) {
            // $conn-close();
            StdoutStreamManager::createManager()->write('[response-body-close][DONE]');
            $conn->send(json_encode([
                'event' => 'response_end',
                'data' => '',
            ]));
        });
        // 响应流结束了
        $body->on('end', function () use ($body) {
            // $body->close();
        });
    }

    public function connectWebsocket($conn, $request, $buffer = '')
    {

        StdoutStreamManager::createManager()->write('[websocket]'. $conn->data->local_host);
      
        
        $tcpConnector = new \React\Socket\TcpConnector();

        $tcpConnector->connect($conn->data->local_host)->then(function (\React\Socket\ConnectionInterface $connection) use ($conn, $request, &$buffer) {
            StdoutStreamManager::createManager()->write('2222'.$buffer."33333");

            $connection->write(Helper::toString($request));
            // if ($buffer) {
            //     StdoutStreamManager::createManager()->write('2222'.$buffer."5555");

            //     $connection->write($buffer);
            // }
            $buffer = '';
            $connection->on('data', $fn = function ($msg) use ($conn, &$fn, $connection, &$buffer) {
                StdoutStreamManager::createManager()->write('[data]'.$msg);
                $buffer.= $msg;
                $pos = strpos($buffer, "\r\n\r\n");
                if ($pos !== false) {
                    $connection->removeListener('data', $fn);
                    $conn->send(json_encode([
                        'event' => 'websocket_response_header',
                        'data' => $buffer,
                    ]));
                    $buffer = '';
                    $connection->on('data', function($msg) use ($conn){
                        StdoutStreamManager::createManager()->write('[data]'.$msg);
                        $conn->send(json_encode([
                            'event' => 'websocket_response_message',
                            'data' => Helper::encode($msg),
                        ]));
                    });

                    // 后续websocket请求
                    $conn->on('websocket_request_message', function($msg) use ($connection) {
                        StdoutStreamManager::createManager()->write('[websocket_request_message]');
                        $connection->write($msg);
                    });
                } 
               
            });
            
            $connection->on('end', function ($msg = '') use ($conn) {
                StdoutStreamManager::createManager()->write('[websocket-end]');
                StdoutStreamManager::createManager()->write('[websocket_response_end]');
                $conn->send(json_encode([
                    'event' => 'websocket_response_end',
                    'data' => $msg
                ]));
            });
            
            $connection->on('error', function (\Exception $e) {
                StdoutStreamManager::createManager()->write('[websocket-error]'. $e->getMessage());
            });
            
            $connection->on('close', function ($msg = '') use ($conn)  {
                StdoutStreamManager::createManager()->write('[websocket-closed]');
                StdoutStreamManager::createManager()->write('[websocket_response_end]');

                $conn->send(json_encode([
                    'event' => 'websocket_response_end',
                    'data' => $msg
                ]));
                $conn->close();
            });

            
            $conn->on('close', function($data = '') use ($connection) {
                StdoutStreamManager::createManager()->write('[proxy-websocket-closed]');

                $connection->removeAllListeners('close');
                $connection->close($data);
            });
            
        }, function(\Exception $e){
            
            StdoutStreamManager::createManager()->write('[websocket-fail]'. $e->getMessage());
        });
    }

}