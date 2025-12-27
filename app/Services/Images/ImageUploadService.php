<?php

namespace App\Services\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Upload a single image.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @return string The stored file path
     */
    public function upload(UploadedFile $file, string $directory = 'images', string $disk = 'public'): string
    {
        return $file->store($directory, $disk);
    }

    /**
     * Upload multiple images.
     *
     * @param array<int, UploadedFile> $files
     * @param string $directory
     * @param string $disk
     * @return array<int, string> Array of stored file paths
     */
    public function uploadMultiple(array $files, string $directory = 'images', string $disk = 'public'): array
    {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = $this->upload($file, $directory, $disk);
        }

        return $paths;
    }

    /**
     * Delete an image.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete multiple images.
     *
     * @param array<int, string> $paths
     * @param string $disk
     * @return void
     */
    public function deleteMultiple(array $paths, string $disk = 'public'): void
    {
        foreach ($paths as $path) {
            $this->delete($path, $disk);
        }
    }

    /**
     * Check if an image exists.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get image URL.
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    public function url(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }
}
