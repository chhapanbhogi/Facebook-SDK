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

namespace FacebookExtended\FileUpload;

use Facebook\FileUpload\FacebookVideo as FbVideo;

/**
 * Extends the base FacebookVideo class from Facebook Graph SDK package
 *
 * Currently the original FacebookVideo does not support remote file size. This
 * class gets the remote file size from headers or using curl if not available in headers.
 *
 * @category FacebookSDK
 * @package  MuviFacebook
 */

class FacebookVideo extends FbVideo
{
    /**
     * Return the size of the file.
     *
     * @return int
     */
    public function getSize()
    {
        return ($this->isRemoteFile($this->path)) ?
                $this->getRemoteFileSize() :
                filesize($this->path);
    }

    /**
     * Return the size of the remote file.
     *
     * @return int
     */
    public function getRemoteFileSize()
    {
        $size = $this->getRemoteFileSizeByHeader();
        if ($size === -1) {
            $size = $this->getRemoteFileSizeByCurl();
        }
        
        return $size;
    }

    /**
     * Return the size of the remote file.
     *
     * @return int
     * will return -1 if content length is not available in the header
     */
    public function getRemoteFileSizeByHeader()
    {
        $head = array_change_key_case(get_headers($this->path, 1));
        return $head['content-length'] ?? -1;
    }

    /**
     * Return the size of the remote file.
     *
     * @return int
     */
    public function getRemoteFileSizeByCurl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->path);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        return $size;
    }
}
