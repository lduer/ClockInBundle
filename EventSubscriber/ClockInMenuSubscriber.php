<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClockInMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::CONFIGURE => ['onMainMenuConfigure', 200],
        ];
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $event->getMenu()->addItem(
            new MenuItemModel('clock-in', 'menu.clock-in', 'clock_in_index', [], 'fas fa-user-clock')
        );
    }
}
