<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalogue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CatalogueController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Catalogues/Index', [
            'catalogues' => Catalogue::orderBy('sort_order')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Catalogues/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'file_url' => 'nullable|string|max:500',
            'catalogue_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:51200',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('catalogue_file')) {
            $file = $request->file('catalogue_file');
            $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('catalogues', $fileName, 'public');
            $validated['file_url'] = '/storage/' . $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['catalogue_file']);

        if (empty($validated['file_url'])) {
            return back()->withErrors(['file_url' => 'Vui lòng tải lên file hoặc nhập URL.']);
        }

        Catalogue::create($validated);

        return redirect()->route('admin.catalogues.index')
            ->with('success', 'Tạo catalogue thành công');
    }

    public function edit(Catalogue $catalogue)
    {
        return Inertia::render('Admin/Catalogues/Edit', [
            'catalogue' => $catalogue,
        ]);
    }

    public function update(Request $request, Catalogue $catalogue)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'file_url' => 'nullable|string|max:500',
            'catalogue_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip|max:51200',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('catalogue_file')) {
            // Delete old file if local
            if ($catalogue->file_url && str_starts_with($catalogue->file_url, '/storage/catalogues/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $catalogue->file_url));
            }

            $file = $request->file('catalogue_file');
            $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('catalogues', $fileName, 'public');
            $validated['file_url'] = '/storage/' . $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['catalogue_file']);

        if (empty($validated['file_url']) && empty($catalogue->file_url)) {
            return back()->withErrors(['file_url' => 'Vui lòng tải lên file hoặc nhập URL.']);
        }

        if (empty($validated['file_url'])) {
            unset($validated['file_url']);
        }

        $catalogue->update($validated);

        return redirect()->route('admin.catalogues.index')
            ->with('success', 'Cập nhật catalogue thành công');
    }

    public function destroy(Catalogue $catalogue)
    {
        if ($catalogue->file_url && str_starts_with($catalogue->file_url, '/storage/catalogues/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $catalogue->file_url));
        }
        $catalogue->delete();

        return redirect()->route('admin.catalogues.index')
            ->with('success', 'Xóa catalogue thành công');
    }
}
