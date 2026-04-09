<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService {
    public function storeStudentPhoto(UploadedFile $file): array {
        $baseName  = uniqid('student_', true);
        $origPath  = "students/{$baseName}.webp";
        $thumbPath = "students/thumbs/{$baseName}_thumb.webp";

        $image = Image::read($file->getRealPath())->scaleDown(width:800,height:800)->toWebp(quality:85);
        Storage::disk('public')->put($origPath, $image);

        $thumb = Image::read($file->getRealPath())->cover(width:200,height:200)->toWebp(quality:80);
        Storage::disk('public')->put($thumbPath, $thumb);

        return [$origPath, $thumbPath];
    }

    public function deleteStudentPhoto(string $origPath, ?string $thumbPath): void {
        Storage::disk('public')->delete(array_filter([$origPath, $thumbPath]));
    }
}
