<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Homepage controller is a redirect controller with user specific logic.
 *
 * @Route(path="/")
 * @Security("is_granted('ROLE_USER')")
 */
class HomepageController extends AbstractController
{
    /**
     * @Route(path="/homepage", defaults={}, name="homepage2", methods={"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request)
    {
        // make me configurable via UserPreference
        $route = 'clock_in_index';

        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $language = $user->getPreferenceValue('language', $locale);

        return $this->redirectToRoute($route, ['_locale' => $language]);
    }

    /**
     * @Route(path="/clock-in", defaults={}, name="clock_in_redirect
     * ", methods={"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function clockInAction(Request $request)
    {
        return $this->redirectToRoute('clock_in_index');
    }
}
