<?php

namespace Wizart\Tech\Subscriber;

use Twig\Environment;
use Base\Service\BaseService;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class WizartListener
{
    private $twig;

    private $token;
    private $enable = false;

    public function __construct(ParameterBagInterface $parameterBag, Environment $twig)
    {
        $this->twig   = $twig;
        
        $this->enable     = $parameterBag->get("wizart.enable");
        $this->autoAppend = $parameterBag->get("wizart.autoappend");
        $this->enable     = $parameterBag->get("wizart.enable");
        $this->token      = $parameterBag->get("wizart.token");
    }

    private function allowRender(ResponseEvent $event)
    {
        if (!$this->enable)
            return false;

        if (!$this->autoAppend)
            return false;

        $contentType = $event->getResponse()->headers->get('content-type');
        if ($contentType && !str_contains($contentType, "text/html"))
            return false;

        if (!$event->isMainRequest())
            return false;
        
        return true;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->enable) return;
        if (!$this->token) return;

        $entry_point   = "<script defer type='application/javascript' src='https://d35so7k19vd0fx.cloudfront.net/production/integration/entry-point.min.js'></script>";
        $javascripts = 
        "<script type='application/javascript'>
            const WizartVisualizer = function () {
                const entryPoint = new window.WEntryPoint({
                    token: '".$this->token."',
                    element: document.getElementsByTagName('body')[0]
                });
                entryPoint.open();
            };
        </script>";

        $this->twig->addGlobal("wizart", array_merge(
            $this->twig->getGlobals()["wizart"] ?? [],
            [
                "entry_point" => ($this->twig->getGlobals()["wizart"]["entry_point"] ?? "") . $entry_point,
                "javascripts"   => ($this->twig->getGlobals()["wizart"]["javascripts"] ?? "") . $javascripts
            ]
        ));
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->allowRender($event)) return false;

        $response    = $event->getResponse();
        $entry_point = $this->twig->getGlobals()["wizart"]["entry_point"] ?? "";
        $javascripts = $this->twig->getGlobals()["wizart"]["javascripts"] ?? "";

        $content = preg_replace([
            '/<\/head\b[^>]*>/',
            '/<body\b[^>]*>/',
        ], [
            $entry_point . "$0",
            "$0" . $javascripts,
        ], $response->getContent(), 1);

        $response->setContent($content);

        return true;
    }
}
