<?php

namespace Wizart\Tech\Subscriber;

use Twig\Environment;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class WizartListener
{
    private $twig;

    private $token;
    private $enable = false;

    public function __construct(ParameterBagInterface $parameterBag, Environment $twig, RequestStack $requestStack)
    {
        $this->twig         = $twig;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
    }

    public function isProfiler()
    {
        $request = $this->requestStack->getCurrentRequest();
        $route = $request->get('_route');
        return $route == "_wdt" || $route == "_profiler";
    }

    public function isEasyAdmin()
    {
        $request = $this->requestStack->getCurrentRequest();

        $controllerAttribute = $request->attributes->get("_controller");
        $array = is_array($controllerAttribute) ? $controllerAttribute : explode("::", $request->attributes->get("_controller"));
        $controller = explode("::", $array[0])[0];

        $parents = [];
        $parent = $controller;

        while(class_exists($parent) && ( $parent = get_parent_class($parent)))
            $parents[] = $parent;

        $eaParents = array_filter($parents, fn($c) => str_starts_with($c, "EasyCorp\Bundle\EasyAdminBundle"));
        return !empty($eaParents);
    }

    private function allowRender(ResponseEvent $event)
    {
        if (!$event->isMainRequest())
            return false;

        if (!$this->enable)
            return false;

        if (!$this->autoAppend)
            return false;

        if($this->isEasyAdmin())
            return false;

        if($this->isProfiler())
            return false;

        $contentType = $event->getResponse()->headers->get('content-type');
        if ($contentType && !str_contains($contentType, "text/html"))
            return false;

        return true;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) return;

        $this->enable     = $this->parameterBag->get("wizart.enable");
        if (!$this->enable) return;

        $this->autoAppend = $this->parameterBag->get("wizart.autoappend");
        $this->token      = $this->parameterBag->get("wizart.token");
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

        if(!is_instanceof($response, [StreamedResponse::class, BinaryFileResponse::class]))
            $response->setContent($content);

        return true;
    }
}
