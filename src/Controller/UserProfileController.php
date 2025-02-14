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
use App\Service\ImageUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserProfileController extends AbstractController
{
    private $imageUploader;
    public function __construct(ImageUploader $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }
    #[Route('/profile', name: 'user_profile_settings')]
    public function profile(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser(); // Lấy thông tin user hiện tại

        if (!$user instanceof User) {
            throw new \LogicException('Người dùng không hợp lệ.');
        }

        $isDoctor = in_array('ROLE_DOCTOR', $user->getRoles());

        $form = $this->createForm(ProfileType::class, $user, [
            'is_doctor' => $isDoctor,
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
            // if ($imageFile) {
            //     // Đặt tên file duy nhất
            //     $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            //     $safeFilename = $slugger->slug($originalFilename);
            //     $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            //     // Lưu ảnh vào thư mục `public/uploads/users`
            //     try {
            //         $imageFile->move(
            //             $this->getParameter('uploads_user'),
            //             $newFilename
            //         );
            //     } catch (FileException $e) {
            //         $this->addFlash('error', 'Có lỗi khi tải ảnh lên!');
            //     }

            //     // Xóa ảnh cũ nếu có
            //     if ($user->getImage()) {
            //         $oldImagePath = $this->getParameter('uploads_user') . '/' . $user->getImage();
            //         if (file_exists($oldImagePath)) {
            //             unlink($oldImagePath);
            //         }
            //     }

            //     // Lưu tên file mới vào database
            //     $user->setImage($newFilename);
            // }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Cập nhật thông tin người dùng thành công!');
            return $this->redirectToRoute('user_profile_settings');
        }

        return $this->render('user_profile/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user, // Truyền user vào template
        ]);
    }

    #[Route('/profile/change-password', name: 'user_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Người dùng không hợp lệ.');
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
