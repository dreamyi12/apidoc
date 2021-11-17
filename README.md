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

## 介绍
一个可以大大节省开发时间的组件
在我们使用hyperf开发过程中，经常会出现的问题，而该组件能帮忙解决以下问题。接下来让我们来一一描述

1.每次开发接口都需要写详细的接口文档，并且还需要建立路由，非常麻烦

2.使用默认的验证规则可能无法达到我们现在项目的要求，而hyperf提供的自定义验证必须要定义到监听器，非常不利于管理

3.hyperf提供的枚举类无法一次获得所有的枚举类型或者，通过枚举文本获得枚举值

4.hyperf自带的Annotation收集器每次都需要循环遍历才能取到自己需要的东西，很影响执行效率

5.每次进行参数经过验证之后还需要做一系列的判断才能去做增删改查，如下代码

```
        if (isset($param['xxx'])) {
            $model = $model->where('xxx',$param['xxxx']);
        }
        if (isset($param['xxx'])) {
            $model = $model->where('xxx',$param['xxxx']);
        }
       $model->get();
       //$model->update($data);
       //$model->delete();
       ....
```

该组件提供主要的功能就是解决以上所有问题

## 一.安装插件

```
# composer安装本组件
composer require dreamyi12/apidoc

# 发布组件初始化配置文件到你的项目下
php bin/hyperf.php vendor:publish dreamyi12/apidoc
```
## 二.使用
### 1.使用注解方式生成文档
通过注解的方式自动生成API接口文档以及路由，可使用RESTful API方式进行构造
```
use Dreamyi12\ApiDoc\Annotation\{ApiController, ApiResponse, ApiVersion, Delete, Get, Post, Put, Form, Query};
use App\Common\Controller\Controller;
use Dreamyi12\ApiDoc\Condition\Condition;
use Hyperf\Di\Annotation\Inject;

/**
 * @ApiController (tag="文档组描述", prefix="/auth", description="描述")
 * @ApiVersion (group="v1", description="第一版本")
 */
class IndexController extends Controller
{
    /**
     * @Inject
     * @var Service
     */
    protected $service;

    /**
     * @Post(path="/路径", description="描述", summary="简介")
     * @Form(key="XXX|字段描述", rule="验证规则") //包含所有hyperf自带验证 多个验证用|隔开
     * @Form(key="XXX|字段描述", rule="required|")
     * @ApiResponse (code="200", schema={"$ref":"Response"})
     */
    public function post()
    {
        return;
    }
    
    /**
     * @Get(path="/路径", description="描述", summary="简介")
     * @Query(key="XXX|字段描述", rule="验证规则") //包含所有hyperf自带验证 多个验证用|隔开
     * @Query(key="XXX|字段描述", rule="验证规则") //包含所有hyperf自带验证 多个验证用|隔开
     * @ApiResponse (code="200", schema={"$ref":"Response"})
     */
    public function get()
    {
        return ;
    }
    
    /**
     * @Put(path="/路径", description="描述", summary="简介")
     * @Form(key="XXX|字段描述", rule="验证规则") //包含所有hyperf自带验证 多个验证用|隔开
     * @Form(key="XXX|字段描述", rule="required|")
     * @ApiResponse (code="200", schema={"$ref":"Response"})
     */
    public function edit()
    {
        return ;
    }
    
    /**
     * @Delete(path="/路径", description="描述", summary="简介")
     * @Form(key="XXX|字段描述", rule="验证规则") //包含所有hyperf自带验证 多个验证用|隔开
     * @Form(key="XXX|字段描述", rule="required|")
     * @ApiResponse (code="200")
     */
    public function delete()
    {
        return ;
    }
```
写完生成文档的注解之后，启动hyperf将会自动启动生成文档到public/swagger目录下面，使用nginx
代理到对应的目录即可展示

### 2.自定义验证规则
在任意目录简历自定义的验证类，并且继承Dreamyi12\ApiDoc\Validation\Rule\CustomValidatorFactory类实现
handle方法，并且在你的类上加上CustomValidator注解，如下：
```
use Dreamyi12\ApiDoc\Annotation\Collector\CustomCollector;
use Dreamyi12\ApiDoc\Annotation\Enums\EnumClass;
use Dreamyi12\ApiDoc\Annotation\Validator\CustomValidator;
use Dreamyi12\ApiDoc\Validation\Rule\CustomValidatorFactory;

/**
 * @CustomValidator(name="enum")
 **/
class EnumValidator extends CustomValidatorFactory
{
    public function handle(array $data, $value, string $field, string $filed_name, array $options = []): array
    {
        if (empty($options)) {
            return [true, ''];
        }
        [$enumType] = $options;
        $enumClass = CustomCollector::getAnnotationByClasses(EnumClass::class, $enumType);
        $enums = $enumClass::getEnums();
        $err = $this->translator->trans('validation.enum', ['attribute' => $filed_name]);
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!empty($item) && !isset($enums[$item])) return [false, $err];
            }
        } else {
            return [isset($enums[$value]), $err];
        }
        return [true, ''];
    }

}
```
这是一个验证枚举类的例子

