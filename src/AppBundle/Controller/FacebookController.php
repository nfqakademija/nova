<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class FacebookConnectController
 * @package AppBundle\Controller
 * @Route("facebook")
 */
class FacebookController extends Controller
{
    /**
     * @Route("/connect", name="app.facebook.connect")
     */
    public function connectAction()
    {
        $facebook = $this->get('app.facebook_authenticator');

        return new RedirectResponse($facebook->getLoginUrl());
    }

    /**
     * @Route("/check", name="app.facebook.check")
     */
    public function checkAction()
    {
        // This method will be handled by Guard so it's only a route
    }
}
