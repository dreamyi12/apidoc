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

use Dreamyi12\ApiDoc\Swagger\Swagger;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Resource\Json\ResourceCollection;
use Hyperf\Utils\Context;
use Kph\Helpers\StringHelper;
use Kph\Helpers\ValidateHelper;
use Kph\Objects\BaseObject;
use Psr\Container\ContainerInterface;

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
     * @param $data
     * @param string $msg
     * @param int $code
     * @return array
     */
    public function success($data, string $message = '', int $code = 200)
    {
        if ($data instanceof ResourceCollection) {
            return $data->additional(['code' => $code, 'message' => $message])->toResponse();
        }
        return [
            'message' => is_string($data) ? $data : $message,
            'code' => $code,
            'data' => is_string($data) ? [] : $data,
        ];
    }


    /**
     * 处理接口失败数据
     * @param string $message
     * @param int $code
     * @return array
     */
    public function error(string $message = '', int $code = 200, $data = [])
    {
        return [
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
    }

    public static function validationFail(string $message = '', int $code = 400)
    {
        return [
            'message' => $message,
            'code' => $code,
            'data' => [],
        ];
    }

    /**
     * 根据结构名获取模型默认值
     * @param string $schemaStr
     * @param array $data
     * @return array
     */
    public static function getDefaultDataBySchemaName(string $schemaStr, array $data = []): array
    {
        $res = array_merge([], $data);
        [$schemaName, $schemaMethod] = Swagger::extractSchemaNameMethod($schemaStr);
        if (method_exists(static::class, $schemaMethod)) {
            $callback = [static::class, $schemaMethod];
            $schemaData = call_user_func($callback);
            if (is_array($schemaData)) {
                $res = array_merge($res, $schemaData);
            }
        }

        foreach ($res as &$item) {
            if (is_array($item) && !empty($item)) {
                $item = self::getDefaultDataBySchemaName('', $item);
            } elseif (is_string($item) && ValidateHelper::startsWith($item, '$')) {
                $str = StringHelper::removeBefore($item, '$', true);
                if (ValidateHelper::isAlphaNumDash($str)) {
                    $item = self::getDefaultDataBySchemaName($str, []);
                }
            }
        }

        return $res;
    }

    protected function getCondition()
    {
        $where = Context::get('validator.where');
        if (empty($where)) return false;
    }
}