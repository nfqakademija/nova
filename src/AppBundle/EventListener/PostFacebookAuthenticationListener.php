<?php

namespace AppBundle\EventListener;

use AppBundle\Event\FacebookLoginEvent;
use Misteio\CloudinaryBundle\Wrapper\CloudinaryWrapper;

class PostFacebookAuthenticationListener
{
    /**
     * @var CloudinaryWrapper
     */
    private $cloudinary;

    /**
     * PostFacebookAuthenticationListener constructor.
     * @param CloudinaryWrapper $cloudinary
     */
    public function __construct(CloudinaryWrapper $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * @param FacebookLoginEvent $event
     * @return CloudinaryWrapper|null
     */
    public function savePicture(FacebookLoginEvent $event) {
        if ($event->isDefaultPicture()) {
            return null;
        }

        return $this->cloudinary->upload($event->getPictureUrl(), $event->getFacebookUserId());
    }
}