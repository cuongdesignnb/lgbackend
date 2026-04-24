<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VideoController extends Controller
{
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
