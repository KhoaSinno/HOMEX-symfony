<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProfileController extends AbstractController
{
    #[Route('/profile', name: 'user_profile_settings')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser(); // Lấy thông tin user hiện tại

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Cập nhật thông tin người dùng thành công!');
            return $this->redirectToRoute('user_profile_settings');
        }

        return $this->render('user_profile/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/change-password', name: 'user_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User is not authenticated or is not an instance of User.');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($newPassword);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Thay đổi mật khẩu thành công!');
            return $this->redirectToRoute('user_profile_settings');
        }

        return $this->render('user_profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
