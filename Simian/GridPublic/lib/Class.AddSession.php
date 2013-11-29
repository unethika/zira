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

class AddSession implements IGridService
{
    private $UserID;
    private $SessionID;
    private $SecureSessionID;

    public function Execute($db, $params)
    {
        if (isset($params["UserID"]) && UUID::TryParse($params["UserID"], $this->UserID))
        {
            if (isset($params["SessionID"], $params["SecureSessionID"]) &&
                UUID::TryParse($params['SessionID'], $this->SessionID) && UUID::TryParse($params['SecureSessionID'], $this->SecureSessionID))
            {
                // Creating or updating a user session
                $sql = "INSERT INTO Sessions (UserID, SessionID, SecureSessionID, SceneID, ScenePosition, SceneLookAt)
            			VALUES (:UserID, :SessionID, :SecureSessionID, '00000000-0000-0000-0000-000000000000', '<0, 0, 0>', '<0, 0, 0>')
            			ON DUPLICATE KEY UPDATE SessionID=VALUES(SessionID), SecureSessionID=VALUES(SecureSessionID)";
                
                $sth = $db->prepare($sql);
                
                if ($sth->execute(array(':UserID' => $this->UserID, ':SessionID' => $this->SessionID, ':SecureSessionID' => $this->SecureSessionID)))
                {
                    if ($sth->rowCount() > 0)
                    {
                        header("Content-Type: application/json", true);
                        echo sprintf('{ "Success": true, "SessionID": "%s", "SecureSessionID": "%s" }', $this->SessionID, $this->SecureSessionID);
                        exit();
                    }
                    else
                    {
                        log_message('error', "Failed updating the database");
                        
                        header("Content-Type: application/json", true);
                        echo '{ "Message": "Database update failed" }';
                        exit();
                    }
                }
                else
                {
                    log_message('error', sprintf("Error occurred during query: %d %s", $sth->errorCode(), print_r($sth->errorInfo(), true)));
                    log_message('debug', sprintf("Query: %s", $sql));
                    
                    header("Content-Type: application/json", true);
                    echo '{ "Message": "Database query error" }';
                    exit();
                }
            }
            else
            {
                // Creating or fetching a user session
                $this->SessionID = UUID::Random();
                $this->SecureSessionID = UUID::Random();
                
                $sql = "INSERT INTO Sessions (UserID, SessionID, SecureSessionID, SceneID, ScenePosition, SceneLookAt)
            			VALUES (:UserID, :SessionID, :SecureSessionID, '00000000-0000-0000-0000-000000000000', '<0, 0, 0>', '<0, 0, 0>')";
                
                $sth = $db->prepare($sql);
                
                if ($sth->execute(array(':UserID' => $this->UserID, ':SessionID' => $this->SessionID, ':SecureSessionID' => $this->SecureSessionID)))
                {
                    if ($sth->rowCount() > 0)
                    {
                        header("Content-Type: application/json", true);
                        echo sprintf('{ "Success": true, "SessionID": "%s", "SecureSessionID": "%s" }', $this->SessionID, $this->SecureSessionID);
                        exit();
                    }
                    else
                    {
                        log_message('error', "Failed updating the database");
                        
                        header("Content-Type: application/json", true);
                        echo '{ "Message": "Database update failed" }';
                        exit();
                    }
                }
                else
                {
                    $sql = "SELECT SessionID, SecureSessionID FROM Sessions WHERE UserID=:UserID";
                    
                    $sth = $db->prepare($sql);
                    
                    if ($sth->execute(array(':UserID' => $this->UserID)))
                    {
                        if ($sth->rowCount() > 0)
                        {
                            $obj = $sth->fetchObject();
                            
                            header("Content-Type: application/json", true);
                            echo sprintf('{ "Success": true, "SessionID": "%s", "SecureSessionID": "%s" }', $obj->SessionID, $obj->SecureSessionID);
                            exit();
                        }
                        else
                        {
                            log_message('error', "Failed retrieving user session from the database");
                            
                            header("Content-Type: application/json", true);
                            echo '{ "Message": "No user session found" }';
                            exit();
                        }
                    }
                    else
                    {
                        log_message('error', sprintf("Error occurred during query: %d %s", $sth->errorCode(), print_r($sth->errorInfo(), true)));
                        log_message('debug', sprintf("Query: %s", $sql));
                        
                        header("Content-Type: application/json", true);
                        echo '{ "Message": "Database query error" }';
                        exit();
                    }
                }
            }
        }
        else
        {
            header("Content-Type: application/json", true);
            echo '{ "Message": "Invalid parameters" }';
            exit();
        }
    }
}
