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

class AuthorizeIdentity implements IGridService
{
    public function Execute($db, $params)
    {
        if (isset($params["Identifier"], $params["Credential"], $params["Type"]))
        {
            // Right now we're going to assume "Identifier" == "Name".
            // TODO: support other identifiers.

            // SimianGrid uses "Name", but ROBUST uses "FirstName" and
            // "LastName", so split "Name"...
            $name = explode(' ', $params["Identifier"]);

            // ROBUST doesn't use $1$salt$... format so strip it...
            $credential = preg_replace('/^.*\$/', '', $params["Credential"]);

            // Handle cases where Name has more or less than FirstName
            // and LastName...
            if (count($name) != 2) {
                header("Content-Type: application/json", true);
                echo '{ "Message": "No matching user found" }';
                exit();
            }

            $first_name = $name[0];
            $last_name = $name[1];

            // HACK: Special handling for salted md5hash passwords
            if ($params["Type"] == 'md5hash')
            {
                $sql = "SELECT UserAccounts.PrincipalID, UserAccounts.FirstName, UserAccounts.LastName,
                        auth.passwordHash, auth.passwordSalt FROM UserAccounts, auth 
                        WHERE UserAccounts.FirstName=:FirstName AND UserAccounts.LastName=:LastName
                        AND UserAccounts.PrincipalID=auth.UUID";
                $sth = $db->prepare($sql);

                if ($sth->execute(array(':FirstName' => $first_name, ':LastName' => $last_name)) && $sth->rowCount() > 0)
                {
                    $obj = $sth->fetchObject();
                    $finalhash = $obj->passwordHash;
                    $salt = $obj->passwordSalt;
                    
                        if (md5($credential . ':' . $salt) == $finalhash)
                        {
                            header("Content-Type: application/json", true);
                            echo '{ "Success":true, "UserID":"' .  $obj->PrincipalID . '" }';
                            exit();
                        }
                        else
                        {
                            log_message('info', 'Authentication failed for identifier ' . $params["Identifier"] . ', type md5hash (salted)');
                            
                            header("Content-Type: application/json", true);
                            echo '{ "Message": "Missing identity or invalid credentials" }';
                            exit();
                        }
                }
            }

            // TODO: fix below

            $sql = "SELECT UserID FROM Identities WHERE Identifier=:Identifier AND Credential=:Credential AND Type=:Type and Enabled=true";
            
            $sth = $db->prepare($sql);
            
            if ($sth->execute(array(':Identifier' => $params["Identifier"], ':Credential' => $params["Credential"], ':Type' => $params["Type"])))
            {
                if ($sth->rowCount() > 0)
                {
                    $obj = $sth->fetchObject();
                    
                    header("Content-Type: application/json", true);
                    echo '{ "Success":true, "UserID":"' . $obj->UserID . '" }';
                    exit();
                }
                else
                {
                    log_message('info', 'Authentication failed for identifier ' . $params["Identifier"] . ', type ' . $params["Type"]);
                    
                    header("Content-Type: application/json", true);
                    echo '{ "Message": "Missing identity or invalid credentials" }';
                    exit();
                }
            }
            else
            {
                log_message('error', sprintf("Error occurred during query: %d %s", $sth->errorCode(), print_r($sth->errorInfo(), true)));
                
                header("Content-Type: application/json", true);
                echo '{ "Message": "Database query error" }';
                exit();
            }
        }
        else
        {
            header("Content-Type: application/json", true);
            echo '{ "Message": "Missing or invalid parameters" }';
            exit();
        }
    }
}
