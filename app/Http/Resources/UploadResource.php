<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'url' => $this->file_name ? asset('storage/uploads/' . $this->file_name) : null,
            'status' => $this->status,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
