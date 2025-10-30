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

    public function index(Request $request, $course_id) {

        try{
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
                    'cover',
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
                        'cover' => $video->cover,
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
        } catch (\Exception $e) {
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
                'path' => 'nullable|file|mimes:mp4,mov,avi',
                'youtube_path' => 'nullable|string|max:255',
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'required|string',
                'description_ar' => 'required|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            if ($request->hasFile('path')) {
                $path = $request->file('path')->store('videos', 'public');
                $validatedData['path'] = '/storage/' . $path;
            }

            if ($request->hasFile('cover')) {
                $path = $request->file('cover')->store('covers', 'public');
                $validatedData['cover'] = '/storage/' . $path;
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

    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');

            $video = Video::select(
                'id',
                'course_id',
                $lang === 'ar' ? 'title_ar as title' : 'title_en as title',
                $lang === 'ar' ? 'subTitle_ar as subTitle' : 'subTitle_en as subTitle',
                $lang === 'ar' ? 'description_ar as description' : 'description_en as description',
                'path',
                'youtube_path',
                'cover'
            )->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Video retrieved successfully',
                'video' => [
                    'id' => $video->id,
                    'course_id' => $video->course_id,
                    'title' => $video->title,
                    'subTitle' => $video->subTitle,
                    'description' => $video->description,
                    'path' => $video->path ? asset($video->path) : null,
                    'youtube_path' => $video->youtube_path,
                    'cover' => $video->cover ? asset($video->cover) : null,
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $video = Video::findOrFail($id);

            $validatedData = $request->validate([
                'course_id' => 'nullable|exists:courses,id',
                'path' => 'nullable|file|mimes:mp4,mov,avi|max:51200',
                'youtube_path' => 'nullable|string|max:255',
                'title_en' => 'nullable|string|max:255',
                'title_ar' => 'nullable|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                
            ]);

            if ($request->hasFile('path')) {
                if ($video->path && file_exists(public_path($video->path))) {
                    unlink(public_path($video->path));
                }
                $path = $request->file('path')->store('videos', 'public');
                $validatedData['path'] = '/storage/' . $path;
            }

            if ($request->hasFile('cover')) {
                if ($video->cover && file_exists(public_path($video->cover))) {
                    unlink(public_path($video->cover));
                }
                $path = $request->file('cover')->store('covers', 'public');
                $validatedData['cover'] = '/storage/' . $path;
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
