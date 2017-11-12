<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Facebook\Facebook;
use Misteio\CloudinaryBundle\Wrapper\CloudinaryWrapper;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class PostFacebookAuthenticationListener
{
    /**
     * @var CloudinaryWrapper
     */
    private $cloudinary;

    /**
     * @var string
     */
    private $facebook_app_id;

    /**
     * @var string
     */
    private $facebook_app_secret;

    /**
     * @var string
     */
    private $facebook_app_version;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * PostFacebookAuthenticationListener constructor.
     * @param string $facebook_app_id
     * @param string $facebook_app_secret
     * @param string $facebook_app_version
     * @param CloudinaryWrapper $cloudinary
     */
    public function __construct($facebook_app_id, $facebook_app_secret, $facebook_app_version, CloudinaryWrapper $cloudinary, EntityManager $em)
    {
        $this->facebook_app_id = $facebook_app_id;
        $this->facebook_app_secret = $facebook_app_secret;
        $this->facebook_app_version = $facebook_app_version;
        $this->cloudinary = $cloudinary;
        $this->em = $em;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function savePicture(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        // Facebook user's id and token are necessary
        if (!$user->getFacebookId() || !$user->getToken()) {
            return;
        }

        // Facebook request instance
        $facebook = new Facebook([
            'app_id'                => $this->facebook_app_id,
            'app_secret'            => $this->facebook_app_secret,
            'default_graph_version' => $this->facebook_app_version,
        ]);

        try {
            $response = $facebook->get(
                '/' . $user->getFacebookId() .'/picture?redirect=0&type=normal',
                $user->getToken()
            );
        } catch (\Exception $e) {
            return;
        }

        $profile_picture = $response->getDecodedBody()['data'];

        // If user is using Facebook's silhouette
        if ($profile_picture['is_silhouette']) {
            // ... we will set Facebook picture's status to false
            $user->setHasFacebookPicture(false);
            $this->em->flush();
            // ... and delete image from cloudinary
            $this->cloudinary->destroy($user->getFacebookId());

            return;
        }

        $this->cloudinary->upload($profile_picture['url'], $user->getFacebookId());
        $user->setHasFacebookPicture(true);
        $this->em->flush();
    }
}