<?php

namespace App\Events;

use App\Models\Upload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UploadStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public Upload $upload;

    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    public function broadcastOn()
    {
        return new Channel('uploads');
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->upload->id,
            'status' => $this->upload->status,
            'file_name' => $this->upload->file_name,
            'updated_at' => $this->upload->updated_at->toDateTimeString(),
        ];
    }
}
