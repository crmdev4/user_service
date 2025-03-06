<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image as Image;
use Intervention\Image\Interfaces\EncoderInterface;
use Illuminate\Http\File;

class MinioServiceController extends Controller
{
    public function fileUploaderNew(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
                'folder' => 'string'
            ]);

            $fileName = $request->file('file')->getClientOriginalName();
            $fileExtension = $request->file('file')->getClientOriginalExtension();
            $folder = $request->folder ?? '/';
            $filePath = Storage::disk('minio')->put($folder, $request->file('file'));
            \Log::info("file path : " . $filePath);

            $environment = app()->environment();
            $baseUrl = $environment === 'production'
                ? config('apiendpoints.PRODUCTION_S3_URL')
                : config('apiendpoints.DEV_S3_URL');

            $fileUrl = rtrim($baseUrl, '/') . '/rentfms/' . $filePath;

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'File has been uploaded',
                'file' => $fileUrl
            ]);
        } catch (\Exception $e) {
            \Log::info("Error minio upload service message : ");
            \Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function fileUploader(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,heic,pdf,doc,docx|max:5128', // increase to 5MB
                'folder' => 'string'
            ]);

            $file = $request->file('file');
            $folder = $request->folder ?? '/';
            $fileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $tempFilePath = $file->getPathname();

            // compress image
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'heic'])) {
                $image = Image::read($tempFilePath);
                $image->encodeByExtension($fileExtension, 75);

                // resize size
                /* $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                    $constraint->quality(75);
                }); */

                // Limit image height
                /* if ($image->height() > 800) {
                    $image->resize(null, 800, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                        $constraint->quality(75);
                    });
                } */

                $tempFilePath = tempnam(sys_get_temp_dir(), 'compressed_');
                $image->save($tempFilePath);
            }

            // upload to minio
            $filePath = Storage::disk('minio')->putFileAs($folder, new File($tempFilePath), $fileName);

            \Log::info("file path : " . $filePath);

            $environment = app()->environment();
            $baseUrl = $environment === 'production'
                ? config('apiendpoints.PRODUCTION_S3_URL')
                : config('apiendpoints.DEV_S3_URL');

            $fileUrl = rtrim($baseUrl, '/') . '/rentfms/' . $filePath;

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'File has been uploaded',
                'file' => $fileUrl
            ]);
        } catch (\Exception $e) {
            \Log::info("Error minio upload service message : ");
            \Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fileDelete(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string'
            ]);
            $environment = app()->environment();

            if ($environment === 'production') {
                $baseUrl = config('apiendpoints.PRODUCTION_S3_URL') . '/rentfms/';
            } else {
                $baseUrl = config('apiendpoints.DEV_S3_URL') . '/rentfms/';
            }

            // Determine the environment and base URL

            $baseUrl = $environment === 'production'
                ? config('apiendpoints.PRODUCTION_S3_URL') . '/rentfms/'
                : config('apiendpoints.DEV_S3_URL') . '/rentfms/';

            // Extract file path from the provided filename
            $filePath = str_replace($baseUrl, '', $request->filename);


            $disk = Storage::disk('minio');
            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
                return response()->json([
                    'success' => true,
                    'message' => 'File has been deleted',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'File delete failed. File might not exist.'
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Error in MinIO delete service: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleBlobUpload(Request $request)
    {
        // Handle upload blob string base64 from Flutter
        try {
            $request->validate([
                'file' => 'required|string',
                'folder' => 'string',
            ]);

            $file = $request->file;
            $folder = $request->folder ?? '/';

            // Check and extract the base64 data
            if (preg_match('/^data:(.*?);base64,(.*)$/', $file, $matches)) {
                $mimeType = $matches[1]; // Extract MIME type (e.g., image/jpeg)
                $base64Data = $matches[2]; // Extract the actual base64 data
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid base64 file format.',
                ], 400);
            }

            // Decode the Base64 string
            $decodedFile = base64_decode($base64Data);

            // Validate decoding
            if ($decodedFile === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode base64 file.',
                ], 400);
            }

            // Determine file extension based on MIME type
            $extension = match ($mimeType) {
                'image/png', 'image/x-png' => 'png',
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf',
                default => null,
            };

            // Check for unsupported file types
            if (!$extension) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported file type. Only PNG, JPG, GIF, and PDF are allowed.',
                ], 400);
            }

            // Generate unique file name
            $fileName = uniqid() . '.' . $extension;

            // Save the file to MinIO
            $filePath = Storage::disk('minio')->put($folder . '/' . $fileName, $decodedFile);

            // Generate file URL
            $environment = app()->environment();
            $baseUrl = $environment === 'production'
                ? config('apiendpoints.PRODUCTION_S3_URL')
                : config('apiendpoints.DEV_S3_URL');

            $fileUrl = rtrim($baseUrl, '/') . '/rentfms/' . $folder . '/' . $fileName;

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'File has been uploaded',
                'file' => $fileUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error("File upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

}