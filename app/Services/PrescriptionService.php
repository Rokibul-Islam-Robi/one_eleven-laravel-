<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Prescription upload (5 MB; JPG/PNG/WEBP/PDF) on the private "local" disk. Ported from prescription_store(). */
class PrescriptionService
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    public const ALLOWED = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    /** Error code -> message used by api/checkout. */
    public const MESSAGES = [
        'no_file'       => 'No file received.',
        'upload_failed' => 'Upload failed, please try again.',
        'too_large'     => 'File must be 5 MB or smaller.',
        'bad_type'      => 'Only JPG, PNG, WEBP or PDF files are accepted.',
        'store_failed'  => 'Could not store the file, please try again.',
    ];

    /** True when the request carried a (named) rx_file upload — original: !empty($_FILES['rx_file']['name']). */
    public static function present(?UploadedFile $file): bool
    {
        return $file !== null && (string) $file->getClientOriginalName() !== '';
    }

    /** @return array record incl. 'id', or ['error' => code] */
    public static function store(
        ?UploadedFile $file,
        string $customerName,
        string $phone,
        ?int $customerId = null,
        string $notes = ''
    ): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['error' => 'no_file'];
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return ['error' => 'upload_failed'];
        }
        $size = (int) filesize($file->getPathname());
        if ($size > self::MAX_BYTES) {
            return ['error' => 'too_large'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file->getPathname());
        if (! isset(self::ALLOWED[$mime])) {
            return ['error' => 'bad_type'];
        }

        $stored = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED[$mime];
        $dir = trim((string) config('bherb.prescription_dir', 'uploads/prescriptions'), '/');
        try {
            $ok = Storage::disk('local')->putFileAs($dir, $file, $stored);
        } catch (Throwable $e) {
            $ok = false;
        }
        if (! $ok) {
            return ['error' => 'store_failed'];
        }

        $record = [
            'customer_name' => mb_substr($customerName, 0, 150),
            'phone'         => mb_substr($phone, 0, 40),
            'file_path'     => $dir . '/' . $stored,
            'original_name' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
            'mime_type'     => $mime,
            'file_size'     => $size,
            'notes'         => $notes !== '' ? $notes : null,
            'status'        => 'pending',
        ];
        if ($customerId !== null && Schema::hasColumn('prescriptions', 'store_customer_id')) {
            $record['store_customer_id'] = $customerId;
        }

        try {
            $record['id'] = (int) DB::table('prescriptions')->insertGetId($record + ['created_at' => date('Y-m-d H:i:s')]);
        } catch (Throwable $ex) {
            report($ex);
            Storage::disk('local')->delete($record['file_path']);
            return ['error' => 'store_failed'];
        }
        return $record;
    }
}
