<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Aws\S3\S3Client;

class R2Service
{
    protected $s3Client;
    protected $bucket;

    public function __construct()
    {
        // optimize: reuse the s3 client from the storage driver if possible, 
        // but creating a new one specifically for low-level operations is safer 
        // to ensure we have the right configuration for presigned URLs.
        
        $config = config('filesystems.disks.s3');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $config['region'],
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'],
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);

        $this->bucket = $config['bucket'];
    }

    /**
     * Initiate a multipart upload.
     *
     * @param string $fileName
     * @param string $contentType
     * @return array
     */
    public function initiateMultipartUpload(string $fileName, string $contentType = 'video/mp4'): array
    {
        // Generate a unique path for the file
        $dateTime = now()->format('YmdHis');
        $key = 'videos/' . $dateTime . '-' . uniqid() . '-' . Str::slug($fileName);

        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'ContentType' => $contentType,
            // 'ACL'    => 'public-read', // Uncomment if files should be public
        ]);

        return [
            'uploadId' => $result['UploadId'],
            'key' => $result['Key'],
        ];
    }

    /**
     * Get a presigned URL for a specific part.
     *
     * @param string $key
     * @param string $uploadId
     * @param int $partNumber
     * @return string
     */
    public function getPresignedPartUrl(string $key, string $uploadId, int $partNumber): string
    {
        $command = $this->s3Client->getCommand('UploadPart', [
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $request = $this->s3Client->createPresignedRequest($command, '+1 hour');

        return (string) $request->getUri();
    }

    /**
     * Complete a multipart upload.
     *
     * @param string $key
     * @param string $uploadId
     * @param array $parts  Array of ['PartNumber' => 1, 'ETag' => '"etag"']
     * @return string The location of the uploaded file
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): string
    {
        $result = $this->s3Client->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ]);

        return $result['Key'];
    }
    
    /**
     * Abort a multipart upload.
     * 
     * @param string $key
     * @param string $uploadId
     * @return void
     */
    public function abortMultipartUpload(string $key, string $uploadId): void
    {
         $this->s3Client->abortMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);
    }
}
