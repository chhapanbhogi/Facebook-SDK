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

use Facebook\FileUpload\FacebookTransferChunk as FbTransferChunk;

/**
 * Class FacebookTransferChunk
 *
 * @package Facebook
 */
class FacebookTransferChunk extends FbTransferChunk
{
    /**
     * @var resource
     */
    protected $stream = null;
    /**
     * @var integer
     */
    protected $fileSize = null;

    /**
     * @param FacebookFile $file
     * @param int $uploadSessionId
     * @param int $videoId
     * @param int $startOffset
     * @param int $endOffset
     * @param Resource|null $fileResource
     * @param Resource|null $fileSize
     */
    // phpcs:ignore
    public function __construct(FacebookFile $file, $uploadSessionId, $videoId, $startOffset, $endOffset, $fileResource = null, $fileSize = null)
    {
        $this->stream = $fileResource;
        $this->fileSize = $fileSize;
        parent::__construct($file, $uploadSessionId, $videoId, $startOffset, $endOffset);
    }

    /**
     * Return a FacebookFile entity with partial content.
     *
     * @return FacebookFile
     */
    public function getPartialFile()
    {
        $maxLength = $this->getEndOffset() - $this->getStartOffset();
        // phpcs:ignore
        return new FacebookFile($this->getFile()->getFilePath(), $maxLength, $this->getStartOffset(), $this->stream, $this->fileSize);
    }
}
