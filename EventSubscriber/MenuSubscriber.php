<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 200],
        ];
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $event->getMenu()->addItem(
            new MenuItemModel('clock-in', 'menu.clock-in', 'clock_in_index', [], 'fas fa-user-clock')
        );
    }
}
