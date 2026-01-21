<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Podcast;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PodcastController extends Controller
{
    // عرض كل البودكاستات مع فلترة اللغة و pagination
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en'); 
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 10);

            $podcasts = Podcast::orderBy('id', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = $podcasts->map(function ($podcast) use ($lang) {
                return [
                    'id' => $podcast->id,
                    'title' => $lang === 'ar' ? $podcast->title_ar : $podcast->title_en,
                    'description' => $lang === 'ar' ? $podcast->description_ar : $podcast->description_en,
                    'youtube_link' => $podcast->youtube_link,
                    'cover' => $podcast->cover ? asset($podcast->cover) : null,
                    'created_at' => $podcast->created_at,
                    'updated_at' => $podcast->updated_at,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $podcasts->currentPage(),
                    'last_page' => $podcasts->lastPage(),
                    'per_page' => $podcasts->perPage(),
                    'total' => $podcasts->total(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'message' => $e->getMessage()], 500);
        }
    }

    // عرض بودكاست واحد مع اللغة
    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');
            $podcast = Podcast::findOrFail($id);

            return response()->json([
                'id' => $podcast->id,
                'title' => $lang === 'ar' ? $podcast->title_ar : $podcast->title_en,
                'description' => $lang === 'ar' ? $podcast->description_ar : $podcast->description_en,
                'youtube_link' => $podcast->youtube_link,
                'cover' => $podcast->cover ? asset($podcast->cover) : null,
                'created_at' => $podcast->created_at,
                'updated_at' => $podcast->updated_at,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Podcast not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Unexpected error occurred.', 'message' => $e->getMessage()], 500);
        }
    }

    // إنشاء بودكاست جديد مع رفع الصورة
   public function store(Request $request)
{
    try {
        $data = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'description_en' => 'required|string',
            'description_ar' => 'required|string',
            'youtube_link' => 'required|url',
            'cover' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:5120',
        ]);

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = uniqid('podcast_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('podcasts'), $filename);
            $coverPath = 'podcasts/' . $filename;
        }

        $podcast = Podcast::create(array_merge($data, ['cover' => $coverPath]));

        // تعديل الرابط ليكون كامل
        $podcast->cover = $podcast->cover ? asset($podcast->cover) : null;

        return response()->json([
            'status' => true,
            'message' => 'Podcast created successfully.',
            'data' => $podcast
        ], 201);

    } catch (Exception $e) {
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}

    // تحديث بودكاست
    public function update(Request $request, $id)
{
    try {
        $podcast = Podcast::findOrFail($id);

        $data = $request->validate([
            'title_en' => 'sometimes|required|string|max:255',
            'title_ar' => 'sometimes|required|string|max:255',
            'description_en' => 'sometimes|required|string',
            'description_ar' => 'sometimes|required|string',
            'youtube_link' => 'sometimes|required|url',
            'cover' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:5120',
        ]);

        if ($request->hasFile('cover')) {
            if ($podcast->cover && file_exists(public_path($podcast->cover))) {
                unlink(public_path($podcast->cover));
            }

            $file = $request->file('cover');
            $filename = uniqid('podcast_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('podcasts'), $filename);
            $data['cover'] = 'podcasts/' . $filename;
        }

        $podcast->update($data);

        // تعديل الرابط ليكون كامل
        $podcast->cover = $podcast->cover ? asset($podcast->cover) : null;

        return response()->json([
            'status' => true,
            'message' => 'Podcast updated successfully.',
            'data' => $podcast
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Podcast not found.'], 404);
    } catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong.', 'message' => $e->getMessage()], 500);
    }
}

    // حذف بودكاست
    public function destroy($id)
    {
        try {
            $podcast = Podcast::findOrFail($id);

            if ($podcast->cover && file_exists(public_path($podcast->cover))) {
                unlink(public_path($podcast->cover));
            }

            $podcast->delete();

            return response()->json(['status' => true, 'message' => 'Podcast deleted successfully.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Podcast not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'message' => $e->getMessage()], 500);
        }
    }
}
