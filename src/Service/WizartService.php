<?php

namespace Wizart\Tech\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WizartService
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var boolean
     */
    protected $enable;

    /**
     * construct
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->enable = $parameterBag->get("wizart.enable");
        $this->token  = $parameterBag->get("wizart.token");
    }

    public function getToken()
    {
        return $this->token;
    }
    public function setToken($token)
    {
        $this->token = $token;
    }

    public function isEnabled()
    {
        return $this->enable;
    }
}
