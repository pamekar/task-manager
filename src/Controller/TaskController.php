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
 * @Route("/api/tasks", name="api_")
 */
class TaskController extends AbstractFOSRestController
{
    /**
     * Lists all Tasks.
     * @Rest\Get("/")
     *
     * @return Response
     */
    public function indexTasksAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Task::class);
        $tasks = $repository->findBy(['author' => $this->getUser()]);
        return $this->handleView($this->view($tasks));
    }

    /**
     * Show a Task.
     * @Rest\Get("/{id}")
     *
     * @param Task $task
     * @return Response
     */
    public function showTasksAction(Task $task)
    {
        if ($task->getAuthor() === $this->getUser()) {
            return $this->handleView($this->view($task));
        }

        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    /**
     * Show a Task.
     * @Rest\Get("/show/day/{timestamp}")
     *
     * @param null $timestamp
     * @return Response
     */
    public function showDailyTasksAction($timestamp = null)
    {
        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findCurrentTasks($this->getUser(), $timestamp);
        return $this->handleView($this->view($tasks));
    }

    /**
     * Create Task.
     * @Rest\Post("/")
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
        $validate = $this->validate($validator, $data ?? []);
        if (!$validate['success']) {
            return $this->handleView($this->view(['status' => 'failed validation',
                'data' => $validate], 422));
        }

        $form = $this->createForm(TaskType::class, $task);

        $form->submit($validate['data'] ?? []);
        if ($validate['success'] && $form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok', 'data' => $task], 201));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Update Task.
     * @Rest\Put("/{id}")
     *
     * @param Request $request
     * @param Task $task
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateTasksAction(Request $request, Task $task, ValidatorInterface $validator)
    {
        $data = json_decode($request->getContent(), true);
        $validate = $this->validate($validator, $data);
        if (!$validate['success']) {
            return $this->handleView($this->view(['status' => 'failed validation',
                'data' => $validate], 422));
        }

        $data = $validate['data'];
        if ($task->getAuthor() === $this->getUser()) {
            if ($title = $data['title'] ?? null) {
                $task->setTitle($title);
            }
            if ($description = $data['description'] ?? null) {
                $task->setDescription($description);
            }
            if ($start_at = $data['start_at'] ?? null) {
                $task->setStartAt($start_at);
            }
            if ($end_at = $data['end_at'] ?? null) {
                $task->setEndAt($end_at);
            }
            if ($status = $data['status'] ?? null) {
                $task->setStatus($status);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok', 'data' => $task], 200));
        }
        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    /**
     * Delete Task.
     * @Rest\Delete("/{id}")
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
            return $this->handleView($this->view(['status' => 'deleted'], 204));
        }

        return $this->handleView($this->view(['status' => "Task not found."], Response::HTTP_NOT_FOUND));
    }

    public function validate(ValidatorInterface $validator, $data)
    {
        $constraints = new Assert\Collection([
            'title' => [new Assert\Length(['min' => 2])],
            'description' => [new Assert\Length(['min' => 2])],
            'start_at' => [],
            'end_at' => [],
            'status' => [new Assert\Choice(['pending', 'active', 'completed'])]
        ]);

        $fields = ['title' => null, 'description' => null, 'start_at' => null, 'end_at' => null, 'status' => null];
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