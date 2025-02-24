<?php

namespace App\Controller\Admin;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\User; 
use Symfony\Component\Form\FormError;

class AdminAccountController extends AbstractController
{
    private $em;
    private $passHasher;
    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passHasher)
    {
        $this->em = $em;
        $this->passHasher = $passHasher;
    }

    #[Route('/admin/account/changePass', name: 'app_admin_account_changePass')]
    public function changePass(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Người dùng không hợp lệ.');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $data = $form->getData();
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if(!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $form->get('oldPassword')->addError(new FormError('Mật khẩu hiện tại không đúng.'));
                
                return $this->render('admin/account/changePass.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $newPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($newPassword);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Thay đổi mật khẩu thành công!');
            return $this->redirectToRoute('app_admin_account_changePass');
        }

        return $this->render('admin/account/changePass.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
