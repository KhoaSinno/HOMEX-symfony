<?php

namespace App\Controller\Admin;

use App\Entity\Specialty;
use App\Form\SpecialtyType;
use App\Repository\SpecialtyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/specialty')]
final class SpecialtyController extends AbstractController
{
    #[Route(name: 'app_specialty_index', methods: ['GET'])]
    public function index(SpecialtyRepository $specialtyRepository, FormFactoryInterface $formFactory): Response
    {
        $specialties = $specialtyRepository->findAll();
        $forms = [];

        foreach ($specialties as $specialty) {
            $forms[$specialty->getId()] = $formFactory->create(SpecialtyType::class, $specialty, [
                'action' => $this->generateUrl('app_specialty_edit', ['id' => $specialty->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        // Form cho thêm mới chuyên khoa
        $addForm = $formFactory->create(SpecialtyType::class, null, [
            'action' => $this->generateUrl('app_specialty_new'),
            'method' => 'POST',
        ])->createView();

        return $this->render('admin/specialty/index.html.twig', [
            'specialties' => $specialties,
            'forms' => $forms,
            'addForm' => $addForm,
        ]);
    }

    #[Route('/new', name: 'app_specialty_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $specialty = new Specialty();
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Xử lý upload ảnh nếu có
            $image = $form->get('image')->getData();
            if ($image) {
                $imageName = md5(uniqid()) . '.' . $image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('uploads_directory'),
                        $imageName
                    );
                    $specialty->setImage($imageName);
                } catch (FileException $e) {
                    return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
    
            $entityManager->persist($specialty);
            $entityManager->flush();
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Chuyên khoa mới đã được thêm thành công!',
                'data' => [
                    'id' => $specialty->getId(),
                    'name' => $specialty->getName(),
                    'clinicNumber' => $specialty->getClinicNumber(),
                    'image' => $specialty->getImage(),
                ],
            ]);
        }
    
        // Nếu form không hợp lệ, trả về lỗi
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
    
        return new JsonResponse([
            'success' => false,
            'message' => 'Form không hợp lệ.',
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }
    

    // #[Route('/admin/specialty/new', name: 'app_specialty_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $specialty = new Specialty();
    //     $form = $this->createForm(SpecialtyType::class, $specialty);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {

    //         // Take image from form
    //         $image = $form->get('image')->getData();

    //         // If image is not null
    //         if ($image) {
    //             // Create a unique name for the image
    //             $imageName = md5(uniqid()) . '.' . $image->guessExtension();

    //             try {
    //                 // Move the file to the directory where images are stored
    //                 $image->move(
    //                     $this->getParameter('uploads_directory'),
    //                     $imageName
    //                 );

    //                 // Update Image's name in Entity
    //                 $specialty->setImage($imageName);
    //             } catch (FileException $e) {
    //                 return new Response($e->getMessage());
    //             }
    //         }

    //         $entityManager->persist($specialty);
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_specialty_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/specialty/new.html.twig', [
    //         'specialty' => $specialty,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/{id}/edit', name: 'app_specialty_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Specialty $specialty, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lưu tên ảnh cũ để xóa sau khi thêm ảnh mới
            $oldImage = $specialty->getImage();

            // Lấy ảnh từ form
            $image = $form->get('image')->getData();
            if ($image) {
                // Tạo tên mới cho ảnh
                $imageName = md5(uniqid()) . '.' . $image->guessExtension();

                try {
                    // Lưu ảnh vào thư mục uploads
                    $image->move(
                        $this->getParameter('uploads_directory'),
                        $imageName
                    );
                    // Gán tên ảnh mới cho thực thể
                    $specialty->setImage($imageName);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Lỗi khi tải ảnh lên.');
                }
            }

            // Lưu thông tin vào cơ sở dữ liệu
            $entityManager->flush();

            // Sau khi flush, xóa ảnh cũ nếu tồn tại và thêm ảnh mới thành công
            if ($image && $oldImage) {
                $imagePath = $this->getParameter('uploads_directory') . '/' . $oldImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            return $this->redirectToRoute('app_specialty_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/specialty/edit.html.twig', [
            'specialty' => $specialty,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_specialty_delete', methods: ['POST'])]
    public function delete(Request $request, Specialty $specialty, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $specialty->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($specialty);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_specialty_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete-image', name: 'specialty_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, Specialty $specialty, EntityManagerInterface $entityManager): JsonResponse
    {
        // In token nhận được từ request
        $token = $request->get('_token');
        // Log thông tin toàn bộ request
        error_log(print_r($request->request->all(), true));
        dump($request->get('_token'));

        if (!$this->isCsrfTokenValid('delete_image' . $specialty->getId(), $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 400);
        }

        if ($specialty->getImage()) {
            $imagePath = $this->getParameter('uploads_directory') . '/' . $specialty->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath); // Xóa file từ thư mục
            }
            $specialty->setImage(null); // Xóa thông tin ảnh trong database
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Image not found.'], 404);
    }
}
