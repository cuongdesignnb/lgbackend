<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalogue;

class CatalogueController extends Controller
{
    public function index()
    {
        return response()->json(
            Catalogue::active()->orderBy('sort_order')->latest()->get()
        );
    }

    /**
     * Track download count
     */
    public function download(Catalogue $catalogue)
    {
        $catalogue->increment('download_count');
        return response()->json(['url' => $catalogue->file_url]);
    }
}
