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

#[Route('admin/doctor')]
final class AdminDoctorController extends AbstractController
{
    private UserPasswordHasherInterface $passHasher;
    private ImageUploader $imageUploader;
    public function __construct(UserPasswordHasherInterface $passHasher, ImageUploader $imageUploader)
    {
        $this->passHasher = $passHasher;
        $this->imageUploader = $imageUploader;
    }
    
    #[Route(name: 'app_doctor_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $doctors = array_filter($userRepository->findByRole('ROLE_DOCTOR'), function($doctor) {
            return $doctor->getDel() == false || $doctor->getDel() == null;
        });

        return $this->render('admin/doctor/index.html.twig', [
            'doctors' => $doctors,
        ]);
    }
    #[Route('/listDoctor/Del',name: 'app_doctor_listDel', methods: ['GET'])]
    public function listDel(UserRepository $userRepository): Response
    {
        $doctors = array_filter($userRepository->findByRole('ROLE_DOCTOR'), function($doctor) {
            return $doctor->getDel() == true;
        });
        
        return $this->render('admin/doctor/listDel.html.twig', [
            'doctors' => $doctors,
        ]);
    }


    #[Route('/new', name: 'app_doctor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user, [
            'is_doctor' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // dump($form->getErrors(true));

            $user->setRoles(['ROLE_DOCTOR']);
            $user->setDel(false);
            $temPass = "dr" . $user->getPhoneNumber();
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

            return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/doctor/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/doctor/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_doctor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_doctor' => true,
        ]);
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

            return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/doctor/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_doctor_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $user->setDel(true);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Đã cho bác sĩ nghĩ việc!');
        } else {
            $this->addFlash('danger', 'Lỗi CSRF, vui lòng thử lại!');
        }

        return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);

        // if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
        //     $user->setDel(true);
        //     // $entityManager->remove($user);
        //     $entityManager->flush();
        // }

        // return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
    }
}
