<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/4/16
 * Time: 16:32
 * Desc: 基本控制器
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Context;
use Kph\Objects\BaseObject;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BaseController
 * @package Dreamyi12\ApiDoc\Controller
 */
abstract class BaseController extends BaseObject implements ControllerInterface
{

    use SchemaModel;

    /**
     * 全局容器Hyperf\Di\Containe
     * @Inject
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;


    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;


    /**
     * @var array 接口响应的基本json结构
     */
    protected static $baseSchema = [
        'status' => true,
        'message' => 'success',
        'code' => 200,
        'data' => [],
    ];


    /**
     * 获取结构-基本响应体(键值对数组)
     * @return array
     */
    public static function getSchemaResponse(): array
    {
        return self::$baseSchema;
    }


    /**
     * 处理接口成功数据
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return array
     */
    public function success($data = [], string $message = '', int $code = 200): array
    {
        $message = is_string($data) ? $data : $message;
        return [
            'status' => true,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
    }


    /**
     * 处理接口失败数据
     * @param string $message
     * @param int $code
     * @return array
     */
    public function error(string $message = '', int $code = 200): array
    {
        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
            'data' => [],
        ];
    }

    /**
     * 初始化方法(在具体动作之前执行).
     * 不会中止后续具体动作的执行.
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    //    public function initialization(ServerRequestInterface $request): ServerRequestInterface {
    //        //自定义处理逻辑,如 将数据存储到$request属性中
    //        $request = $request->withAttribute('test', 'hello world');
    //
    //        //然后在具体动作里面获取数据
    //        $test = $request->getAttribute('test');
    //
    //        return $request;
    //    }


    /**
     * 拦截方法(在具体动作之前执行).
     * 当返回非空的数组或字符串时,将中止后续具体动作的执行.
     * @param string $controller 控制器类名
     * @param string $action 方法名(待执行的动作)
     * @param string $route 路由(url)
     * @return array|null
     */
    //    public function interceptor(string $controller, string $action, string $route) {
    //        if (false) {
    //            return self::doFail();
    //        }
    //
    //        return null;
    //    }


    /**
     * 后置方法(在具体动作之后执行,无论是否执行了拦截方法).
     * @param ServerRequestInterface $request
     * @param PsrResponseInterface $response
     */
    //    public function after(ServerRequestInterface $request, PsrResponseInterface $response): void {
    //        $uri  = $request->getRequestTarget();
    //        $code = $response->getStatusCode();
    //        printf("after action finish: code[%d] url[%s]\r", $code, $uri);
    //    }


    protected function getCondition()
    {
        $where = Context::get('validator.where');
        if (empty($where)) return false;
    }
}