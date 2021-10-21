<?php


namespace Dreamyi12\ApiDoc\Validation\Rule;


use App\Client\Common\Assist\ClientLogin;
use App\Enterprise\Common\Assist\EnterpriseLogin;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Phper666\JWTAuth\JWT;
use Phper666\JWTAuth\Util\JWTUtil;

abstract class CustomValidatorFactory implements CustomValidatorInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var
     */
    protected $login;

    /**
     * @Inject
     * @var JWT
     */
    protected $jwt;

    /**
     * @Inject
     * @var EnterpriseLogin
     */
    protected $enterpriseLogin;

    /**
     * @Inject
     * @var ClientLogin
     */
    protected $clientLogin;

    /**
     * @var
     */
    protected $adminLogin;


    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


}