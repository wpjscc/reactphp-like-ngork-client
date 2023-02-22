## 特性

* 穿透http协议
* 穿透 websocket协议
* 支持 https
    * 第一次使用时，https证书可能两分钟后生效 

## 安装

gitee
```
git clone https://gitee.com/wpjscc/reactphp-like-ngork-client

```

或github
```
git clone https://github.com/wpjscc/reactphp-like-ngork-client

```

## 获取token

可选择一个token使用 
* ICnw6YVCRZp0KkInR9oMSR0ejeyPQNin
* vMbcnz0rQADJFeJraLvV4NpMZ2mnC6PI
* u0P8USK9XEwnYQ4zZy55DuyOxOhb5u35
* xo18g6QEvH1H0edtXgNlxR8gPcaFTldC

> 注意该token可能被占用，或定期被更新。token 不够用，可联系作者添加


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

## docker run

```
docker run -it --rm --network host wpjscc/reactphp-like-ngork-client php  proxy_client.php 192.168.1.1 ICnw6YVCRZp0KkInR9oMSR0ejeyPQNin
```
停止容器

```
docker ps | grep reactphp
```

```
docker stop containerId
```



## 交流

添加作者微信 amNjMjAxNDAxMjY=  暗号 “reactphp进群”

## 其他

* 技术栈是什么
    * php [reactphp](https://reactphp.org/)

