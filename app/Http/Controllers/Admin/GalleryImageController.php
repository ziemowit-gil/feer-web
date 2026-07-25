<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\Request;

class GalleryImageController extends Controller
{
    public function index()
    {
        $galleryImages = GalleryImage::orderBy('order')->get();

        return view('admin.gallery.index', compact('galleryImages'));
    }

    public function create()
    {
        return view('admin.gallery.form', ['galleryImage' => new GalleryImage]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        unset($data['image']);

        $galleryImage = GalleryImage::create($data);
        $galleryImage->addMediaFromRequest('image')->toMediaCollection('image');

        return redirect()->route('admin.galeria.index')->with('status', 'Zdjęcie zostało dodane.');
    }

    public function edit(GalleryImage $galleryImage)
    {
        return view('admin.gallery.form', compact('galleryImage'));
    }

    public function update(Request $request, GalleryImage $galleryImage)
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        unset($data['image']);

        $galleryImage->update($data);

        if ($request->hasFile('image')) {
            $galleryImage->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.galeria.index')->with('status', 'Zdjęcie zostało zaktualizowane.');
    }

    public function destroy(GalleryImage $galleryImage)
    {
        $galleryImage->delete();

        return redirect()->route('admin.galeria.index')->with('status', 'Zdjęcie zostało usunięte.');
    }
}
