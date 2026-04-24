<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Catalogue;

class VideoController extends Controller
{
    public function index()
    {
        return response()->json(
            Video::active()->orderBy('sort_order')->latest()->take(12)->get()
        );
    }
}
