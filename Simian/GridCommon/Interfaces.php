<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Simian grid services
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

interface IGridService
{
    public function Execute($db, $request);
}

interface IPublicService
{
    public function Execute($request);
}

interface IOSD
{
    public function toOSD();
    public static function fromOSD($strOsd);
}

class Asset
{
    public $ID;
    public $CreatorID;
    public $ContentLength;
    public $ContentType;
    public $CreationDate;
    public $SHA256;
    public $Temporary;
    public $Public;
    public $Data;
}

class MapTile
{
    public $X;
    public $Y;
    public $Data;
}

class Inventory
{
    public $ID;
    public $ParentID;
    public $OwnerID;
    public $Name;
    public $ContentType;
    public $ExtraData;
    public $CreationDate;
    public $Type;
}

interface IAvatarInventoryFolder
{
    public function Folders();
    public function Items();
    public function Appearance();
    public function Attachments();
    public function Configure();
}

class AvatarInventoryFolderFactory
{
    public static function Create($type,$name,$userid)
    {
        if (class_exists($type))
            return new $type($name,$userid);
    
        $classFile = BASEPATH . 'avatar/Avatar.' . $type . '.php';
        if (file_exists($classFile))
        {
            include_once $classFile;
            return new $type($name,$userid);
        }
        else
        {
            log_message('warn', "requested avatar $type not found, using default");
    
            $type = "DefaultAvatar";
            $classFile = BASEPATH . 'avatar/Avatar.DefaultAvatar.php';

            include_once $classFile;
            return new $type($name,$userid);
        }
    }
}
