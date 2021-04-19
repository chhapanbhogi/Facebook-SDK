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

use Facebook\Exceptions\FacebookSDKException;
use Facebook\FileUpload\FacebookFile as FbFile;
use FacebookExtended\Facebook;

/**
 * Class FacebookFile
 *
 * @package Facebook
 */
class FacebookFile extends FbFile
{
    /**
     * @var int The maximum bytes to read. Defaults to -1 (read all the remaining buffer).
     */
    private $maxLength;

    /**
     * @var int Seek to the specified offset before reading.
     * If this number is negative, no seeking will occur and reading will start from the current position.
     */
    private $offset;

    /**
     * @var resource The stream pointing to the file.
     */
    protected $stream;

    /**
     * @var integer|null The stream pointing to the file.
     */
    protected $fileSize;

    /**
     * Creates a new FacebookFile entity.
     *
     * @param string $filePath
     * @param int $maxLength
     * @param int $offset
     * @param Resource|null $stream
     *
     * @throws FacebookSDKException
     */
    public function __construct($filePath, $maxLength = -1, $offset = -1, $stream = null, $fileSize = null)
    {
        $this->stream    = $stream;
        $this->fileSize  = $fileSize;
        $this->maxLength = $maxLength;
        $this->offset    = $offset;
        
        parent::__construct($filePath, $maxLength, $offset);
    }

    /**
     * Opens a stream for the file.
     *
     * @throws FacebookSDKException
     */
    public function open()
    {
        if (!$this->isRemoteFile($this->path) && !is_readable($this->path)) {
            throw new FacebookSDKException('Failed to create FacebookFile entity. Unable to read resource: ' . $this->path . '.');
        }
        
        if (!is_resource($this->stream)) {
            $this->stream = fopen($this->path, 'r');
        }
        
        if (!$this->stream) {
            throw new FacebookSDKException('Failed to create FacebookFile entity. Unable to open resource: ' . $this->path . '.');
        }
    }

    /**
     * @param integer|null $fileSize
     *
     * Return the contents of the file.
     *
     * @return string
     */
    public function getContents()
    {
        if (null !== $this->fileSize && ($this->maxLength + $this->offset) > $this->fileSize) {
            $this->maxLength = $this->fileSize - $this->offset;
        }
        
        return stream_get_contents($this->stream, $this->maxLength, $this->offset);
    }

    /**
     * Closes the stream when destructed.
     */
    public function __destruct()
    {
        //$this->close();
    }

    /**
     * Returns true if the path to the file is remote.
     *
     * @param string $pathToFile
     *
     * @return boolean
     */
    protected function isRemoteFile($pathToFile)
    {
        return preg_match('/^(https?|ftp):\/\/.*/', $pathToFile) === 1;
    }

    /**
     * Returns stream resource.
     *
     * @param string $pathToFile
     *
     * @return boolean
     */
    public function getFileResource()
    {
        return $this->stream;
    }
}
