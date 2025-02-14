<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/patient')]
class AdminPatientController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserRepository $userRepo;
    public function __construct(EntityManagerInterface $em, UserRepository $userRepo)
    {
        $this->em = $em;
        $this->userRepo = $userRepo;
    }

    #[Route(name: 'app_admin_patient', methods: ['GET'])]
    public function index(): Response
    {
        $patients = $this->userRepo->findByRole('ROLE_PATIENT');
        return $this->render('admin/patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/new', name: 'app_admin_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        // $form = $this->createForm(UserType::class, $user,[
        //     'is_patient' => true,
        // ]);

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/patient/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_patient_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/patient/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // $form = $this->createForm(UserType::class, $user,[
        //     'is_patient' => true,
        // ]);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/patient/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_patient_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
    }
}
