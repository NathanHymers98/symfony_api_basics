<?php

namespace AppBundle\Controller\Api;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\Programmer;
use AppBundle\Form\ProgrammerType;
use AppBundle\Form\UpdateProgrammerType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProgrammerController extends BaseController
{
    /**
     * @Route("/api/programmers")
     * @Method("POST")
     */
    public function newAction(Request $request) // The endpoint for creating a programmer
    {
        $programmer = new Programmer();
        $form = $this->createForm(new ProgrammerType(), $programmer); // Using the form to create a new Programmer object using the API by passing it the programmer form object
        $this->processForm($request, $form);

        $programmer->setUser($this->findUserByUsername('weaverryan'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        $response = $this->createApiResponse($programmer, 201); // Calling the createApiResponse method to create the response with the passed $programmer object as the first argument and manually setting the status code to 201 since the default is 200 in the method
        $programmerUrl = $this->generateUrl( // generating a URL with the name of the GET a single programmer URL
            'api_programmers_show',
            ['nickname' => $programmer->getNickname()] // and passing it an array with the nickname key set to the nickname of this new programmer
        );
        $response->headers->set('Location', $programmerUrl);

        return $response;
    }

    /**
     * @Route("/api/programmers/{nickname}", name="api_programmers_show")
     * @Method("GET")
     */
    public function showAction($nickname) // The endpoint for getting a single programmer. Passing it the $nickname property because that is what this method uses to find a single user.
    {
        $programmer = $this->getDoctrine() // Using this method to allow us to use repository classes that allow us to query the database
            ->getRepository('AppBundle:Programmer') // Getting the programmer repository
            ->findOneByNickname($nickname); // Using the query method inside the repository that finds one user via their nickname and passing the $nickname property there

        if (!$programmer) { // if the programmer is not found
            throw $this->createNotFoundException(sprintf( // Create a not found exception with the following sprintf message
                'No programmer found with nickname "%s"',
                $nickname
            ));
        }

        $response = $this->createApiResponse($programmer, 200);

        return $response;
    }

    /**
     * @Route("/api/programmers")
     * @Method("GET")
     */
    public function listAction() // The endpoint for getting a collection of programmers
    {
        $programmers = $this->getDoctrine() // querying the database to get all the programmers in the database
            ->getRepository('AppBundle:Programmer')
            ->findAll();

        $response = $this->createApiResponse(['programmers' => $programmers], 200);

        return $response;
    }

    // This function has two HTTP methods because both PUT and PATCH requests should be sent to this endpoint
    /**
     * @Route("/api/programmers/{nickname}")
     * @Method({"PUT", "PATCH"})
     */
    public function updateAction($nickname, Request $request)
    {
        $programmer = $this->getDoctrine() // querying for the programmer via the nickname that was passed to this method
            ->getRepository('AppBundle:Programmer')
            ->findOneByNickname($nickname);

        if (!$programmer) { // If the programmer is not found in the database, display the message below.
            throw $this->createNotFoundException(sprintf( // This sprintf() message takes the $nickname object that was passed to this method and puts it into the message string where the %s is to make the error message more helpful
                'No programmer found with nickname "%s"',
                $nickname
            ));
        }

        $form = $this->createForm(new UpdateProgrammerType(), $programmer); // Using UpdateProgrammerType, which is the exact same form as ProgrammerType, but with a different default value for the field option is_edit
        $this->processForm($request, $form); // Calling the processForm method and passing it the $request and $form objects in order to make the form submit action

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        $response = $this->createApiResponse($programmer, 200);

        return $response;
    }

    /**
     * @Route("/api/programmers/{nickname}")
     * @Method("DELETE")
     */
    public function deleteAction($nickname)
    {
        $programmer = $this->getDoctrine() // querying for a programmer resource via the nickname that was passed to this method
            ->getRepository('AppBundle:Programmer')
            ->findOneByNickname($nickname);

        if ($programmer) { // If the programmer was found, delete it.
            // debated point: should we 404 on an unknown nickname?
            // or should we just return a nice 204 in all cases?
            // we're doing the latter
            $em = $this->getDoctrine()->getManager(); // Getting the entity manager and using the remove() method to delete the passed programmer resource
            $em->remove($programmer);
            $em->flush();
        }

        return new Response(null, 204); // if the programmer wasn't found, then return a 204 instead of a 404 because the thing we wanted to delete doesn't exist anyway which is what the end result of this would have been of the programmer did exist
    }

    private function processForm(Request $request, FormInterface $form)
    {
        $data = json_decode($request->getContent(), true); // reads and decodes the request body

        $clearMissing = $request->getMethod() != 'PATCH'; // Clear all the missing fields, unless the method is PATCH
        $form->submit($data, $clearMissing);
    }
}
