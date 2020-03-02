# V2ray for whmcs.

### 使用方式:
1. 安装modules中的插件到whmcs, 写入license.
2. 在插件中新建服务器, 执行一件安装脚本, 放开防火墙
3. 新建产品, 插件选择 Wray, 并配置流量和付款周期等信息
4. 在产品配置页点击配置更多或直接到插件配置中心配置产品, 选择该产品可连接的服务器.
5. Enjoy!


### 试用
安装插件后, 使用插件的"安装测试服务器"功能无须license.

### Config:
```yaml
logging:
  stdout: info #输出的log level
  file: notice #写入到文件的log level
tls:
  cert: # 如果启用了tls, 证书地址 (fullchain.pem)
  key: # 如果启用了tls, 密钥地址 (privkey.pem)
api: # 一下一般不用动
  port: 6787
  inbound-tag: proxy
  level: 0
whmcs:
  host: http://whmcs.com #访问api的host, 同时验证授权用.
  token: demo # 如果是 demo就是测试模式, 否则是验证的token, 可从复制一键安装脚本中取得
  timeout: 5 #访问api的timeout
  duration:
    pull_user: 60 # 拉去用户的周期
    push_stat: 60 # 发送统计数据的周期
    push_load: 60 # 发送负载数据的周期
```

## Contact: t.me/GoriGorgeSency

## 后端:
可从releases下载.
