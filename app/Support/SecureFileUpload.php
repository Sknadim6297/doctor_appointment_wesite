<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SecureFileUpload
{
    public static function assertValid(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $blocked = config('enrollment_form.blocked_extensions', []);
        $allowed = config('enrollment_form.allowed_extensions', []);

        if ($extension === '' || in_array($extension, $blocked, true)) {
            throw new \InvalidArgumentException('This file type is not allowed for security reasons.');
        }

        if (!in_array($extension, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported file type. Allowed: ' . implode(', ', $allowed));
        }

        $maxKb = (int) config('enrollment_form.max_file_kb', 10240);
        if ($file->getSize() > $maxKb * 1024) {
            throw new \InvalidArgumentException("File exceeds maximum size of {$maxKb} KB.");
        }

        $mime = $file->getMimeType();
        $allowedMimes = config('enrollment_form.allowed_mimes', []);
        if ($mime && $allowedMimes !== [] && !in_array($mime, $allowedMimes, true)) {
            throw new \InvalidArgumentException('File content type is not permitted.');
        }

        $finfoMime = self::detectMime($file);
        if ($finfoMime && $allowedMimes !== [] && !in_array($finfoMime, $allowedMimes, true)) {
            throw new \InvalidArgumentException('File content does not match an allowed document type.');
        }
    }

    public static function assertRequestWithinTotalSize(Request $request): void
    {
        $maxKb = (int) config('enrollment_form.max_request_upload_kb', 51200);
        $total = 0;

        foreach ($request->allFiles() as $file) {
            $total += self::fileSizeRecursive($file);
        }

        if ($total > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'uploads' => ["Total upload size exceeds the limit of {$maxKb} KB."],
            ]);
        }
    }

    /**
     * @param  UploadedFile|array<int, UploadedFile>|mixed  $file
     */
    private static function fileSizeRecursive(mixed $file): int
    {
        if ($file instanceof UploadedFile) {
            return (int) $file->getSize();
        }

        if (!is_array($file)) {
            return 0;
        }

        return array_sum(array_map(self::fileSizeRecursive(...), $file));
    }

    private static function detectMime(UploadedFile $file): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        return is_string($mime) ? $mime : null;
    }
}
