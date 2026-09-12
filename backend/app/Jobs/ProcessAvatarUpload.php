<?php

namespace App\Jobs;

use App\Models\Organizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessAvatarUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];

    public function __construct(
        public string $organizerId,
        public string $path,
        public string $content,
        public string $mime,
        public ?string $oldAvatarUrl,
        public int $expectedVersion
    ) {}

    public function handle(): void
    {
        $organizer = Organizer::find($this->organizerId);
        if (!$organizer) return;

        // Optimistic lock: only update if version matches expected (no concurrent win)
        if ((int) $organizer->avatarVersion !== $this->expectedVersion) {
            // Another upload won — delete this orphan file
            try {
                $disk = $this->disk();
                $disk->delete($this->path);
            } catch (\Throwable $e) {}
            return;
        }

        // Resize (same GD logic as controller fallback)
        $resized = $this->resizeImageTo400($this->content, $this->mime);

        $disk = $this->disk();
        $disk->put($this->path, $resized, 'public');
        $url = $disk->url($this->path);

        // Atomic update with version increment
        $updated = Organizer::where('id', $organizer->id)
            ->where('avatarVersion', $this->expectedVersion)
            ->update(['avatarUrl' => $url, 'avatarVersion' => $this->expectedVersion + 1]);

        if ($updated) {
            // Delete old
            if ($this->oldAvatarUrl) {
                $oldPath = $this->extractStoragePath($this->oldAvatarUrl);
                if ($oldPath && $oldPath !== $this->path) {
                    try { $disk->delete($oldPath); } catch (\Throwable $e) {}
                    try { Storage::disk('s3')->delete($oldPath); } catch (\Throwable $e) {}
                }
            }
            try { \Illuminate\Support\Facades\Cache::forget("organizer:public:{$organizer->id}:v*"); } catch (\Throwable $e) {}
        } else {
            // Lost race — delete orphan
            try { $disk->delete($this->path); } catch (\Throwable $e) {}
        }
    }

    private function disk()
    {
        try {
            $s3Configured = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'));
            return $s3Configured ? Storage::disk('s3') : Storage::disk('public');
        } catch (\Throwable $e) {
            return Storage::disk('public');
        }
    }

    private function resizeImageTo400(string $content, string $mime): string
    {
        try {
            if (!extension_loaded('gd')) return $content;
            $src = @imagecreatefromstring($content);
            if (!$src) return $content;
            $w = imagesx($src); $h = imagesy($src);
            $dst = imagecreatetruecolor(400, 400);
            if (in_array($mime, ['image/png','image/webp'], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $t = imagecolorallocatealpha($dst, 0,0,0,127);
                imagefill($dst,0,0,$t);
            }
            $size = min($w,$h);
            $sx = (int)(($w - $size)/2); $sy = (int)(($h - $size)/2);
            imagecopyresampled($dst,$src,0,0,$sx,$sy,400,400,$size,$size);
            ob_start();
            if ($mime==='image/png') imagepng($dst);
            elseif ($mime==='image/webp' && function_exists('imagewebp')) imagewebp($dst);
            else imagejpeg($dst,null,90);
            $out = ob_get_clean();
            imagedestroy($src); imagedestroy($dst);
            return $out ?: $content;
        } catch (\Throwable $e) { return $content; }
    }

    private function extractStoragePath(?string $url): ?string
    {
        if (!$url) return null;
        if (str_starts_with($url, 'avatars/')) return $url;
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) return null;
        $path = ltrim($path,'/');
        if (str_starts_with($path,'storage/')) $path = substr($path,8);
        $pos = strpos($path,'avatars/');
        return $pos!==false ? substr($path,$pos) : null;
    }
}
