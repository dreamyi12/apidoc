<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/10
 * Time: 09:43
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc;

use Doctrine\Common\Annotations\AnnotationException;
use Dreamyi12\ApiDoc\Annotation\ApiController;
use Dreamyi12\ApiDoc\Annotation\Enums\EnumClass;
use Dreamyi12\ApiDoc\Annotation\Methods;
use Dreamyi12\ApiDoc\Annotation\Params;
use Dreamyi12\ApiDoc\Annotation\Validator\CustomValidator;
use Dreamyi12\ApiDoc\Controller\ControllerInterface;
use Dreamyi12\ApiDoc\Swagger\Swagger;
use Dreamyi12\ApiDoc\Validation\Validator;
use Hyperf\Config\Config;
use Hyperf\Di\Exception\ConflictAnnotationException;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Router\DispatcherFactory as BaseDispatcherFactory;
use Hyperf\Server\Exception\RuntimeException as ServerRuntimeException;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Hyperf\Validation\Concerns\ValidatesAttributes;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;


/**
 * Class DispatcherFactory
 * @package Dreamyi12\ApiDoc
 */
class DispatcherFactory extends BaseDispatcherFactory
{
    /**
     *
     * @var Config
     */
    private Config $config;

    /**
     * @var Swagger
     */
    public Swagger $swagger;

    /**
     * @var array
     */
    protected array $tmps;

    /**
     * @var array
     */
    protected array $customValidator = [];

    /**
     * @var array
     */
    protected array $enumClass = [];

    /**
     * DispatcherFactory constructor.
     */
    public function __construct()
    {
        $this->initConfig();
        $this->initSwagger();
        parent::__construct();
        $this->addRouteCache();
    }

    /**
     *Initialize basic configuration
     */
    private function initConfig(): void
    {
        $path = BASE_PATH . '/config/autoload/apihelper.php';
        $conf = file_exists($path) ? include $path : [];
        $this->config = new Config($conf);
    }

    /**
     * Initialize Swagger object
     */
    public function initSwagger(): void
    {
        $this->swagger = make(Swagger::class);
    }

    /**
     * @param array $collector
     * @throws ConflictAnnotationException
     */
    protected function initAnnotationRoute(array $collector): void
    {
        $middlewareData = [];
        $this->checkBaseController('');
        parent::initAnnotationRoute($collector);

        foreach ($collector as $className => $metadata) {
            if (isset($metadata['_c'][EnumClass::class])) {
                $class = $metadata['_c'][EnumClass::class];
                $this->enumClass[$class->name] = $className;
            }
            if (isset($metadata['_c'][CustomValidator::class])) {
                $class = $metadata['_c'][CustomValidator::class];
                $this->customValidator[$class->name] = $className;
            }
            if (isset($metadata['_c'][ApiController::class])) {
                $middlewares = $this->handleMiddleware($metadata['_c']);
                $middlewareData[] = ['middlewares' => $middlewares, 'class_name' => $className, 'metadata' => $metadata];
            }
        }
        foreach ($middlewareData as $middleware) {
            $this->parseController($middleware['class_name']);
            $this->handleController($middleware['class_name'], $middleware['metadata']['_c'][ApiController::class], ($middleware['metadata']['_m'] ?? []), $middleware['middlewares']);
        }
        $this->swagger->saveJson();
    }

    /**
     * Verify basic controller
     * @param string|null $className
     */
    private function checkBaseController(string $className = null)
    {
        $baseCtrlClass = $this->config->get('api.base_controller');
        if ($className) {
            $ctrlObj = new $className();
            if (!($ctrlObj instanceof $baseCtrlClass)) {
                throw new ServerRuntimeException("{$className} must extends from {$baseCtrlClass}.");
            } elseif (!($ctrlObj instanceof ControllerInterface)) {
                throw new ServerRuntimeException("{$baseCtrlClass} must implements " . ControllerInterface::class);
            }
        }
        if (empty($baseCtrlClass)) {
            throw new ServerRuntimeException("api.base_controller can not be empty.");
        } elseif (!class_exists($baseCtrlClass)) {
            throw new ServerRuntimeException("class: {$baseCtrlClass} does not exist.");
        }
    }

