<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    /**
     * Extract a thumbnail URL from embed code (YouTube / Vimeo).
     * Returns null if we can't determine one.
     */
    private function extractThumbnailFromEmbed(?string $embedCode): ?string
    {
        if (!$embedCode) return null;

        // Extract iframe src
        if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $embedCode, $m)) return null;
        $src = $m[1];

        // YouTube: extract video ID
        if (preg_match('/(?:youtube\.com|youtube-nocookie\.com)\/embed\/([a-zA-Z0-9_-]{11})/', $src, $yt)) {
            return 'https://img.youtube.com/vi/' . $yt[1] . '/hqdefault.jpg';
        }

        // Vimeo: extract video ID, use vumbnail.com service (no API key needed)
        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $src, $vm)) {
            return 'https://vumbnail.com/' . $vm[1] . '.jpg';
        }

        return null;
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

            // Auto-fill thumbnail from embed if not provided
            if (empty($validated['thumbnail'])) {
                $validated['thumbnail'] = $this->extractThumbnailFromEmbed($validated['embed_code']);
            }
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

            // Auto-fill thumbnail from embed if not provided
            if (empty($validated['thumbnail'])) {
                $validated['thumbnail'] = $this->extractThumbnailFromEmbed($validated['embed_code']);
            }
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
