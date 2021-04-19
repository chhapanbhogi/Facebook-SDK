<?php
/**
 * Copyright 2017 Facebook, Inc.
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
namespace FacebookExtended\FileUpload;

use FacebookExtended\Facebook;
use Facebook\FileUpload\FacebookResumableUploader as FbResumableUploader;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookResumableUploadException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookApp;
use Facebook\FacebookClient;
use Facebook\FacebookRequest;

/**
 * Class FacebookResumableUploader
 *
 * @package Facebook
 */
class FacebookResumableUploader extends FbResumableUploader
{
    /**
     * @var integer $chunkSize chunk size to be uploaded to Facebook
     */
    protected $chunkSize;

    /**
     * @param FacebookApp             $app
     * @param FacebookClient          $client
     * @param AccessToken|string|null $accessToken
     * @param string                  $graphVersion
     * @param Integer|null            $chunkSize
     */
    public function __construct(FacebookApp $app, FacebookClient $client, $accessToken, $graphVersion, $chunkSize)
    {
        $this->chunkSize = $chunkSize;
        parent::__construct($app, $client, $accessToken, $graphVersion);
    }

    /**
     * Upload by chunks - start phase
     *
     * @param string $endpoint
     * @param FacebookFile $file
     * @param Resource|null $fileResource
     * @param integer|null $fileSize
     *
     * @return FacebookTransferChunk
     *
     * @throws FacebookSDKException
     */
    public function start($endpoint, $file, $fileResource = null, $fileSize = null)
    {
        $params = [
            'upload_phase' => 'start',
            'file_size' => $fileSize ?? $file->getSize(),
        ];
        $response = $this->sendUploadRequest($endpoint, $params);
        
        if (null !== $this->chunkSize) {
            $response['end_offset'] = $this->chunkSize;
        }
        
        // phpcs:ignore
        return new FacebookTransferChunk($file, $response['upload_session_id'], $response['video_id'], $response['start_offset'], $response['end_offset'], $fileResource, $fileSize);
    }

    /**
     * Upload by chunks - transfer phase
     *
     * @param string $endpoint
     * @param FacebookTransferChunk $chunk
     * @param boolean $allowToThrow
     * @param Resource|null $fileResource
     * @param integer|null $fileSize
     *
     * @return FacebookTransferChunk
     *
     * @throws FacebookResponseException
     */
    public function transfer($endpoint, $chunk, $allowToThrow = false, $fileResource = null, $fileSize = null)
    {
        $params = [
            'upload_phase' => 'transfer',
            'upload_session_id' => $chunk->getUploadSessionId(),
            'start_offset' => $chunk->getStartOffset(),
            'video_file_chunk' => $chunk->getPartialFile(),
        ];

        try {
            $response = $this->sendUploadRequest($endpoint, $params);
        } catch (FacebookResponseException $e) {
            $preException = $e->getPrevious();
            if ($allowToThrow || !$preException instanceof FacebookResumableUploadException) {
                throw $e;
            }

            if (null !== $preException->getStartOffset() && null !== $preException->getEndOffset()) {
                return new FacebookTransferChunk(
                    $chunk->getFile(),
                    $chunk->getUploadSessionId(),
                    $chunk->getVideoId(),
                    $preException->getStartOffset(),
                    $preException->getEndOffset(),
                    $fileResource
                );
            }

            // Return the same chunk entity so it can be retried.
            return $chunk;
        }
        if ($response['end_offset'] != $response['start_offset'] && null !== $this->chunkSize) {
            $response['end_offset'] = $response['start_offset'] + $this->chunkSize;
        }
        // phpcs:ignore
        return new FacebookTransferChunk($chunk->getFile(), $chunk->getUploadSessionId(), $chunk->getVideoId(), $response['start_offset'], $response['end_offset'], $fileResource, $fileSize);
    }

    /**
     * Helper to make a FacebookRequest and send it.
     *
     * @param string $endpoint The endpoint to POST to.
     * @param array $params The params to send with the request.
     *
     * @return array
     */
    private function sendUploadRequest($endpoint, $params = [])
    {
        $request = new FacebookRequest($this->app, $this->accessToken, 'POST', $endpoint, $params, null, $this->graphVersion);

        return $this->client->sendRequest($request)->getDecodedBody();
    }
}
