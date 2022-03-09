<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/9
 * Time: 16:35
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Middleware;

use Doctrine\Common\Annotations\AnnotationException;
use Dreamyi12\ApiDoc\Helpers\StringHelper;
use FastRoute\Dispatcher;
use Dreamyi12\ApiDoc\Annotation\Body;
use Dreamyi12\ApiDoc\Annotation\File;
use Dreamyi12\ApiDoc\Annotation\Form;
use Dreamyi12\ApiDoc\Annotation\Header;
use Dreamyi12\ApiDoc\Annotation\Path;
use Dreamyi12\ApiDoc\Annotation\Query;
use Dreamyi12\ApiDoc\ApiAnnotation;
use Dreamyi12\ApiDoc\Validation\ValidationInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\HttpServer\Server;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;


/**
 * Class ApiValidationMiddleware
 * @package Dreamyi12\ApiDoc\Middleware
 */
class ApiValidationMiddleware extends CoreMiddleware
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;


    /**
     * @Inject()
     * @var ValidationInterface
     */
    protected $validation;


    /**
     * ApiValidationMiddleware constructor.
     * @param ContainerInterface $container
     * @param HttpResponse $response
     * @param RequestInterface $request
     */
    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->response = $response;
        $this->request = $request;

        $server = $container->get(Server::class);
        $serverName = (string)$server->getServerName();

        parent::__construct($container, $serverName ? $serverName : "Unknown");
    }


    /**
     * 执行处理
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $routes = $this->dispatcher->dispatch($request->getMethod(), $uri->getPath());

        if ($routes[0] !== Dispatcher::FOUND) {
            return $handler->handle($request);
        }

        if ($routes[1] instanceof Handler) {
            if (is_string($routes[1]->callback) || is_array($routes[1]->callback)) {
                [$controller, $action] = $this->prepareHandler($routes[1]->callback);
            } else {
                return $handler->handle($request);
            }
        } else {
            [$controller, $action] = $this->prepareHandler($routes[1]);
        }

        $controllerInstance = $this->container->get($controller);
        $globalConf = $this->container->get(ConfigInterface::class);

        $doAfter = function (ResponseInterface $response): ResponseInterface {
            return $response;
        };

        $ruleObj = $this->container->get(ApiAnnotation::class)->getRouteCache();
        $ctrlAct = $controller . "::" . $action;
        $baseCtrlClass = $globalConf->get('apihelper.api.base_controller');
        if (isset($ruleObj->$ctrlAct)) {
            // 先处理BODY规则
            $typeBody = StringHelper::getClassNameByString(Body::class);
            if (isset($ruleObj->$ctrlAct->$typeBody)) {
                $data = [Body::NAME => $request->getBody()->getContents()];
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typeBody), $data, [], $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
                $request = $request->withBody(new SwooleStream($data[Body::NAME] ?? ''));
            }
            // 各请求方法的数据
            $headers = array_map(function ($item) {
                return $item[0] ?? null;
            }, $request->getHeaders());
            $queryData = $request->getQueryParams();
            $postData = $request->getParsedBody();
            $allData = array_merge($headers, $queryData, $postData);


            $typeHeader = StringHelper::getClassNameByString(Header::class);
            if (isset($ruleObj->$ctrlAct->$typeHeader)) {
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typeHeader), $headers, $allData, $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
            }

            $typePath = StringHelper::getClassNameByString(Path::class);
            if (isset($ruleObj->$ctrlAct->$typePath)) {
                $pathData = $routes[2] ?? [];
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typePath), $pathData, $allData, $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
            }

            $typeQuery = StringHelper::getClassNameByString(Query::class);
            if (isset($ruleObj->$ctrlAct->$typeQuery)) {
                //将默认值加入到数据当中
                if ($ruleObj->$ctrlAct->$typeQuery->default) {
                    foreach (get_object_vars($ruleObj->$ctrlAct->$typeQuery->default) as $field => $value) {
                        if (!isset($allData[$field]))
                            $allData[$field] = $value;
                    }
                }
                if (!empty($ruleObj->$ctrlAct->$typeQuery->where)) {
                    Context::set('validator.where', $ruleObj->$ctrlAct->$typeQuery->where);
                }
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typeQuery), $queryData, $allData, $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
                $request = $request->withQueryParams($data);
            }
            $typeForm = StringHelper::getClassNameByString(Form::class);

            if (isset($ruleObj->$ctrlAct->$typeForm)) {
                if (!empty($ruleObj->$ctrlAct->$typeForm->where)) {
                    Context::set('validator.where', $ruleObj->$ctrlAct->$typeForm->where);
                }
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typeForm), $postData, $allData, $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
                $request = $request->withParsedBody($data);
            }

            //文件上传
            $typeFile = StringHelper::getClassNameByString(File::class);
            if (isset($ruleObj->$ctrlAct->$typeFile)) {
                [$data, $error] = $this->checkRules(get_object_vars($ruleObj->$ctrlAct->$typeFile), $request->getUploadedFiles(), $allData, $controllerInstance);
                if (!empty($error)) {
                    return $doAfter($this->response->json($baseCtrlClass::validationFail($error)));
                }
                $request = $request->withUploadedFiles($data);
            }
        }

        Context::set(ServerRequestInterface::class, $request);
        $response = $handler->handle($request);

        return $doAfter($response);
    }


    /**
     * 执行规则检查
     * @param array $rules
     * @param array $data
     * @param array $otherData
     * @param object $controller
     * @return array
     */
    public function checkRules(array $rules, array $data, array $otherData, object $controller): array
    {
        [$validatedData, $errors] = $this->validation->validate($rules, $data, $otherData, $controller);
        $error = empty($errors) ? '' : current($errors);

        return [$validatedData, $error];
    }

}