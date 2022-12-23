## git clone

gitee
```
git clone https://gitee.com/wpjscc/reactphp-like-ngork-client

```

或github
```
git clone https://github.com/wpjscc/reactphp-like-ngork-client

```

## 获取token



## 使用

```
php proxy_client.php 127.0.0.1 token
```

验证成功后会返回一个链接，访问该链接

## 例子

穿透一个本地虚拟域名

```
php proxy_client.php local.test token
```

穿透一个http 端口

```
php proxy_client.php 192.168.1.2:8080 token
```


穿透一个 websocket 

```
php proxy_client.php 192.168.1.2:8080 token is_websocket
```

添加一个参数is_websocket即可，链接时使用
ws://返回的域名

或

wss://返回的域名

