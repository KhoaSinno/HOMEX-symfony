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
                        $this->getParameter('uploads_specialty'),
                        $imageName
                    );
                    $specialty->setImage($imageName);
                } catch (FileException $e) {
                    return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $entityManager->persist($specialty);
            $entityManager->flush();
            // $this->addFlash('success', 'Thêm mới chuyên khoa thành công!');

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

        // Nếu form không hợp lệ, trả về lỗi chi tiết
        $errors = [];
        foreach ($form->all() as $fieldName => $formField) {
            if ($formField->getErrors()->count() > 0) {
                foreach ($formField->getErrors() as $error) {
                    $errors[$fieldName][] = $error->getMessage();
                }
            }
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Form không hợp lệ.',
            'errors' => $errors, // Trả về lỗi chi tiết cho từng field
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/edit', name: 'app_specialty_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Specialty $specialty, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Kiểm tra xem có thay đổi gì trong form không
            $isFormValid = false;

            // Kiểm tra nếu form hợp lệ hoặc không có thay đổi gì
            if ($form->isValid() || $form->isSubmitted()) {
                $isFormValid = true;
            }

            // Nếu form không hợp lệ và không có thay đổi gì, trả về kết quả
            if (!$isFormValid) {
                $errors = [];
                foreach ($form->all() as $fieldName => $formField) {
                    if ($formField->getErrors()->count() > 0) {
                        foreach ($formField->getErrors() as $error) {
                            $errors[$fieldName][] = $error->getMessage();
                        }
                    }
                }

                // Trả về JSON lỗi nếu form không hợp lệ
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Form không hợp lệ.',
                    'errors' => $errors, // Trả về lỗi chi tiết cho từng field
                ], Response::HTTP_BAD_REQUEST);
            }

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
                        $this->getParameter('uploads_specialty'),
                        $imageName
                    );
                    // Gán tên ảnh mới cho thực thể
                    $specialty->setImage($imageName);
                } catch (FileException $e) {
                    // Nếu có lỗi khi tải ảnh lên, thêm thông báo lỗi vào flash
                    $this->addFlash('danger', 'Lỗi khi tải ảnh lên.');
                    // Trả về lỗi JSON nếu có lỗi tải ảnh
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Lỗi khi tải ảnh lên.'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Lưu thông tin vào cơ sở dữ liệu
            try {
                $entityManager->flush();
            } catch (\Exception $e) {
                // Nếu có lỗi khi lưu vào cơ sở dữ liệu
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Lỗi khi lưu dữ liệu vào cơ sở dữ liệu.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Sau khi flush, xóa ảnh cũ nếu tồn tại và thêm ảnh mới thành công
            if ($image && $oldImage) {
                $imagePath = $this->getParameter('uploads_specialty') . '/' . $oldImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Trả về kết quả thành công sau khi sửa thông tin
            return $this->redirectToRoute('app_specialty_index', [], Response::HTTP_SEE_OTHER);
        }

        // Nếu form chưa được submit, trả về trang chỉnh sửa với form
        return $this->render('admin/specialty/edit.html.twig', [
            'specialty' => $specialty,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}', name: 'app_specialty_delete', methods: ['POST'])]
    public function delete(Request $request, Specialty $specialty, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $specialty->getId(), $request->getPayload()->getString('_token'))) {

            if ($specialty->getUsers()->count() > 0) {
                $this->addFlash('error', 'Không thể xóa vì có bác sĩ đang sử dụng chuyên khoa này!');
                return $this->redirectToRoute('app_specialty_index');
            }

            if ($specialty->getImage()) {
                $imagePath = $this->getParameter('uploads_specialty') . '/' . $specialty->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($specialty);
            $entityManager->flush();
            $this->addFlash('success', 'Xóa chuyên khoa thành công!');
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
            $imagePath = $this->getParameter('uploads_specialty') . '/' . $specialty->getImage();
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
