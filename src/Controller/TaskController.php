<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Task;
use App\Form\TaskType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function indexTasksAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Task::class);
        $tasks = $repository->findall();
        return $this->handleView($this->view($tasks));
    }

    /**
     * Show a Task.
     * @Rest\Get("/tasks/{id}")
     *
     * @param $id
     * @return Response
     */
    public function showTasksAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Task::class);
        $task = $repository->find($id);
        if ($task && $task->getAuthor() === $this->getUser()) {
            return $this->handleView($this->view($task));
        }

        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    /**
     * Create Task.
     * @Rest\Post("/tasks")
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function storeTasksAction(Request $request, ValidatorInterface $validator)
    {
        $task = new Task();
        $task->setAuthor($this->getUser());

        $data = json_decode($request->getContent(), true);
        $validate = $this->validate($validator, $data);
        if (!$validate['success']) {
            return $this->handleView($this->view(['status' => 'failed validation',
                'data' => $validate], Response::HTTP_CREATED));
        }

        $form = $this->createForm(TaskType::class, $task);

        $form->submit($validate['data'] ?? []);
        if ($validate['success'] && $form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok', 'data' => $task], Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Update Task.
     * @Rest\Patch("/tasks/{id}")
     *
     * @param Request $request
     * @param $id
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateTasksAction(Request $request, $id, ValidatorInterface $validator)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Task::class);
        $task = $repository->find($id);
        $data = json_decode($request->getContent(), true);
        $validate = $this->validate($validator, $data);
        if (!$validate['success']) {
            return $this->handleView($this->view(['status' => 'failed validation',
                'data' => $validate], Response::HTTP_CREATED));
        }

        $data = $validate['data'];
        if ($task && $task->getAuthor() === $this->getUser()) {
            if ($title = $data['title'] ?? null) {
                $task->setTitle($title);
            }
            if ($description = $data['description'] ?? null) {
                $task->setTitle($description);
            }
            if ($start_date = $data['start_date'] ?? null) {
                $task->setStartDdate($start_date);
            }
            if ($end_date = $data['end_date'] ?? null) {
                $task->setEndDate($end_date);
            }
            return $this->handleView($this->view(['status' => 'ok', 'data' => $task], Response::HTTP_NOT_FOUND));
        }
        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    /**
     * Delete Task.
     * @Rest\Delete("/tasks/{id}")
     *
     * @param $id
     * @return Response
     */
    public function deleteTasksAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Task::class);
        $task = $repository->find($id);
        if ($task && $task->getAuthor() === $this->getUser()) {
            $entityManager->remove($task);
            $entityManager->flush();
            return $this->handleView($this->view(['status' => 'deleted']));
        }

        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    public function validate(ValidatorInterface $validator, $data)
    {
        $constraints = new Assert\Collection([
            'title' => [new Assert\Length(['min' => 2])],
            'description' => [new Assert\Length(['min' => 2])],
            'start_at' => [new Assert\DateTime()],
            'end_at' => [new Assert\DateTime()]
        ]);

        $fields = ['title' => null, 'description' => null, 'start_at' => null, 'end_at' => null];
        $input = array_merge($fields, $data); // Add absent fields to validator

        $violations = $validator->validate($input, $constraints);

        if (count($violations) > 0) {

            $accessor = PropertyAccess::createPropertyAccessor();

            $errorMessages = [];

            foreach ($violations as $violation) {

                $accessor->setValue($errorMessages,
                    $violation->getPropertyPath(),
                    $violation->getMessage());
            }
            return ['success' => false, 'errorMessages' => $errorMessages];
        } else {
            try {
                if ($start_at = $input['start_at'] ?? null) {
                    $input['start_at'] = new \DateTime($start_at);
                }
                if ($end_at = $input['end_at'] ?? null) {
                    $input['end_at'] = new \DateTime($end_at);
                }
            } catch (\Exception $e) {
                return ['success' => false, 'errorMessages' => $e->getMessage()];
            }
            return ['success' => true, 'data' => $input];
        }
    }
}