<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MediaController extends Controller
{
    /**
     * Display media library page
     */
    public function index(Request $request)
    {
        $query = Media::query()->latest();

        // Filter by folder
        if ($request->filled('folder') && $request->folder !== '/') {
            $query->where('folder', $request->folder);
        }

        // Filter by type
        if ($request->filled('type')) {
            match ($request->type) {
                'image' => $query->where('mime_type', 'like', 'image/%'),
                'video' => $query->where('mime_type', 'like', 'video/%'),
                'document' => $query->where(function ($q) {
                    $q->where('mime_type', 'not like', 'image/%')
                      ->where('mime_type', 'not like', 'video/%');
                }),
                default => null,
            };
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $media = $query->paginate(40)->withQueryString();
        $folders = Media::folders();

        return Inertia::render('Admin/Media/Index', [
            'media' => $media,
            'folders' => $folders,
            'filters' => $request->only(['folder', 'type', 'search']),
        ]);
    }

    /**
     * Upload files. Wrapped in a top-level try/catch so a server-side error
     * (storage permission, GD missing, ImageMagick fail, DB column mismatch...)
     * never escapes as a generic HTML 500 — admin always sees a readable
     * Vietnamese message via Inertia errors / JSON.
     */
    public function upload(Request $request)
    {
        try {
            return $this->doUpload($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let Laravel render the standard 422 with field errors.
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $msg = 'Upload that bai: ' . $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $msg, 'errors' => ['files' => [$msg]]], 422);
            }
            return back()->withErrors(['files' => $msg])->withInput();
        }
    }

    private function doUpload(Request $request)
    {
        // If PHP/Nginx truncated the upload (file > upload_max_filesize / post_max_size /
        // client_max_body_size), $request->file('files') is empty even though the form
        // had files attached. Surface a readable error instead of crashing later.
        if (empty($request->file('files'))) {
            $msg = 'Khong nhan duoc file. Co the do file qua lon so voi gioi han server (php.ini upload_max_filesize / post_max_size hoac Nginx client_max_body_size). Tang cac gioi han nay len 20M roi thu lai.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $msg, 'errors' => ['files' => [$msg]]], 422);
            }
            return back()->withErrors(['files' => $msg]);
        }

        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,pdf,doc,docx,xls,xlsx,zip',
            'folder' => 'nullable|string|max:255',
        ]);

        // Make sure the storage directories exist and are writable. Without this
        // a fresh deploy where `php artisan storage:link` ran but `media/` was
        // never created produces a confusing "file_put_contents failed" trace.
        try {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('media');
        } catch (\Throwable $e) {
            report($e);
        }

        $folder = $request->input('folder', '/');
        $uploaded = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            try {
                $name = pathinfo($originalName, PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $mime = $file->getMimeType();
                $size = $file->getSize();

                // Convert to WebP only if GD has imagewebp() AND source is a
                // common raster image. Skips silently when GD lacks WebP.
                $shouldConvertToWebP = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])
                    && str_starts_with($mime, 'image/')
                    && function_exists('imagewebp');

                $webpPath = null;
                if ($shouldConvertToWebP) {
                    $webpPath = $this->convertToWebP($file);
                    if ($webpPath) {
                        $file = new \Illuminate\Http\File($webpPath);
                        $extension = 'webp';
                        $mime = 'image/webp';
                        $size = filesize($webpPath);
                    }
                }

                // Generate unique filename
                $fileName = Str::slug($name) . '-' . Str::random(6) . '.' . $extension;
                $storagePath = ltrim($folder, '/');
                $storagePath = $storagePath === '' ? 'media' : 'media/' . $storagePath;

                // Store file
                $path = $file->storeAs($storagePath, $fileName, 'public');
                if (!$path) {
                    throw new \RuntimeException('Khong luu duoc file vao storage (kiem tra quyen ghi storage/app/public).');
                }

                // Get image dimensions
                $width = null;
                $height = null;
                if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
                    try {
                        $dimensions = @getimagesize($file->getRealPath());
                        if ($dimensions) {
                            $width = $dimensions[0];
                            $height = $dimensions[1];
                        }
                    } catch (\Throwable $e) {
                        // skip — dimension is best-effort
                    }

                    // Generate thumbnail (300px wide). Thumbnail failure must
                    // not block the upload — wrapped inside createThumbnail.
                    try {
                        $this->createThumbnail($file, $storagePath, $fileName);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $media = Media::create([
                    'name' => $name,
                    'file_name' => $originalName,
                    'path' => $path,
                    'disk' => 'public',
                    'mime_type' => $mime,
                    'size' => $size,
                    'width' => $width,
                    'height' => $height,
                    'alt' => $name,
                    'folder' => $folder,
                    'uploaded_by' => $request->user()?->id,
                ]);

                $uploaded[] = $media;

                // Clean up temporary WebP file
                if ($webpPath && file_exists($webpPath)) {
                    @unlink($webpPath);
                }
            } catch (\Throwable $e) {
                // Per-file failure must not blow up the whole batch into a 500.
                // Common causes: storage/ not writable (chown -R www:www storage),
                // gd missing imagewebp(), disk full, db schema mismatch.
                $errors[] = $originalName . ': ' . $e->getMessage();
                report($e);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'files' => $uploaded,
                'errors' => $errors,
            ], $uploaded ? 201 : 422);
        }

        if (empty($uploaded) && $errors) {
            return back()->withErrors([
                'files' => 'Upload that bai: ' . implode(' | ', $errors) . ' (Kiem tra quyen ghi storage/ va extension gd co WebP).',
            ]);
        }

        $msg = count($uploaded) . ' file da duoc upload.';
        if ($errors) {
            $msg .= ' (' . count($errors) . ' file loi: ' . implode(' | ', $errors) . ')';
        }
        return back()->with('success', $msg);
    }

    /**
     * Update media info (name, alt, folder)
     */
    public function update(Request $request, Media $medium)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'alt' => 'nullable|string|max:255',
            'folder' => 'nullable|string|max:255',
        ]);

        $medium->update($data);

        if ($request->wantsJson()) {
            return response()->json(['media' => $medium->fresh()]);
        }

        return back()->with('success', 'Đã cập nhật thông tin file.');
    }

    /**
     * Delete media
     */
    public function destroy(Request $request, Media $medium)
    {
        // Delete file from disk
        Storage::disk($medium->disk)->delete($medium->path);
        Storage::disk($medium->disk)->delete('thumbnails/' . $medium->path);

        $medium->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xoá file.']);
        }

        return back()->with('success', 'Đã xoá file.');
    }

    /**
     * Bulk delete
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id',
        ]);

        $items = Media::whereIn('id', $request->ids)->get();
        foreach ($items as $item) {
            Storage::disk($item->disk)->delete($item->path);
            Storage::disk($item->disk)->delete('thumbnails/' . $item->path);
            $item->delete();
        }

        return back()->with('success', count($items) . ' file đã được xoá.');
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-zA-Z0-9_\-]+$/',
            'parent' => 'nullable|string|max:255',
        ]);

        $parent = $request->input('parent', '/');
        $folderPath = $parent === '/' ? $request->name : $parent . '/' . $request->name;

        // Create dir on disk
        Storage::disk('public')->makeDirectory('media/' . $folderPath);

        return back()->with('success', 'Thư mục "' . $request->name . '" đã được tạo.');
    }

    /**
     * API endpoint: browse media for picker
     */
    public function browse(Request $request)
    {
        $query = Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->latest();

        if ($request->filled('folder') && $request->folder !== '/') {
            $query->where('folder', $request->folder);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type') && $request->type !== 'image') {
            $query->where('mime_type', 'like', $request->type . '/%');
        }

        $paginated = $query->paginate(40)->toArray();
        $paginated['folders'] = Media::folders();

        return response()->json($paginated);
    }

    /**
     * Convert image to WebP format
     */
    private function convertToWebP($file): ?string
    {
        try {
            $sourcePath = $file->getRealPath();
            $mime = $file->getMimeType();

            // Load image based on mime type
            $image = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($sourcePath),
                'image/png' => @imagecreatefrompng($sourcePath),
                'image/gif' => @imagecreatefromgif($sourcePath),
                default => null,
            };

            if (!$image) {
                return null;
            }

            // Preserve transparency for PNG/GIF
            imagealphablending($image, false);
            imagesavealpha($image, true);

            // Create temporary WebP file
            $tempPath = sys_get_temp_dir() . '/' . uniqid('webp_') . '.webp';
            
            // Convert to WebP with quality 85 (good balance between quality and size)
            $success = imagewebp($image, $tempPath, 85);
            
            imagedestroy($image);

            return $success ? $tempPath : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create thumbnail (300px wide, proportional height)
     */
    private function createThumbnail($file, string $storagePath, string $fileName): void
    {
        try {
            $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (!$image) return;

            $origW = imagesx($image);
            $origH = imagesy($image);

            if ($origW <= 300) {
                imagedestroy($image);
                return; // No need for thumbnail if small enough
            }

            $newW = 300;
            $newH = (int) round($origH * ($newW / $origW));

            $thumb = imagecreatetruecolor($newW, $newH);

            // Preserve transparency for PNG
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);

            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            $thumbDir = storage_path('app/public/thumbnails/' . $storagePath);
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }

            // Always save thumbnails as WebP for optimal size
            $thumbFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
            $thumbPath = $thumbDir . '/' . $thumbFileName;
            imagewebp($thumb, $thumbPath, 80);

            imagedestroy($image);
            imagedestroy($thumb);
        } catch (\Exception $e) {
            // Thumbnail creation failed silently
        }
    }
}
