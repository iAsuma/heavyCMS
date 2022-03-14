
基于iAsuma/layui-tp51-admin升级 ，主要迁移thinkphp5.1LTS框架至6.0LTS版本

TODO:shop模块路由尚未适配

iAsuma/heavyCMS（伪）纯净轻盈的后台管理系统
===============

想较于QingCMS比较重，但tp6.0的多应用模式是低耦合的设计，所以如果不需要相应的模块（应用），只需要删除对应的文件夹即可

内置扩展：

+ 集成微信开发SDK -- EasyWeChat（[手册传送门](https://www.easywechat.com/docs/master/overview)）
> 若不需要，删除composer.json中的`"overtrue/wechat": "~4.0" ` (安装前) 或 `composer remove overtrue/wechat` (安装后)

使用本框架需提前准备以下环境：

+ php7.4以上
+ Mysql5.7以上
+ Redis

## 安装步骤

### composer安装(推荐)

```
composer create-project iasuma/heavycsm your-project-name
```


### git安装
1.git下载项目源文件

~~~
git clone https://github.com/iAsuma/layui-tp51-admin.git my-project
~~~

2.使用Composer安装thinkphp框架以及依赖库

~~~
cd my-project
composer install
~~~

3.复制环境变量文件

~~~
cp .env.example .env
~~~

4.修改.env环境变量配置文件

~~~
vi .env #根据项目实际情况进行修改
~~~
~~~
DB_HOST = 127.0.0.1
DB_NAME = my_db
DB_USER = root
DB_PWD = 123456
~~~

5.完成。 根据自身情况部署web环境

~~~
http://your-domain.com/admin
~~~

> 使用本系统默认视为已熟悉PHP Web开发，熟悉Thinkphp6.0，熟悉LNMP开发项目，请自行部署Web访问环境
> 安装后请使用域名访问本系统，或者放在Web环境根目录

### 使用百度UEditor

在模板中需要使用富文本编辑器的地方引入以下代码
~~~
{include file="public/ueditor" name=""}
~~~

>`name`为form表单域字段名称

若需要初始化编辑器内容
~~~
{include file="public/ueditor" name=""}
<input type="hidden" id="hidden_content" value="这里是初始化的内容">
~~~

## 作者

+ Asuma (阿斯玛)
+ [微博](https://weibo.com/770878450)
+ [个人网站](http://www.udzan.com/)

## 在线手册

+ [ThinkPHP 6.0完全开发手册](https://www.kancloud.cn/manual/thinkphp6_0/content)

+ [Layui官方文档](https://www.layui.com/doc/)


## 声明

**本系统仅供交流学习使用，请勿作商业用途发布**

**若使用本系统涉及到layuiAdmin，请认真阅读[《layui 付费产品服务条款》](https://fly.layui.com/jie/26280/)，并自行到[layui官网](https://www.layui.com/admin/)下载源码**

基于开源的ThinkPHP6.0官方源码二次开发

前端组件使用开源的Layui前端UI框架

开源协议请参阅 [LICENSE.txt](LICENSE.txt)
