<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploader
{
    private array $uploadDirs;
    private $slugger;
    public function __construct(array $uploadDirs, SluggerInterface $slugger)
    {
        $this->uploadDirs = $uploadDirs;
        $this->slugger = $slugger;
    }

    public function uploadImage(UploadedFile $imageFile, string $type, ?string $oldImage = null): ?string
    {
        if (!$imageFile || !isset($this->uploadDirs[$type])) {
            return null;
        }

        $uploadDir = $this->uploadDirs[$type];

        // Đặt tên file duy nhất
        $oriFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $this->slugger->slug($oriFileName);
        $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        // Lưu ảnh vào thư mục `public/uploads/users`
        try {
            $imageFile->move(
                $uploadDir,
                $newFileName
            );
        } catch (FileException $e) {
            throw new \Exception('Có lỗi khi tải ảnh lên!');
        }

        // Xoa ảnh cũ nếu có
        if ($oldImage) {
            $oldImagePath = $uploadDir . '/' . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        return $newFileName;
    }
}
