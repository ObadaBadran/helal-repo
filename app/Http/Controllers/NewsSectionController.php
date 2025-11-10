<?php

namespace App\Http\Controllers;

use App\Models\NewsSection;
use App\Models\NewsSectionImage;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class NewsSectionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en');
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', (int) $request->query('sizer', 10));

            $sections = NewsSection::with('images')->orderBy('id', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = $sections->map(function ($section) use ($lang) {
                $images = $section->images->map(fn($img) => asset('storage/' . $img->image));
                return [
                    'id' => $section->id,
                    'title' => $lang === 'ar' ? $section->title_ar : $section->title_en,
                    'subtitle' => $lang === 'ar' ? $section->subtitle_ar : $section->subtitle_en,
                    'description' => $lang === 'ar' ? $section->description_ar : $section->description_en,
                    'images' => $images,
                ];
            });

            if ($data->isEmpty()) {
                return response()->json(['message' => 'No news sections found.'], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $sections->currentPage(),
                    'last_page' => $sections->lastPage(),
                    'per_page' => $sections->perPage(),
                    'total' => $sections->total(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');
            $section = NewsSection::with('images')->findOrFail($id);
            $images = $section->images->map(fn($img) => asset('storage/' . $img->image));

            return response()->json([
                'id' => $section->id,
                'title' => $lang === 'ar' ? $section->title_ar : $section->title_en,
                'subtitle' => $lang === 'ar' ? $section->subtitle_ar : $section->subtitle_en,
                'description' => $lang === 'ar' ? $section->description_ar : $section->description_en,
                'images' => $images,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Section not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unexpected error occurred.', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ إنشاء قسم جديد مع صور (تخزين في public/storage)
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'subtitle_en' => 'nullable|string|max:255',
                'subtitle_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'image.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            ]);

            $newsSection = NewsSection::create($data);
            $imagePaths = [];

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $imageName = uniqid('news_') . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('storage/news_images'), $imageName);

                    NewsSectionImage::create([
                        'news_section_id' => $newsSection->id,
                        'image' => 'news_images/' . $imageName,
                    ]);

                    $imagePaths[] = asset('storage/news_images/' . $imageName);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'News section created successfully',
                'data' => [
                    'id' => $newsSection->id,
                    'title_en' => $newsSection->title_en,
                    'title_ar' => $newsSection->title_ar,
                    'subtitle_en' => $newsSection->subtitle_en,
                    'subtitle_ar' => $newsSection->subtitle_ar,
                    'description_en' => $newsSection->description_en,
                    'description_ar' => $newsSection->description_ar,
                    'image' => $imagePaths,
                    'created_at' => $newsSection->created_at,
                    'updated_at' => $newsSection->updated_at,
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_message' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ تعديل قسم وصوره
    public function update(Request $request, $id)
    {
        try {
            $newsSection = NewsSection::with('images')->findOrFail($id);

            $data = $request->validate([
                'title_en' => 'sometimes|nullable|string|max:255',
                'title_ar' => 'sometimes|nullable|string|max:255',
                'subtitle_en' => 'sometimes|nullable|string|max:255',
                'subtitle_ar' => 'sometimes|nullable|string|max:255',
                'description_en' => 'sometimes|nullable|string',
                'description_ar' => 'sometimes|nullable|string',
                'image.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            ]);

            $newsSection->update($data);

            if ($request->hasFile('image')) {
                // حذف الصور القديمة من public/storage
                foreach ($newsSection->images as $oldImage) {
                    $oldPath = public_path('storage/' . $oldImage->image);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $oldImage->delete();
                }

                // رفع الصور الجديدة
                foreach ($request->file('image') as $image) {
                    $imageName = uniqid('news_') . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('storage/news_images'), $imageName);

                    NewsSectionImage::create([
                        'news_section_id' => $newsSection->id,
                        'image' => 'news_images/' . $imageName,
                    ]);
                }
            }

            $newsSection->load('images');
            $imagePaths = $newsSection->images->map(fn($img) => asset('storage/' . $img->image))->toArray();

            return response()->json([
                'status' => true,
                'message' => 'News section updated successfully',
                'data' => [
                    'id' => $newsSection->id,
                    'title_en' => $newsSection->title_en,
                    'title_ar' => $newsSection->title_ar,
                    'subtitle_en' => $newsSection->subtitle_en,
                    'subtitle_ar' => $newsSection->subtitle_ar,
                    'description_en' => $newsSection->description_en,
                    'description_ar' => $newsSection->description_ar,
                    'image' => $imagePaths,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Section not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ حذف قسم وصوره من public/storage
    public function destroy($id)
    {
        try {
            $newsSection = NewsSection::with('images')->findOrFail($id);

            foreach ($newsSection->images as $image) {
                $path = public_path('storage/' . $image->image);
                if (file_exists($path)) {
                    unlink($path);
                }
                $image->delete();
            }

            $newsSection->delete();

            return response()->json([
                'status' => true,
                'message' => 'News section deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Section not found.'], 404);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
