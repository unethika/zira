<?php
/** Simian grid services
 *
 * PHP version 5
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    SimianGrid
 * @author     Jim Radford <http://www.jimradford.com/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */
require_once(COMMONPATH . 'SQLAssets.php');
//require_once(COMMONPATH . 'MongoAssets.php');
//require_once(COMMONPATH . 'FSAssets.php');

class GetAsset implements IGridService
{
    public function Execute($db, $asset)
    {
        $headrequest = (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE);
        
        $assets = new SQLAssets($db);
        //$assets = new MongoAssets($db);
        //$assets = new FSAssets($db);
        
        if ($headrequest)
        {
            $asset = $assets->GetAssetMetadata($asset->ID);
        }
        else
        {
            $asset = $assets->GetAsset($asset->ID);
        }
        
        if ($asset)
        {
            // TODO: Enforce this once we support one or more auth methods
            $asset->Public = true;
            
            if ($asset->Public)
            {
                header("ETag: " . $asset->SHA256, true);
                header("Last-Modified: " . gmdate(DATE_RFC850, $asset->CreationDate), true);
                header("X-Asset-Creator-Id: " . $asset->CreatorID, true);
                header("Content-Type: " . $asset->ContentType, true);
                header("Content-Length: " . $asset->ContentLength, true);
                
                if (!$headrequest)
                    echo $asset->Data;
                
                exit();
            }
            else
            {
                log_message('debug', "Access forbidden to private asset " . $asset->ID);
                
                header("HTTP/1.1 403 Forbidden");
                echo 'Access forbidden';
                exit();
            }
        }
        else
        {
            header("HTTP/1.1 404 Not Found");
            echo 'Asset not found';
            exit();
        }
    }
}
