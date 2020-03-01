<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Task;
use App\Form\TaskType;

/**
 * Task controller.
 * @Route("/api", name="api_")
 */
class TaskController extends AbstractFOSRestController
{
    /**
     * Lists all Tasks.
     * @Rest\Get("/tasks")
     *
     * @return Response
     */
    public function getTasksAction()
    {
        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findall();
        return $this->handleView($this->view($tasks));
    }

    /**
     * Create Task.
     * @Rest\Post("/tasks")
     *
     * @param Request $request
     * @return Response
     */
    public function postTasksAction(Request $request)
    {
        $task = new Task();
        $task->setAuthor($this->getUser());
        $form = $this->createForm(TaskType::class, $task);

        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }
}