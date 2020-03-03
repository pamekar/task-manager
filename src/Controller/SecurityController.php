<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use Symfony\Component\HttpFoundation\Response;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class SecurityController extends AbstractFOSRestController
{
    private $client_manager;
    private $encoderFactory;

    public function __construct(ClientManagerInterface $client_manager, EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
        $this->client_manager = $client_manager;
    }

    /**
     * Create Client.
     * @FOSRest\Post("/auth/createClient")
     *
     * @param Request $request
     * @return Response
     */
    public function createClient(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['redirect-uri']) || empty($data['grant-type'])) {
            return $this->handleView($this->view($data));
        }
        $clientManager = $this->client_manager;
        $client = $clientManager->createClient();
        $client->setRedirectUris([$data['redirect-uri']]);
        $client->setAllowedGrantTypes([$data['grant-type']]);
        $clientManager->updateClient($client);
        $rows = [
            'client_id' => $client->getPublicId(), 'client_secret' => $client->getSecret()
        ];
        return $this->handleView($this->view($rows, Response::HTTP_CREATED));
    }

    /**
     * Create Client.
     * @FOSRest\Post("/auth/register")
     *
     * @param Request $request
     * @return Response
     */
    public function createUser(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $data = json_decode($request->getContent(), true);

        $encoder = $this->encoderFactory->getEncoder($user);

        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user->setPassword($encoder->encodePassword($data['password'], $user->getSalt()));
            $user->setEnabled(true);
            $em->persist($user);
            $em->flush();

            return $this->handleView($this->view(['status' => 'ok', 'data' => $user], Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Get logged in user
     * @FOSRest\Get("/api/user")
     *
     * @param Request $request
     * @return Response
     */
    public function getLoggedUser(Request $request)
    {
        $user = $this->getUser();
        if ($user) {
            return $this->handleView($this->view($user));
        }
        return $this->handleView($this->view(['status' => "User not found."], Response::HTTP_NOT_FOUND));
    }
}