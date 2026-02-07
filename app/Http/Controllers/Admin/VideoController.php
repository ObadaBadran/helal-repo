<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\PaginationTrait;
use App\Services\R2Service;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class VideoController extends Controller
{
    use PaginationTrait;

    protected $r2Service;

    public function __construct(R2Service $r2Service)
    {
        $this->r2Service = $r2Service;
    }

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
                        'path' => $video->path ? Storage::disk('s3')->url($video->path) : null,
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
                'video_key' => 'required_without:youtube_path|string|max:255',
                'youtube_path' => 'required_without:video_key|string|max:255',
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'required|string',
                'description_ar' => 'required|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            // تخزين الفيديو
            if ($request->has('video_key')) {
                $validatedData['path'] = $request->input('video_key');
            }

            // تخزين الغلاف
            if ($request->hasFile('cover')) {
                $coverFile = $request->file('cover');
                $coverName = uniqid('cover_') . '.' . $coverFile->getClientOriginalExtension();
                $coverFile->move(public_path('covers'), $coverName);
                $validatedData['cover'] = 'covers/' . $coverName;
            }

            $video = Video::create($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Video created successfully'
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

                'video_key' => 'nullable|string|max:255|prohibits:youtube_path',
                'youtube_path' => 'nullable|string|max:255|prohibits:video_key',
                'title_en' => 'nullable|string|max:255',
                'title_ar' => 'nullable|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            if ($request->has('youtube_path')) {
                if ($video->path && Storage::disk('s3')->exists($video->path)) {
                    Storage::disk('s3')->delete($video->path);
                }
                $validatedData['youtube_path'] = $request->get('youtube_path');
                $validatedData['path'] = null;
            }

            if ($request->has('video_key')) {
                if ($video->path && Storage::disk('s3')->exists($video->path)) {
                    Storage::disk('s3')->delete($video->path);
                }
                $validatedData['path'] = $request->get('video_key');
                $validatedData['youtube_path'] = null;
            }

            if ($request->hasFile('cover')) {
                if ($video->cover && file_exists(public_path($video->cover))) {
                    unlink(public_path($video->cover));
                }
                $coverFile = $request->file('cover');
                $coverName = uniqid('cover_') . '.' . $coverFile->getClientOriginalExtension();
                $coverFile->move(public_path('covers'), $coverName);
                $validatedData['cover'] = 'covers/' . $coverName;
            }

            $video->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Video updated successfully'
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

            if ($video->path && Storage::disk('s3')->exists($video->path)) {
                Storage::disk('s3')->delete($video->path);
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

    public function show(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

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
                'cover',
                'created_at',
                'updated_at'
            )->findOrFail($id);

            $videoData = [
                'id' => $video->id,
                'course_id' => $video->course_id,
                'title' => $video->title,
                'subTitle' => $video->subTitle,
                'description' => $video->description,
                'path' => $video->path ? Storage::disk('s3')->url($video->path) : null,
                'youtube_path' => $video->youtube_path,
                'cover' => $video->cover ? asset($video->cover) : null,
                'created_at' => $video->created_at,
                'updated_at' => $video->updated_at,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Video retrieved successfully',
                'video' => $videoData
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve video',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function initiateUpload(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'contentType' => 'nullable|string',
        ]);

        try {
            $data = $this->r2Service->initiateMultipartUpload(
                $request->input('filename'),
                $request->input('contentType', 'video/mp4')
            );

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('R2 Initiate Upload Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUploadPartUrl(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'uploadId' => 'required|string',
            'partNumber' => 'required|integer',
        ]);

        try {
            $url = $this->r2Service->getPresignedPartUrl(
                $request->input('key'),
                $request->input('uploadId'),
                $request->input('partNumber')
            );

            return response()->json([
                'status' => 'success',
                'url' => $url,
            ]);
        } catch (Exception $e) {
            Log::error('R2 Get Part URL Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get upload URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function completeUpload(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'uploadId' => 'required|string',
            'parts' => 'required|array',
            'parts.*.PartNumber' => 'required|integer',
            'parts.*.ETag' => 'required|string',
        ]);

        try {
            $key = $this->r2Service->completeMultipartUpload(
                $request->input('key'),
                $request->input('uploadId'),
                $request->input('parts')
            );

            return response()->json([
                'status' => 'success',
                'key' => $key,
            ]);
        } catch (Exception $e) {
            Log::error('R2 Complete Upload Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
