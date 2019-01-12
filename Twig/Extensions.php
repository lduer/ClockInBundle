<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Twig;

use App\Twig\Extensions as ExtensionBase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends ExtensionBase
{
    /**
     * @var string[]
     */
    protected static $icon_additions = [
        'pause' => 'fas fa-pause',
    ];

    /**
     * TODO: move this to separate bundle?
     * Note: This TWIG-Extension does not belong to the clock-in-bundle alone
     *
     * Extensions constructor.
     * @param RequestStack $requestStack
     * @param array $languages
     */
    public function __construct(RequestStack $requestStack, array $languages)
    {
        parent::$icons = array_merge(parent::$icons, self::$icon_additions);

        parent::__construct($requestStack, $languages);
    }
}
