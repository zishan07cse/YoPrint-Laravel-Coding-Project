<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Jobs\ProcessCsvUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UploadResource;


class UploadController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:51200',
        ]);

        $file = $request->file('file');
        // $fileContents = file_get_contents($file->getRealPath());
        $fileContents = mb_convert_encoding(file_get_contents($file->getRealPath()), 'UTF-8', 'UTF-8');

        $checksum = hash('sha256', $fileContents);

        // if exists and completed, return existing record (idempotent)
        $existing = Upload::where('checksum', $checksum)->first();
        if ($existing && $existing->status === 'completed') {
            return (new UploadResource($existing))->response();
        }

        // save file to storage/app/public/uploads (public disk)
        $fileName = time() . '_' . $file->getClientOriginalName();
        Storage::disk('public')->putFileAs('uploads', $file, $fileName);

        // Idempotent upsert

        $upload = Upload::updateOrCreate(
            ['checksum' => $checksum],
            [
                'file_name' => $fileName,
                'status' => 'pending',
            ]
        );
        // dispatch background job
        ProcessCsvUpload::dispatch($upload);

        Log::info('Upload record created', ['upload_id' => $upload->id]);

        return response()->json([
            'success' => true,
            'upload' => $upload
        ], 201);
    }

    public function index()
    {
        $uploads = Upload::latest()->take(50)->get();

        // Pass the uploads to the Blade view
        return view('upload', compact('uploads'));
    }

    public function uploadsJson()
    {
        $uploads = Upload::latest()->take(50)->get();
        return response()->json(['data' => $uploads]);
    }
}
