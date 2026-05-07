<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VideoController extends Controller
{
    /**
     * Domains allowed inside iframe src for embedded videos.
     * Reject anything else to keep storefront safe.
     */
    private const ALLOWED_EMBED_HOSTS = [
        'youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com',
        'player.vimeo.com', 'vimeo.com',
        'tiktok.com', 'www.tiktok.com',
        'facebook.com', 'www.facebook.com',
    ];

    /**
     * Validate an embed_code: must be a single iframe whose src host is allowed,
     * with no <script> tags. Returns the (lightly normalised) iframe markup or
     * throws ValidationException with a clear admin-facing message.
     */
    private function validateEmbedCode(?string $code, string $field = 'embed_code'): ?string
    {
        if ($code === null || trim($code) === '') return null;
        $code = trim($code);

        // Reject obvious script injections.
        if (preg_match('/<\s*script\b/i', $code)) {
            throw ValidationException::withMessages([
                $field => 'Mã nhúng chứa thẻ <script> — không được phép. Chỉ dán iframe từ YouTube / TikTok / Facebook / Vimeo.',
            ]);
        }

        // Must contain at least one iframe.
        if (!preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $code, $m)) {
            throw ValidationException::withMessages([
                $field => 'Mã nhúng phải là thẻ <iframe ... src="..."> hợp lệ.',
            ]);
        }
        $src = $m[1];

        $host = strtolower(parse_url($src, PHP_URL_HOST) ?: '');
        if ($host === '') {
            throw ValidationException::withMessages([
                $field => 'iframe src thiếu domain hợp lệ.',
            ]);
        }

        $allowed = false;
        foreach (self::ALLOWED_EMBED_HOSTS as $h) {
            if ($host === $h || str_ends_with($host, '.' . $h)) { $allowed = true; break; }
        }
        if (!$allowed) {
            throw ValidationException::withMessages([
                $field => "Domain \"{$host}\" không nằm trong danh sách cho phép. Chỉ chấp nhận YouTube / Vimeo / TikTok / Facebook.",
            ]);
        }

        return $code;
    }

    public function index()
    {
        return Inertia::render('Admin/Videos/Index', [
            'videos' => Video::orderBy('sort_order')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Videos/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'embed_code' => 'nullable|string',
            'source' => 'required|in:embed,upload',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if (($validated['source'] ?? null) === 'embed') {
            $validated['embed_code'] = $this->validateEmbedCode($validated['embed_code'] ?? null);
        }

        Video::create($validated);

        return redirect()->route('admin.videos.index')
            ->with('success', 'Tạo video thành công');
    }

    public function edit(Video $video)
    {
        return Inertia::render('Admin/Videos/Edit', [
            'video' => $video,
        ]);
    }

    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'embed_code' => 'nullable|string',
            'source' => 'required|in:embed,upload',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if (($validated['source'] ?? null) === 'embed') {
            $validated['embed_code'] = $this->validateEmbedCode($validated['embed_code'] ?? null);
        }

        $video->update($validated);

        return redirect()->route('admin.videos.index')
            ->with('success', 'Cập nhật video thành công');
    }

    public function destroy(Video $video)
    {
        $video->delete();
        return redirect()->route('admin.videos.index')
            ->with('success', 'Xóa video thành công');
    }
}
