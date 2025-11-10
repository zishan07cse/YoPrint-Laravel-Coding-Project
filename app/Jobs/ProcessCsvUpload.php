<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\UploadStatusUpdated;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Upload $upload;

    /**
     * Create a new job instance.
     */
    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->upload->update(['status' => 'processing']);
        broadcast(new UploadStatusUpdated($this->upload));

        $path = storage_path('app/public/uploads/' . $this->upload->file_name);

        if (!file_exists($path)) {
            $this->upload->update(['status' => 'failed']);
            broadcast(new UploadStatusUpdated($this->upload));
            return;
        }

        try {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \Exception('Cannot open file.');
            }

            // Read header
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new \Exception('Empty or invalid CSV file.');
            }

            // Normalize header (remove BOM, trim)
            $header = array_map(function ($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                return trim($h);
            }, $header);

            // --- REQUIRED HEADER VALIDATION ---
            $required = ['UNIQUE_KEY', 'PRODUCT_TITLE', 'PRODUCT_DESCRIPTION', 'STYLE#', 'SANMAR_MAINFRAME_COLOR', 'SIZE', 'COLOR_NAME', 'PIECE_PRICE'];
            $missing = array_diff($required, $header);
            if (!empty($missing)) {
                throw new \Exception('Missing required columns: ' . implode(', ', $missing));
            }

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    // Skip rows with mismatched columns
                    continue;
                }

                $data = array_combine($header, $row);

                // Clean each value: UTF-8, remove control chars
                $data = array_map(function ($value) {
                    if (is_null($value)) return null;
                    $v = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    $v = preg_replace('/[^\P{C}\n\r\t]+/u', '', $v); // remove non-printable
                    return trim($v);
                }, $data);

                $rows[] = [
                    'unique_key' => $data['UNIQUE_KEY'] ?? null,
                    'product_title' => $data['PRODUCT_TITLE'] ?? null,
                    'product_description' => $data['PRODUCT_DESCRIPTION'] ?? null,
                    'style' => $data['STYLE#'] ?? ($data['STYLE'] ?? null),
                    'sanmar_mainframe_color' => $data['SANMAR_MAINFRAME_COLOR'] ?? null,
                    'size' => $data['SIZE'] ?? null,
                    'color_name' => $data['COLOR_NAME'] ?? null,
                    'piece_price' => isset($data['PIECE_PRICE']) ? preg_replace('/[^\d.\-]/', '', $data['PIECE_PRICE']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            fclose($handle);

            // Upsert in chunks
            $chunks = array_chunk($rows, 500);
            foreach ($chunks as $chunk) {
                DB::transaction(function () use ($chunk) {
                    Product::upsert(
                        $chunk,
                        ['unique_key'], // unique column
                        ['product_title', 'product_description', 'style', 'sanmar_mainframe_color', 'size', 'color_name', 'piece_price', 'updated_at']
                    );
                });
            }

            $this->upload->update(['status' => 'completed']);
            broadcast(new UploadStatusUpdated($this->upload));
        } catch (\Exception $e) {
            Log::error('Upload processing failed: ' . $e->getMessage(), ['upload_id' => $this->upload->id]);
            $this->upload->update(['status' => 'failed']);
            broadcast(new UploadStatusUpdated($this->upload));
        }
    }
}
