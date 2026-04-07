<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BulkUploadController extends Controller
{
    public function index()
    {
        return view('admin.bulk_upload.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'exfile' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $file = $request->file('exfile');
        $path = $file->store('bulk_uploads');

        $rowCount = null;
        if ($file->getClientOriginalExtension() === 'csv') {
            $rowCount = 0;
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                while (($line = fgetcsv($handle)) !== false) {
                    if (!empty(array_filter($line, fn($cell) => $cell !== null && $cell !== ''))) {
                        $rowCount++;
                    }
                }
                fclose($handle);
                if ($rowCount > 0) {
                    $rowCount--; // header row
                }
            }
        }

        $message = 'File uploaded successfully: ' . $file->getClientOriginalName();
        if (!is_null($rowCount)) {
            $message .= ' (' . max($rowCount, 0) . ' data rows detected)';
        }

        return redirect()->route('admin.bulk-upload.index')->with('success', $message)->with('bulk_upload_path', $path);
    }
}
