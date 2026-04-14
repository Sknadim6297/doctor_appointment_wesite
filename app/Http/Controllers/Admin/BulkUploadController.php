<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\BulkDoctorUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BulkUploadController extends Controller
{
    public function __construct(
        private readonly BulkDoctorUploadService $bulkDoctorUploadService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index()
    {
        return view('admin.bulk_upload.index', [
            'templateHeaders' => BulkDoctorUploadService::templateHeaders(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exfile' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $file = $request->file('exfile');
        try {
            $result = $this->bulkDoctorUploadService->import($file, Auth::id());
        } catch (Throwable $throwable) {
            return back()
                ->withInput()
                ->with('error', 'Unable to process the upload file: ' . $throwable->getMessage());
        }

        $path = $file->store('bulk_uploads');

        $this->activityLogService->log(
            $request,
            'doctors',
            'edit',
            description: 'Imported doctor records using bulk upload.',
            metadata: [
                'file_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'processed_rows' => $result['processed_rows'],
                'created_rows' => $result['created_rows'],
                'updated_rows' => $result['updated_rows'],
                'skipped_rows' => $result['skipped_rows'],
            ]
        );

        $message = sprintf(
            'Bulk upload complete. %d created, %d updated, %d skipped.',
            $result['created_rows'],
            $result['updated_rows'],
            $result['skipped_rows']
        );

        if (!empty($result['errors'])) {
            $message .= ' Review the row errors shown below.';
        }

        return redirect()
            ->route('admin.bulk-upload.index')
            ->with('success', $message)
            ->with('bulk_upload_path', $path)
            ->with('bulk_upload_result', $result);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, BulkDoctorUploadService::templateHeaders());
            fputcsv($handle, BulkDoctorUploadService::templateExampleRow());

            fclose($handle);
        }, 'doctor-bulk-upload-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
