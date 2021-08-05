<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/4/16
 * Time: 15:48
 * Desc: 控制器接口
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Controller;

interface ControllerInterface {


    /**
     * 获取结构-基本响应体(键值对数组)
     * @return array
     */
    public static function getSchemaResponse(): array;


    /**
     * 操作成功响应
     * @param array $data
     * @param string $message
     * @param int $code
     * @return array
     */
    public function success($data = [], string $message = '', int $code = 200): array;


    /**
     * 操作失败响应
     * @param string $message
     * @param int $code
     * @return array
     */
    public function error(string $message = '', int $code = 200): array;



}