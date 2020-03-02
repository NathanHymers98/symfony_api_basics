<?php

namespace AppBundle\Controller;

use AppBundle\Repository\ProgrammerRepository;
use AppBundle\Repository\UserRepository;
use AppBundle\Repository\ProjectRepository;
use AppBundle\Repository\BattleRepository;
use AppBundle\Repository\ApiTokenRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

abstract class BaseController extends Controller
{
    /**
     * Is the current user logged in?
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->container->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * Logs this user into the system
     *
     * @param User $user
     */
    public function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
    }

    public function addFlash($message, $positiveNotice = true)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $noticeKey = $positiveNotice ? 'notice_happy' : 'notice_sad';

        $request->getSession()->getFlashbag()->add($noticeKey, $message);
    }

    /**
     * Used to find the fixtures user - I use it to cheat in the beginning
     *
     * @param $username
     * @return User
     */
    public function findUserByUsername($username)
    {
        return $this->getUserRepository()->findUserByUsername($username);
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:User');
    }

    /**
     * @return ProgrammerRepository
     */
    protected function getProgrammerRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Programmer');
    }

    /**
     * @return ProjectRepository
     */
    protected function getProjectRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Project');
    }

    /**
     * @return BattleRepository
     */
    protected function getBattleRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Battle');
    }

    /**
     * @return \AppBundle\Battle\BattleManager
     */
    protected function getBattleManager()
    {
        return $this->container->get('battle.battle_manager');
    }

    /**
     * @return ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:ApiToken');
    }

    protected function createApiResponse($data, $statusCode = 200) // When we call this method, it takes the passed arguments and takes care of creating the correct response
    {
        $json = $this->serialize($data); // serialise whatever $data is, this can changed depending on what is passed ot this method. Serialising is the process of turning an object into a JSON string in this case.

        return new Response($json, $statusCode, array( // Returning a new response with the JSON data, status code (which defaults to 200) and setting the Content-Type header to the correct 'application/json' since it is dealing with JSON data
            'Content-Type' => 'application/json'
        ));
    }

    protected function serialize($data, $format = 'json')
    {
        $context = new SerializationContext(); // Creating a new SerializationContext object
        $context->setSerializeNull(true); // Setting the method setSerializeNull to true. By doing this, when the serializer encounters a null property, it will actually return it and not skip it.
                                            // It skips it because when we serialize an object, it serializes all the properties of that object if they have a value, if they do not it missed it completely and that property is note returned which is why we have to do this

        return $this->container->get('jms_serializer')
            ->serialize($data, $format, $context); // Passing the context as the third argument to serializer, along with the whatever $data and $format is passed to this method
    }
}
