# 1.dreamyi12/apidoc
    
它是一个[Hyperf](https://github.com/hyperf/hyperf)框架的 [**api接口自动验证和swagger接口文档生成**] 组件.  
功能包括：
- 通过注解定义接口路由、请求方法和参数,并由中间件自动校验接口参数.
- 生成json文件,供swagger接口文档测试使用,可打开或关闭.
- swagger支持接口多版本分组管理.
- 支持restful path路由参数校验.
- 支持验证自定义枚举类
- 支持验证通过后的参数自动参与ORM条件参与
- 支持自定义ORM中casts类型

支持：
- php 7.3~7.4
- hyperf 2.2

不支持：
- php 8.x


### 0.安装插件

```
# composer安装本组件
composer require dreamyi12/apidoc

# 发布组件初始化配置文件到你的项目下
php bin/hyperf.php vendor:publish dreamyi12/apidoc
```
