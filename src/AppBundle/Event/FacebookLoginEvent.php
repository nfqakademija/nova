<?php
namespace AppBundle\Event;

use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\EventDispatcher\Event;

class FacebookLoginEvent extends Event
{
    /**
     * @var FacebookUser
     */
    private $facebookUser;

    /**
     * FacebookLoginEvent constructor.
     * @param FacebookUser $facebookUser
     */
    public function __construct(FacebookUser $facebookUser)
    {
        $this->facebookUser = $facebookUser;
    }


    /**
     * Returning true if person using Facebook's silhouette picture.
     *
     * @return bool
     */
    public function isDefaultPicture() {
        return $this->facebookUser->isDefaultPicture();
    }

    /**
     * Returning person's picture URL or null if using Facebook's silhouette.
     *
     * @return null|string
     */
    public function getPictureUrl() {
        return $this->facebookUser->getPictureUrl();
    }

    /**
     * Returning Facebook user's id.
     *
     * @return null|string
     */
    public function getFacebookUserId() {
        return $this->facebookUser->getId();
    }
}