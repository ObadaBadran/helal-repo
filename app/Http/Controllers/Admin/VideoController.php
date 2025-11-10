<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Course;
use App\PaginationTrait;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class VideoController extends Controller
{
    use PaginationTrait;

    public function index(Request $request, $course_id)
    {
        $user = auth('api')->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        try {
            $lang = $request->query('lang', 'en');

            $videosQuery = Video::where('course_id', $course_id)
                ->select(
                    'id',
                    'course_id',
                    $lang === 'ar' ? 'title_ar as title' : 'title_en as title',
                    $lang === 'ar' ? 'subTitle_ar as subTitle' : 'subTitle_en as subTitle',
                    $lang === 'ar' ? 'description_ar as description' : 'description_en as description',
                    'path',
                    'youtube_path',
                    'cover'
                );

            $videosPaginated = $this->paginateResponse(
                $request,
                $videosQuery,
                'Videos',
                function ($video) {
                    return [
                        'id' => $video->id,
                        'course_id' => $video->course_id,
                        'title' => $video->title,
                        'subTitle' => $video->subTitle,
                        'description' => $video->description,
                        'path' => $video->path ? asset($video->path) : null,
                        'youtube_path' => $video->youtube_path,
                        'cover' => $video->cover ? asset($video->cover) : null,
                    ];
                }
            );

            return response()->json([
                'status' => 'success',
                'message' => "Videos retrieved successfully",
                'course_id' => $course_id,
                'videos' => $videosPaginated,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve videos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,id',
                'path' => 'required_without:youtube_path|file|mimes:mp4,mov,avi',
                'youtube_path' => 'required_without:path|string|max:255',
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'required|string',
                'description_ar' => 'required|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            // تخزين الفيديو
            if ($request->hasFile('path')) {
                $videoFile = $request->file('path');
                $videoName = uniqid('video_') . '.' . $videoFile->getClientOriginalExtension();
                $videoFile->move(public_path('storage/videos'), $videoName);
                $validatedData['path'] = 'storage/videos/' . $videoName;
            }

            // تخزين الغلاف
            if ($request->hasFile('cover')) {
                $coverFile = $request->file('cover');
                $coverName = uniqid('cover_') . '.' . $coverFile->getClientOriginalExtension();
                $coverFile->move(public_path('storage/covers'), $coverName);
                $validatedData['cover'] = 'storage/covers/' . $coverName;
            }

            $video = Video::create($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Video created successfully',
                'video' => $video
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create video',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $video = Video::findOrFail($id);

            $validatedData = $request->validate([
                'path' => 'nullable|file|mimes:mp4,mov,avi|prohibits:youtube_path',
                'youtube_path' => 'nullable|string|max:255|prohibits:path',
                'title_en' => 'nullable|string|max:255',
                'title_ar' => 'nullable|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            if ($request->hasFile('path')) {
                if ($video->path && file_exists(public_path($video->path))) {
                    unlink(public_path($video->path));
                }
                $videoFile = $request->file('path');
                $videoName = uniqid('video_') . '.' . $videoFile->getClientOriginalExtension();
                $videoFile->move(public_path('storage/videos'), $videoName);
                $validatedData['path'] = 'storage/videos/' . $videoName;
            }

            if ($request->hasFile('cover')) {
                if ($video->cover && file_exists(public_path($video->cover))) {
                    unlink(public_path($video->cover));
                }
                $coverFile = $request->file('cover');
                $coverName = uniqid('cover_') . '.' . $coverFile->getClientOriginalExtension();
                $coverFile->move(public_path('storage/covers'), $coverName);
                $validatedData['cover'] = 'storage/covers/' . $coverName;
            }

            $video->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Video updated successfully',
                'video' => $video
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update video',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $video = Video::findOrFail($id);

            if ($video->path && file_exists(public_path($video->path))) {
                unlink(public_path($video->path));
            }

            if ($video->cover && file_exists(public_path($video->cover))) {
                unlink(public_path($video->cover));
            }

            $video->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Video deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete video',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
