<?php
/**
 * Copyright 2020 Muvi LLC.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

namespace FacebookExtended;

use FacebookExtended\FileUpload\FacebookVideo;
use FacebookExtended\FileUpload\FacebookResumableUploader;
use FacebookExtended\FileUpload\FacebookTransferChunk;

use Facebook\Facebook as Fb;

/**
 * Extends the base Facebook class from Facebook Graph SDK package
 *
 * @category FacebookSDK
 * @package  MuviFacebook
 */
class Facebook extends Fb
{
    /**
     * Factory to create FacebookVideo's.
     *
     * @param string $pathToFile File Path
     *
     * @return FacebookVideoExtended
     *
     * @throws FacebookSDKException
     */
    public function videoToUpload($pathToFile)
    {
        return new FacebookVideo($pathToFile);
    }

    /**
     * Upload a video in chunks.
     *
     * @param int $target The id of the target node before the /videos edge.
     * @param string $pathToFile The full path to the file.
     * @param array $metadata The metadata associated with the video file.
     * @param string|null $accessToken The access token.
     * @param int $maxTransferTries The max times to retry a failed upload chunk.
     * @param string|null $graphVersion The Graph API version to use.
     * @param int|null $chunkSize Chunk size to be uploaded during multipart.
     *
     * @return array
     *
     * @throws FacebookSDKException
     */
    // phpcs:ignore
    public function uploadVideo($target, $pathToFile, $metadata = [], $accessToken = null, $maxTransferTries = 5, $graphVersion = null, $chunkSize = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultGraphVersion;

        $uploader = new FacebookResumableUploader($this->app, $this->client, $accessToken, $graphVersion, $chunkSize);
        $endpoint = '/'.$target.'/videos';
        $file = $this->videoToUpload($pathToFile);
        /**
         * The file resource is required to use the same resource.
         * Otherwise for remote files it continues to download the
         * chunks from the server
         */
        $fileResource = $file->getFileResource();
        // The size of the file
        $fileSize = $file->getSize();

        $chunk = $uploader->start($endpoint, $file, $fileResource, $fileSize);

        do {
            $chunk = $this->maxTriesTransfer($uploader, $endpoint, $chunk, $maxTransferTries, $fileResource, $fileSize);
        } while (!$chunk->isLastChunk());

        $file->close();

        return [
          'video_id' => $chunk->getVideoId(),
          'success' => $uploader->finish($endpoint, $chunk->getUploadSessionId(), $metadata),
        ];
    }

    /**
     * Attempts to upload a chunk of a file in $retryCountdown tries.
     *
     * @param FacebookResumableUploader $uploader
     * @param string $endpoint
     * @param FacebookTransferChunk $chunk
     * @param int $retryCountdown
     * @param Resource|null $fileResource
     * @param integer $fileSize
     *
     * @return FacebookTransferChunk
     *
     * @throws FacebookSDKException
     */
    // phpcs:ignore
    private function maxTriesTransfer(FacebookResumableUploader $uploader, $endpoint, FacebookTransferChunk $chunk, $retryCountdown, $fileResource = null, $fileSize = null)
    {
        $newChunk = $uploader->transfer($endpoint, $chunk, $retryCountdown < 1, $fileResource, $fileSize);

        if ($newChunk !== $chunk) {
            return $newChunk;
        }

        $retryCountdown--;

        // If transfer() returned the same chunk entity, the transfer failed but is resumable.
        return $this->maxTriesTransfer($uploader, $endpoint, $chunk, $retryCountdown, $fileResource, $fileSize);
    }
}
