<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\FacebookUser;


class FacebookAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var \League\OAuth2\Client\Provider\Facebook
     */
    private $provider;

    /**
     * Facebook app id.
     * @var string
     */
    private $facebook_app_id;

    /**
     * Facebook app secret code.
     * @var string
     */
    private $facebook_app_secret;

    /**
     * Facebook Graph version for app.
     * @var string
     */
    private $facebook_app_version;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * FacebookAuthenticator constructor.
     *
     * @param string $facebook_app_id
     * @param string $facebook_app_secret
     * @param string $facebook_app_version
     * @param EntityManager $em
     * @param RouterInterface $router
     */
    public function __construct($facebook_app_id, $facebook_app_secret, $facebook_app_version, EntityManager $em, RouterInterface $router)
    {
        $this->facebook_app_id = $facebook_app_id;
        $this->facebook_app_secret = $facebook_app_secret;
        $this->facebook_app_version = $facebook_app_version;
        $this->em = $em;
        $this->router = $router;

        $this->createProvider();
    }

    /**
     * Initiate our facebook client provider.
     *
     * @return void
     */
    private function createProvider()
    {
        $this->provider = new Facebook([
            'clientId'          => $this->facebook_app_id,
            'clientSecret'      => $this->facebook_app_secret,
            'redirectUri'       => $this->router->generate('app.facebook.check', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'graphApiVersion'   => $this->facebook_app_version,
        ]);
    }

    /**
     * Creating and returning login url.
     *
     * @param array $permissions Facebook permissions
     * @return string
     */
    public function getLoginUrl($permissions = ['public_profile', 'email'])
    {
        return $this->provider->getAuthorizationUrl($permissions);
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Not used
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * For example, for a form login, you might:
     *
     *      if ($request->request->has('_username')) {
     *          return array(
     *              'username' => $request->request->get('_username'),
     *              'password' => $request->request->get('_password'),
     *          );
     *      } else {
     *          return;
     *      }
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
     *
     * @param Request $request
     *
     * @throws IdentityProviderException
     * @throws AuthenticationException
     *
     * @return mixed|null
     */
    public function getCredentials(Request $request)
    {
        // Skip authentication unless we're on facebook check URL!
        if ($request->getPathInfo() != $this->router->generate('app.facebook.check')) {
            return null;
        }

        if ($request->get('error', null)) {
            throw new AuthenticationException($request->get('error_reason', 'Unknown problem.'), $request->get('error_code', 200));
        }

        try {
            return $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);
        } catch (IdentityProviderException $e) {
            // Maybe we should do something but maybe later...
            // TODO: Handle IdentityProviderException
            throw $e;
        }
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @throws AuthenticationException
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var AccessToken $accessToken */
        $accessToken = $credentials;

        try {
            /** @var FacebookUser $facebookUser */
            $facebookUser = $this->provider->getResourceOwner($accessToken);

            // If user was logged before
            $user = $this->em->getRepository('AppBundle:User')->findOneBy(['facebookId' => $facebookUser->getId()]);
            if ($user) {
                return $user;
            }

            return $this->em->getRepository('AppBundle:User')->createNewFacebookUser($facebookUser);
        } catch (\Exception $e) {
            // Failed to get user details
            return null;
        }
    }

    /**
     * Returns true if the credentials are valid.
     *
     * If any value other than true is returned, authentication will
     * fail. You may also throw an AuthenticationException if you wish
     * to cause authentication to fail.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     *
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // Token worked, so everything is ok
        return true;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        $homepageUrl = $this->router->generate('homepage');
        return new RedirectResponse($homepageUrl);
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->router->generate('homepage'));
    }

    /**
     * Does this method support remember me cookies?
     *
     * Remember me cookie will be set if *all* of the following are met:
     *  A) This method returns true
     *  B) The remember_me key under your firewall is configured
     *  C) The "remember me" functionality is activated. This is usually
     *      done by having a _remember_me checkbox in your form, but
     *      can be configured by the "always_remember_me" and "remember_me_parameter"
     *      parameters under the "remember_me" firewall key
     *  D) The onAuthenticationSuccess method returns a Response object
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return true;
    }
}