### 3.枚举类更加强大
在任意地方简历你需要的枚举类并且继承Dreamyi12\ApiDoc\Enum\EnumConstants，并定义EnumClass和Constants注解
```
use Dreamyi12\ApiDoc\Annotation\Enums\EnumClass;
use Dreamyi12\ApiDoc\Enum\EnumConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @EnumClass(name="sex")
 * @Constants
 */
class Sex extends EnumConstants
{
    /**
     * @Text("男")
     * @Head("/xxxx/xxx.png")
     */
    const MAN = 1;

    /**
     * @Text("女")
     * @Head("/xxxx/xxx.png")
     */
    const GIRL = 2;
}

//定义完成之后在任意地方都可以获取到定义的数据

Sex::getEnums() //获得定义的所有枚举数据  [['value'=>1,'Text'=>'男','Head'=>'/xxxx/xxx.png'], ...]
Sex::getValues() //获得定义的所有枚举值数组 [1,2];

Sex::getText(1) //根据值获得获得定义的Text  男
Sex::getHead(1) //根据值获得定义的Head  /xxxx/xxx.png

//当然可以根据你需求定义更多的注解，不管是Text或Head还是Content等等，取法一样

Sex::getTextValue("男") //根据定义的Text获得值  1
Sex::getHeadValue("/xxxx/xxx.png") //根据定义的HeadValue获得值

```
定义的枚举类型可以配合自定义验证的枚举增加到注释当中去，后续我们会讲

### 4.定义Annotation收集器
我们可以在任意地方（类）定义注解收集器,只需要继承Dreamyi12\ApiDoc\Annotation\Abstracts\CustomAnnotation并且
定义Annotation和Target注解
```
use Dreamyi12\ApiDoc\Annotation\Abstracts\CustomAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class CastsClass extends CustomAnnotation {

    /**
     * 收集名称
     * @var string
     */
    public $name;


//定义好注解之后,比如我们要收集控制器
//只需要在需要收集的类上定义CastsClass注解并给一个name参数
//在我们需要使用的时候可以通过
CustomCollector::getAnnotationByClasses(CastsClass::class); //获得所有收集的类
CustomCollector::getAnnotationByClasses(CastsClass::class,'test'); //获得收集类定义的name为test的类
//你将得到一个数组，这个数组包含所有收集的类的命名空间地址
```
### 5.高级定义注解ORM操作
在这里支持查询时自动注入到ORM的操作，以控制器的注解为例，详细我们看代码
```
/**
     * @Get(path="/路径", description="描述", summary="描述")
     * @Query(key="name|姓名", rule="filled", where={"op":"like"})  
     * @Query(key="sex|性别", rule="filled|enum:sex", where={"op":"eq"})
     * @Query(key="age|年龄", rule="filled|max:100", where={"op":"gt"})
     * //以上常规的查询有:eq(=) neq(!=) gt(>) lt(<) egt(>=) elt(<=) like(like %xx%) left_like(like %xx) right_like(like xx%)  in  not_in  between notBetween
     * //还支持一下 has 
     * @Query(key="school|学校", rule="filled", where={"op":"has","with":"School","type":"like","field":"name"})
     * @Query(key="level|学校级别", rule="filled", where={"op":"has","with":"School","type":"like","field":"level"})
     * //以上会自动组建hasWhere，其中with为定义的关联关系，type为操作方式 field为需要查询的字段如果field不填则默认取key中的字段名
     * //支持where   
     * @Query(key="weight|体重", rule="filled", where={"op":"when","key":"v1","type":"eq"})
     * @Query(key="high|身高", rule="filled", where={"op":"when","key":"v1","type":"eq"})
     //以上的key为需要分组的键 type为操作方式 最终会生成sql： (weight = xx and high = xx)  
     //就是会把key相同的放到一个括号中去
     //还有更多其他的字段 mode 可以设置条件方式：如 and 或者 or  不写默认and
     // symbol 可以自动分割，例如查询一个区间，10-20 岁 如果设置symbol = "-"  可以配合between自动切割 为10-20的一个区间查询
     // function 可以设置一个自定义的处理函数，例如将function设置为base64_encode 那么将会自动将参数变成base64
     * @ApiResponse (code="200", schema={"$ref":"Response"})
     */
    public function page()
    {
         //例如需要查询模型中的数据，需要Student继承 Dreamyi12\ApiDoc\Model\BaseModel 然后调用condition方法
         Student::condition()->get();
         //获得设置了where的参数
         $data = Condition::getValidatorWhere(); 
         //获得通过验证的所有参数
         $data = Condition::getValidatorData();
    }
    
    //以上方式基本涵盖了所有应该的查询方式。还支持参数设置为数组，例如：
    Query(key="student.weight|体重", rule="filled", where={"op":"when","key":"v1","type":"eq"})
    Query(key="student.*.weight|体重", rule="filled")
    Query(key="student.*.*.weight|体重", rule="filled")
```
以上为基本的组件简述，如果有不懂的，可以及时留言，我会第一时间回复