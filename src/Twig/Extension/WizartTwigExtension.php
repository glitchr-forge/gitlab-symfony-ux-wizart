<?php

namespace Wizart\Tech\Twig\Extension;

use App\Entity\Marketplace\Product;
use App\Entity\Marketplace\Product\Extra\Wallpaper;
use App\Entity\Marketplace\Review;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

final class WizartTwigExtension extends AbstractExtension
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getName()
    {
        return 'wizart_extension';
    }
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wizart_is_ready', [$this, 'isReady'], ['is_safe' => ['all']]),
        ];
    }

    public function isReady()
    {
        return $this->parameterBag->get("wizart.token");
    }
}
