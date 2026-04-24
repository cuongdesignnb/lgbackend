<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('position')
            ->orderBy('sort_order')
            ->paginate(50);

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Banners/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'badge' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'link' => 'nullable|string|max:500',
            'position' => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'metadata' => 'nullable|array',
        ]);

        // Handle file upload
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('banners', $fileName, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        unset($validated['image_file']);

        if (empty($validated['image'])) {
            return back()->withErrors(['image' => 'Vui lòng tải lên ảnh hoặc nhập URL ảnh.']);
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Tạo banner thành công');
    }

    public function edit(Banner $banner)
    {
        return Inertia::render('Admin/Banners/Edit', [
            'banner' => $banner,
        ]);
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'badge' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'link' => 'nullable|string|max:500',
            'position' => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'metadata' => 'nullable|array',
        ]);

        // Handle file upload
        if ($request->hasFile('image_file')) {
            // Delete old file if it was a local upload
            if ($banner->image && str_starts_with($banner->image, '/storage/banners/')) {
                $oldPath = str_replace('/storage/', '', $banner->image);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('image_file');
            $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('banners', $fileName, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        unset($validated['image_file']);

        if (empty($validated['image']) && empty($banner->image)) {
            return back()->withErrors(['image' => 'Vui lòng tải lên ảnh hoặc nhập URL ảnh.']);
        }

        // Keep old image if no new one provided
        if (empty($validated['image'])) {
            unset($validated['image']);
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Cập nhật banner thành công');
    }

    public function destroy(Banner $banner)
    {
        // Delete file if local
        if ($banner->image && str_starts_with($banner->image, '/storage/banners/')) {
            $path = str_replace('/storage/', '', $banner->image);
            Storage::disk('public')->delete($path);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Xóa banner thành công');
    }
}
