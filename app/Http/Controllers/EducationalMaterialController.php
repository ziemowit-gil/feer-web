<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;

class EducationalMaterialController extends Controller
{
    public function index()
    {
        $materials = EducationalMaterial::where('is_published', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('educational-materials.index', compact('materials'));
    }
}
