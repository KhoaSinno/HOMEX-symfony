<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/patient')]
class AdminPatientController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserRepository $userRepo;
    private UserPasswordHasherInterface $passHasher;
    private ImageUploader $imageUploader;
    public function __construct(EntityManagerInterface $em, UserRepository $userRepo, UserPasswordHasherInterface $passHasher, ImageUploader $imageUploader)
    {
        $this->em = $em;
        $this->userRepo = $userRepo;
        $this->passHasher = $passHasher;
        $this->imageUploader = $imageUploader;
    }

    #[Route(name: 'app_admin_patient', methods: ['GET'])]
    public function index(): Response
    {
        $patients = array_filter($this->userRepo->findByRole('ROLE_PATIENT'), function ($patient) {
            return $patient->getDel() == false || $patient->getDel() == null;
        });
        return $this->render('admin/patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    // #[Route('/listPatient/Del', name: 'app_admin_patient_listDel', methods: ['GET'])]
    // public function listDel(): Response
    // {
    //     $patients = array_filter($this->userRepo->findByRole('ROLE_PATIENT'), function ($patient) {
    //         return $patient->getDel() == true;
    //     });
    //     return $this->render('admin/patient/index.html.twig', [
    //         'patients' => $patients,
    //     ]);
    // }

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
            $user->setRoles(['ROLE_PATIENT']);
            $temPass = "pt" . $user->getPhoneNumber() || "pt" . $user->getDateOfBirth();
            $user->setPassword($this->passHasher->hashPassword($user, $temPass));

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            $oldImage = $user->getImage();

            // Xử lý ảnh
            if ($imageFile) {
                $newImage = $this->imageUploader->uploadImage($imageFile, 'user', $oldImage);
                if ($newImage) {
                    $user->setImage($newImage);
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_patient', [], Response::HTTP_SEE_OTHER);
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
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            $oldImage = $user->getImage();

            // Xử lý ảnh
            if ($imageFile) {
                $newImage = $this->imageUploader->uploadImage($imageFile, 'user', $oldImage);
                if ($newImage) {
                    $user->setImage($newImage);
                }
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_patient', [], Response::HTTP_SEE_OTHER);
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
            $user->setDel(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_patient', [], Response::HTTP_SEE_OTHER);
    }
}