    /**
     * Analyze the corresponding controller
     * @param string $className
     */
    private function parseController(string $className): void
    {
        $this->checkBaseController($className);
        
        $refObj = new ReflectionClass($className);
        $methods = $refObj->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $methodObj) {
            $rules = [];
            $action = $methodObj->getName();
            $methodMetadata = ApiAnnotation::getMethodMetadata($className, $action);
            if ($methodObj->isStatic() || empty($methodMetadata)) continue;
            //Process parameter route validation
            //rules and initialize the corresponding rules
            foreach ($methodMetadata as $annotation) {
                if (!$annotation instanceof Params) continue;
                $paramType = class_basename($annotation);
                $fieldName = $this->getFieldByKey($annotation->key);
                if (!isset($rules[$paramType])) {
                    $rules[$paramType] = [];
                }
                $customs = $rules[$paramType]['customs'] ?? [];
                $frames = $rules[$paramType]['frames'] ?? [];
                [$customs[$annotation->key], $frames[$annotation->key]] = $this->getRuleProcess($annotation);
                if (!empty($anno->where)) {
                    $query_where[$fieldName] = $anno->where;
                }
                if (!empty($anno->function)) {
                    $function[$fieldName] = $anno->function;
                }
                if (!empty($anno->path)) {
                    $path[$fieldName] = $anno->path;
                }
                $rules[$paramType] = [
                    'path' => isset($path) ? $path : [],
                    'function' => isset($function) ? $function : [],
                    'where' => isset($query_where) ? $query_where : [],
                    'frames' => $frames,
                    'customs' => $customs,
                ];
            }
            $ctrlKey = $className . "::" . $action;
            $this->tmps[$ctrlKey] = $rules;
        }
    }

    /**
     * Get rule process processing
     * @param $annotation
     * @return array
     */
    protected function getRuleProcess($annotation)
    {
        $rules = $this->sortDetailRules(ApiAnnotation::parseByRule($annotation->rule));
        $customs = $frames = [];
        foreach ($rules as $rule) {
            $ruleName = ApiAnnotation::parseRuleName($rule);
            //Customize validation rules
            if (Arr::has($this->customValidator, $ruleName)) {
                $customs[] = $rule;
            }
            $frameMethod = Str::camel('validate_' . $ruleName);
            // Framework validation rules
            if (method_exists(ValidatesAttributes::class, $frameMethod)) {
                $frames[] = $rule;
            } else if (!array_key_exists($ruleName, $this->customValidator)) {
                throw new ServerRuntimeException("The rule not defined: {$ruleName}");
            }
        }
        return [Arr::sortRecursive($customs), Arr::sortRecursive($frames)];
    }


    public function sortDetailRules(array $rules): array
    {
        $priorities = ['default', 'required', 'int', 'integer', 'bool', 'boolean', 'number', 'numeric', 'float', 'string', 'array', 'object'];
        $res = [];
        foreach ($rules as $rule) {
            $lowRule = strtolower($rule);
            if (in_array($lowRule, $priorities)) {
                if ($lowRule == 'int') {
                    $rule = 'integer';
                } elseif ($lowRule == 'bool') {
                    $rule = 'boolean';
                } elseif ($lowRule == 'number') {
                    $rule = 'numeric';
                }
                array_unshift($res, $rule);
            } else {
                array_push($res, $rule);
            }
        }
        return $res;
    }

    /**
     * 从注解key中获取字段名
     * @param string $key
     * @return string
     */
    public static function getFieldByKey(string $key): string
    {
        $arr = explode('|', $key);
        $res = $arr[0] ?? '';
        return $res;
    }

    /**
     *
     * @param string $className
     * @param Controller $ControllerAnnotation
     * @param array $methodMetadata
     * @param array $middlewares
     * @throws AnnotationException
     * @throws ConflictAnnotationException
     */
    protected function handleController(string $className, Controller $ControllerAnnotation, array $methodMetadata, array $middlewares = []): void
    {
        if (empty($methodMetadata)) return;

        $router = $this->getRouter($ControllerAnnotation->server);
        $prefix = rtrim($ControllerAnnotation->prefix, '/');
        $basePath = $this->getPrefix($className, $prefix);
        $versions = ApiAnnotation::getVersionMetadata($className);
        $useVerPath = (bool)$this->config->get('api.use_version_path', true);
        $addPathToRouter = $this->structureRoute($router);
        foreach ($methodMetadata as $action => $annotations) {
            if (empty($annotations)) continue;
            $middlewares = array_unique(array_merge($middlewares, $this->handleMiddleware($annotations)));
            foreach ($annotations as $annotation) {
                if (!$annotation instanceof Methods) continue;
                $path = $basePath . '/' . $action;
                if (!empty($annotation->path)) {
                    $annotationPath = $annotation->path[0] === "/" ? $annotation->path : "/" . $annotation->path;
                    $isRouteParam = preg_match('/^{.*}$/', $annotationPath);
                    $path = $isRouteParam ? $basePath . '/' . $annotationPath : $prefix . $annotationPath;
                }
                //Add version number to routing path
                if (!empty($versions)) {
                    foreach ($versions as $version) {
                        $versionPath = $useVerPath ? ('/' . $version->group . $path) : $path;
                        $this->swagger->addPath($className, $action, $versionPath, $version);
                        $addPathToRouter($annotation->methods, $versionPath, [$className, $action], ['middleware' => $middlewares,]);
                    }
                } else {
                    $this->swagger->addPath($className, $action, $path);
                    $addPathToRouter($annotation->methods, $path, [$className, $action], ['middleware' => $middlewares,]);
                }
            }
        }
    }

    /**
     * Constructing routing rules
     * @param $router
     * @param array $routeAddress
     * @return \Closure
     */
    protected function structureRoute($router, $routeAddress = [])
    {
        return function ($httpMethod, string $route, $handler, array $options = [])
        use ($router, $routeAddress) {
            $routeKey = json_encode([$httpMethod, $route, $handler]);
            if (!in_array($routeKey, $routeAddress)) {
                array_push($routeAddress, $routeKey);
                $router->addRoute($httpMethod, $route, $handler, $options);
            }
        };
    }

    /**
     * Add route cache
     */
    protected function addRouteCache(): void
    {
        $apiAnnotation = new ApiAnnotation();
        $tmps = json_decode(json_encode($this->tmps));
        $apiAnnotation->setRouteCache($tmps);
        $apiAnnotation->setEnumsClass($this->enumClass);
        $apiAnnotation->setCustomValidator($this->customValidator);
        $this->tmps = [];
        $container = ApplicationContext::getContainer();
        $container->set(ApiAnnotation::class, $apiAnnotation);
    }

}
