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
 * @author     John Hurliman <http://software.intel.com/en-us/blogs/author/john-hurliman/>
 *             Jonathan Freedman <http://twitter.com/otakup0pe>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

define('COMMONPATH', str_replace("\\", "/", realpath(dirname(__FILE__) . '/..') . '/GridCommon/'));
define('BASEPATH', str_replace("\\", "/", realpath(dirname(__FILE__)) . '/'));

require_once(COMMONPATH . 'Config.php');
require_once(COMMONPATH . 'Errors.php');
require_once(COMMONPATH . 'Log.php');
require_once(COMMONPATH . 'Interfaces.php');
require_once(COMMONPATH . 'UUID.php');
require_once(COMMONPATH . 'Vector3.php');
require_once(COMMONPATH . 'Curl.php');
require_once(COMMONPATH . 'SimianGrid.php');
require_once(COMMONPATH . 'Scene.php');
require_once(COMMONPATH . 'SceneLocation.php');
require_once(COMMONPATH . 'Session.php');

if ( !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST' ) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

///////////////////////////////////////////////////////////////////////////////
// Path Functions
///////////////////////////////////////////////////////////////////////////////

if ( isset($_SERVER['PATH_INFO'] ) ) {
    $path_bits = explode('/', $_SERVER['PATH_INFO']);
    if ( count($path_bits) > 0 ) {
        $data = file_get_contents("php://input");
        if ( $path_bits[1] == "foreignagent" ) {
            foreignagent_handler(array_slice($path_bits, 2), $data);
        } else if ( $path_bits[1] == "homeagent" ) {
            homeagent_handler(array_slice($path_bits, 2), $data);
        } else if ( $path_bits[1] == "sg_api" ) {
            if ( $path_bits[2] == "link_remote" ) {
                link_remote_handler($data);
            } else if ( $path_bits[2] == "info_remote" ) {
                info_remote_handler($data);
            } else if ( $path_bits[2] == "refresh_map" ) {
                refresh_map_handler($data);
            }
        }
    }
}

///////////////////////////////////////////////////////////////////////////////
// XML-RPC Server
///////////////////////////////////////////////////////////////////////////////

$xmlrpc_server = xmlrpc_server_create();

// Gatekeeper
xmlrpc_server_register_method($xmlrpc_server, "link_region", "link_region");
xmlrpc_server_register_method($xmlrpc_server, "get_region", "get_region");

//
xmlrpc_server_register_method($xmlrpc_server, "get_home_region", "get_home_region");
xmlrpc_server_register_method($xmlrpc_server, "verify_client", "verify_client");
xmlrpc_server_register_method($xmlrpc_server, "verify_agent", "verify_agent");
xmlrpc_server_register_method($xmlrpc_server, "logout_agent", "logout_agent");
xmlrpc_server_register_method($xmlrpc_server, "agent_is_coming_home", "agent_is_coming_home");
xmlrpc_server_register_method($xmlrpc_server, "get_uui", "get_uui");

// These are for friends, handle later
// status_notification
// get_online_friends
// get_user_info
// locate_user

xmlrpc_server_register_method($xmlrpc_server, "get_uuid", "get_uuid");
xmlrpc_server_register_method($xmlrpc_server, "get_server_urls", "get_server_urls");


$request_xml = file_get_contents("php://input");

log_message('debug', "RECEIVING THIS -> $request_xml");

$response = xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');

header('Content-Type: text/xml');

log_message('debug', "SENDING THIS -> $response");
echo $response;

xmlrpc_server_destroy($xmlrpc_server);
exit();

///////////////////////////////////////////////////////////////////////////////
/// Utilities
///////////////////////////////////////////////////////////////////////////////

function bin2int($str)
{
    $result = '0';

    $n = strlen($str);
    do
    {
        $result = bcadd(bcmul($result, '256'), ord($str{--$n}));
    } while ($n > 0);

    return $result;
}

function int2bin($num)
{
    $result = '';

    do
    {
        $result .= chr(bcmod($num, '256'));
        $num = bcdiv($num, '256');
    } while (bccomp($num, '0'));

    return $result;
}

function bitOr($num1, $num2, $start_pos)
{
    $start_byte = intval($start_pos / 8);
    $start_bit = $start_pos % 8;
    $tmp1 = int2bin($num1);

    $num2 = bcmul($num2, 1 << $start_bit);
    $tmp2 = int2bin($num2);

    if ($start_byte < strlen($tmp1))
    {
        $tmp2 |= substr($tmp1, $start_byte);
        $tmp1 = substr($tmp1, 0, $start_byte) . $tmp2;
    }
    else
    {
        $tmp1 = str_pad($tmp1, $start_byte, "\0") . $tmp2;
    }

    return bin2int($tmp1);
}

function bitShift($num1, $bits)
{
    return bcmul($num1, bcpow(2, $bits));
}

function sendresponse($success,$reason,$yourip = null)
{
    $response = array();
    $response['success'] = $success;
    $response['reason'] = $reason;
    $response['your_ip'] = ($yourip == null ? $_SERVER['REMOTE_ADDR'] : $yourip);
    
    $jresponse = json_encode($response);
    log_message('debug',"[hypergrid] returns $jresponse");

    echo $jresponse;
    exit();
}

function hg_register_user($user_id, $username, $homeuri)
{
    $config =& get_config();

    // only create the user if it doesn't already exist
    if (get_user_by_id($user_id) != null)
        return true;

    log_message('info',"[hypergrid] creating new hypergrid user: $username");
    if (create_user($user_id, $username, '__' . $username))
    {
        set_user_data($user_id,'LocalToGrid','false');
        set_user_data($user_id,'HomeURI',$homeuri);
        return true;
    }

    return false;
}

function hg_get_region($hguri, $id)
{
    $info_request = xmlrpc_encode_request('get_region', array('region_uuid' => $id));
    $curl = new Curl();
    $response_raw = $curl->simple_post($hguri, $info_request);

    $response = xmlrpc_decode($response_raw);

    $success = $response['result'];
    
    if ( $success ) {
        if ( isset($response['server_uri']) ) {
            $serveruri = $response['server_uri'];
        } else {
            $serveruri = "http://" . $response['hostname'] . ':' . $response['http_port'] . '/';
        }
        return array(
            'uuid' => $response['uuid'],
            'x' => $response['x'],
            'y' => $response['y'],
            'region_name' => $response['region_name'],
            'hostname' => $response['hostname'],
            'internal_port' => $response['internal_port'],
            'server_uri' => $serveruri
        );
    } else {
        return null;
    }
}

function hg_refresh_map($sceneID)
{
    $config =& get_config();
    
    $scene = lookup_scene_by_id($sceneID);
    
    if ( $scene != null ) {
        $x = $scene->MinPosition->X / 256;
        $y = $scene->MinPosition->Y / 256;
        if ( isset($scene->ExtraData['HyperGrid']) ) {
            if ( refresh_map_tile($x, $y, $scene->ExtraData['RegionImage']) ) {
                echo("updated map");
            } else  {
                echo("unable to update map");
            }
        }
    }
    exit();
}

function hg_link_region($sceneID, $region_name, $external_name, $x, $y, $regionImage, $hgurl)
{
    log_message('info', "[hypergrid] hg_link_region called");

    $config =& get_config();
    $gridService = $config['grid_service'];

    $minpos = '<' . $x * 256 . "," . $y * 256 . ",0>";
    $maxpos = '<' . (($x * 256) + 256) . "," . (($y * 256) + 256) . ",0>";

    $xd = json_encode(array(
        'HyperGrid' => true,
        'RegionImage' => $regionImage,
        'ExternalName' => $external_name
    ));
    
    $data = array(
        'RequestMethod' => 'AddScene',
        'SceneID' => $sceneID,
        'Name' => $region_name,
        'MinPosition' => $minpos,
        'MaxPosition' => $maxpos,
        'ExtraData' => $xd,
        'Address' => $hgurl,
        'Enabled' => true
    );

    $response = webservice_post($gridService, $data);
    
    if ( $response['Success'] ) {
        refresh_map_tile($x, $y, $regionImage);
    }

    return $response['Success'];
}

//taking easy way out... we get the osdmap more or less intact.....
function hg_login($gatekeeper_uri, $userid, $raw_osd, &$yourip)
{
    if (!ends_with($gatekeeper_uri, '/'))
        $gatekeeper_uri .= '/';

    $uri = $gatekeeper_uri . "foreignagent/" . $userid . "/";
    log_message('debug',"[hypergrid] login $userid to $uri with parameters: " . $raw_osd);

    $success = false;
    $options = array();
    $options['HTTPHEADER'] = array('Content-Type: application/json');

    $curl = new Curl();
    $response_raw = $curl->simple_post($uri, $raw_osd, $options);
    if (empty($response_raw))
    {
        log_message('warn', '[hypergrid] hg_login failed, no response from server');
        return false;
    }

    log_message('debug', 'foreignagent returned ' . $response_raw);

    $response = json_decode($response_raw, TRUE);
    if ( isset($response['success']) && $response['success'] )
    {
        log_message('debug','[hypergrid] hg_login succeeded');
        $yourip = empty($response['your_ip']) ? '0.0.0.0' : $response['your_ip'];
        return true;
    }

    $yourip = '0.0.0.0';
    log_message('warn', '[hypergrid] hg_login failed with reason ' . (isset($response['reason']) ? $response['reason'] : 'no reason given'));
    return false;
}

///////////////////////////////////////////////////////////////////////////////
// GateKeeper Service
///////////////////////////////////////////////////////////////////////////////

function link_region($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $config =& get_config();

    $req = $params[0];

    if ( isset($req['region_name']) && strlen($req['region_name']) > 0 ) {
        $region_name = $req['region_name'];
        log_message('debug', "Using specified region name $region_name");
    } else {
        $region_name = $config['hypergrid_default_region'];
        log_message('debug', "No region name specified - using $region_name");
    }
    
    $scene = lookup_scene_by_name($region_name);
    
    $response = array();

    if ( $scene == null ) {
        log_message('warn', "Unable to link to unknown region $region_name - no scene found");
        $response['result'] = 'false';
    } else {
        $response['result'] = 'true';
        $response['uuid'] = $scene->SceneID;
        
        // Yay for 64-bit integer bitmath in PHP
        $x = $scene->MinPosition->X;
        $y = $scene->MinPosition->Y;
        $handle = bitShift($x, 32);
        $handle = bitOr($handle, (string)$y, 0);
        $response['handle'] = (string)$handle;
        
        $response['region_image'] = $config['map_service'] . 'map-1-' . ($x / 256) . '-' . ($y / 256) . '-objects.jpg';
        $response['server_uri'] = $scene->Address;
        $response['external_name'] = $scene->Name;
        log_message('debug', "Succesfully linked to $region_name@" . $scene->Address);
    }
    
    return $response;
}

function get_region($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");
    
    $req = $params[0];
    $regionid = $req['region_uuid'];
    
    $scene = lookup_scene_by_id($regionid);
    
    $response = array();
    $config =& get_config();
    if ( $scene == null ) {
        $response['result'] = "false";
    } else {
        $response['result'] = "true";
        $response['uuid'] = $scene->SceneID;
        $response['x'] = (string) $scene->MinPosition->X;
        $response['y'] = (string) $scene->MinPosition->Y;
        $response['region_name'] = $scene->Name;
        $response['server_uri'] = $scene->Address;
        $response['hostname'] = $scene->ExtraData['ExternalAddress'];
        $response['internal_port'] = (string) $scene->ExtraData['ExternalPort'];
    }

    return $response;
}

///////////////////////////////////////////////////////////////////////////////
// UserAgent Service
///////////////////////////////////////////////////////////////////////////////

function get_home_region($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    
    $req = $params[0];
    $userID = $req['userID'];
    
    $response = array();
    
    log_message('info', "get_home_region called with UserID $userID");
    
    // Fetch the user
    $user = get_user_by_id($userID);
    if (empty($user))
    {
        log_message('warn', "Unknown UserID $userID");
        $response['result'] = 'false';
        return $response;
    }
    
    $homeLocation = null;
    
    if (isset($user['HomeLocation']))
        $homeLocation = SceneLocation::fromOSD($user['HomeLocation']);
    
    log_message('debug', "User retrieval success for $userID...");
    
    $scene = null;
    $position = null;
    $lookat = null;
    
    // If the user's home is set, try to grab info for that scene
    if (isset($homeLocation))
    {
        log_message('debug', sprintf("Looking up scene '%s'", $homeLocation->SceneID));
        $scene = lookup_scene_by_id($homeLocation->SceneID);
        
        if (isset($scene))
        {
            $position = $homeLocation->Position;
            $lookat = $homeLocation->LookAt;
        }
    }
    
    // No home set, last resort lookup for *any* scene in the grid
    if (!isset($scene))
    {
        $position = Vector3::Zero();
        log_message('debug', "Looking up scene closest to '$position'");
        $scene = lookup_scene_by_position($position, true);
        
        if (isset($scene))
        {
            $position = new Vector3(
                (($scene->MinPosition->X + $scene->MaxPosition->X) / 2) - $scene->MinPosition->X,
                (($scene->MinPosition->Y + $scene->MaxPosition->Y) / 2) - $scene->MinPosition->Y,
                25);
            $lookat = new Vector3(1, 0, 0);
        }
    }
    
    if (isset($scene))
    {
	$simurl = $scene->Address;

	$simparts = parse_url($simurl);

        $response['result'] = 'true';
        $response['uuid'] = $scene->SceneID;
        //$response['x'] = (string)($scene->MinPosition->X / 256);
        //$response['y'] = (string)($scene->MinPosition->Y / 256);
        $response['x'] = (string)$scene->MinPosition->X;
        $response['y'] = (string)$scene->MinPosition->Y;
        $response['region_name'] = $scene->Name;
	$response['server_uri'] = $simurl;
        $response['hostname'] = $scene->ExtraData['ExternalAddress'];
	// $response['http_port'] = (string)$scene->ExtraData['ExternalPort'];
        $response['http_port'] = (string)$simparts['port'];
        $response['internal_port'] = (string)$scene->ExtraData['InternalPort'];
        $response['position'] = (string)$position;
        $response['lookAt'] = (string)$lookat;
        
        log_message('debug', "Returning successful home lookup for $userID");
    }
    else
    {
        $response['result'] = 'false';
        log_message('warn', "Failed to find a valid home scene for $userID, returning failure");
    }
    
    return $response;
}

function verify_client($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    
    $req = $params[0];
    $sessionID = $req['sessionID'];
    $token = $req['token'];
    
    log_message('info', "[hypergrid] verify_client called with SessionID $sessionID and Token $token");
    
    $response['result'] = 'true';
    return $response;
}

function verify_agent($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    
    $req = $params[0];
    $sessionID = $req['sessionID'];
    $token = $req['token'];
    
    log_message('info', "[hypergrid] verify_agent called with SessionID $sessionID and Token $token");
    
    $response['result'] = 'true';
    return $response;
}

function logout_agent($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    $response["blah"] = 'blah';
    return $response;
}

function agent_is_coming_home($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    $response["blah"] = 'blah';
    return $response;
}

function get_uui($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    $req = $params[0];
    $userID = $req['userID'];
    $targetID = $req['targetUserID'];

    return $response;
}

function get_uuid($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    $req = $params[0];
    $fname = $req['first'];
    $lname = $req['last'];

    log_message('info', "[hypergrid] get_uuid with $fname and $lname");
    
    $user = get_user_by_name("$fname $lname");
    $response['UUID'] = $user['UserID'];
    return $response;
}

function get_server_urls($method_name, $params, $user_data)
{
    log_message('info', "[hypergrid] $method_name called");

    $response = array();
    $req = $params[0];

    
    $userID = $req['userID'];

    // SRV_InventoryServerURI
    // SRV_AssetServerURI
    // SRV_ProfileServerURI
    // SRV_FriendsServerURI
    // SRV_IMServerURI
    $response[''] = "";

    return $response;
}

///////////////////////////////////////////////////////////////////////////////
/// Home and ForeignAgent Handlers
///////////////////////////////////////////////////////////////////////////////

function foreignagent_handler($path_tail, $data)
{
    log_message('info', "[hypergrid] foreignagent_handler called");

    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] == 'application/x-gzip')
    {
        log_message('info',"[hypergrid] handling compressed foreign agent data");
        $data = gzdecode($data);
    }
    
    $config =& get_config();

    $userid = $path_tail[0];
    log_message('info', "foreign_agent called for $userid with $data");
    
    $osd = decode_recursive_json($data);
    if ($osd == null)
    {
        log_message('error',sprintf('[hypergrid] failed to decode foreignagent json string %s',$data));
        sendresponse(false,'failed to decode foreignagent string');
    }

    $dest_x = $osd['destination_x'];
    $dest_y = $osd['destination_y'];
    
    if ( $dest_x == null ) {
        $dest_x = 0;
    }
    if ( $dest_y == null ) {
        $dest_y = 0;
    }
    
    $caps_path = $osd['caps_path'];
    $username = $osd['first_name'] . ' ' . $osd['last_name'];
    $circuit_code = $osd['circuit_code'];
    $session_id = $osd['session_id'];
    $secure_session_id = $osd['secure_session_id'];
    $service_session_id = $osd['service_session_id'];
    $start_pos = $osd['start_pos'];
    $appearance = $osd['packed_appearance'];

    //$service_urls['HomeURI'] = $osd['service_urls'][1];
    //$service_urls['GatekeeperURI'] = $osd['service_urls'][3];
    //$service_urls['InventoryServerURI'] = $osd['service_urls'][5];
    //$service_urls['AssetServerURI'] = $osd['service_urls'][7];
    if ( isset($osd['client_ip']) ) {
        $client_ip = $osd['client_ip'];
    } else {
        log_message('info','[hypergrid] no client ip specified in foreignagent request');
        $client_ip = null;
    }

    if (empty($osd['destination_uuid']))
    {
        header("HTTP/1.1 400 Bad Request");
        echo "missing destination_uuid";
        exit();
    }
    
    $dest_uuid = $osd['destination_uuid'];
    $scene = lookup_scene_by_id($dest_uuid);
    if ($scene == null)
    {
        header("HTTP/1.1 400 Bad Request");
        echo "invalid destination uuid";
        exit();
    }
    $dest_name = $scene->Name;
    
    $homeuri = $osd['serviceurls']['HomeURI'];
    
    // $username = $osd['first_name'] . ' ' . $osd['last_name'] . '@' . $service_urls['HomeURI'];
    $username = $osd['first_name'] . ' ' . $osd['last_name'];
    log_message('info',"[hypergrid] check user name $username with homeuri $homeuri");
    if ($homeuri != $config['hypergrid_uri'])
    {
        $username =  $username . '@' . $homeuri;
        hg_register_user($userid, $username, $homeuri);
    }

    $extradata = null;
    if ($client_ip != null)
    {
        $extradata = array('ClientIP' => $client_ip);
    }
    
    log_message('info',"[hypergrid] create session for $username");
    create_session($userid, $session_id, $secure_session_id, $extradata);
    
    $result = create_opensim_presence_full($scene->Address, $dest_name, $dest_uuid, $dest_x, $dest_y,
					   $userid, $circuit_code, $username, $appearance, $session_id, $secure_session_id, $start_pos,
					   $caps_path, $client_ip, $osd['serviceurls'], 1073741824, $service_session_id,
					   $seedCaps);

    sendresponse($result,'no reason given');
}

function homeagent_handler($path_tail, $data)
{
    log_message('info', "[hypergrid] homeagent_handler called");
    
    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] == 'application/x-gzip')
    {
        log_message('info',"[hypergrid] handling compressed foreign agent data");
        $data = gzdecode($data);
    }

    $userid = $path_tail[0];
    log_message('info', "homeagent_handler called for $userid with $data");
    
    $osd = decode_recursive_json($data);
    if ($osd == null)
    {
        log_message('error',sprintf('[hypergrid] failed to decode foreignagent json string %s',$data));
        sendresponse(false,'failed to decode foreignagent string');
    }
    
    $gatekeeper_uri = $osd['gatekeeper_serveruri'];
    
    if (! isset($osd['destination_x']))
        $osd['destination_x'] = 128;

    if (! isset($osd['destination_y']))
        $osd['destination_y'] = 128;

    if (! isset($osd['client_ip']) )
    {
        $session = get_session($userid);
        if (! isset($session['ExtraData']['ClientIP']))
        {
            log_message('warn',"[hypergrid] no client ip found in session, this is going to fail");
            sendresponse(false,'no client ip found in the session');
        }
        
        $osd['client_ip'] = $session['ExtraData']['ClientIP'];
    }

    if (! isset($osd['service_session_id'] ) )
    {
        log_message('debug','missing service_session_id, generating a new one');
        $osd['service_session_id'] = $gatekeeper_uri . ';' . UUID::Random();
    }
    
    /* $dest_uuid = $osd['destination_uuid']; */
    /* $caps_path = $osd['caps_path']; */
    /* $username = $osd['first_name'] . ' ' . $osd['last_name']; */
    /* $circuit_code = $osd['circuit_code']; */
    /* $session_id = $osd['session_id']; */
    /* $secure_session_id = $osd['secure_session_id']; */
    /* $start_pos = $osd['start_pos']; */
    /* $appearance = $osd['packed_appearance']; */
    /* $server_uri = $osd['destination_serveruri']; */

    $data = json_encode($osd);
    log_message('info',"[hypergrid] login to region with $data");

    $result = array();

    if ( hg_login($gatekeeper_uri, $userid, $data, $yourip) )
    {
        $result['success'] = true;
        $result['reason'] = "success";
        $result['your_ip'] = $yourip;
        //echo '{"success": true, "reason": "success"}';
    } else {
        $result['success'] = false;
        $result['reason'] = "hypergrid login failed";
        $result['your_ip'] = $yourip;
        // echo '{"success": false, "reason": "hypergrid login failed"}';
    }

    $jresult = json_encode($result);
    log_message('info',"[hypergrid] homeagent returns $jresult");
    
    echo $jresult;
    exit();
}

///////////////////////////////////////////////////////////////////////////////
/// Handlers
///////////////////////////////////////////////////////////////////////////////

function link_remote_handler($data)
{
    $x = $_POST['x'];
    $y = $_POST['y'];
    $hguri = $_POST['hg_uri'];
    $region_name = $_POST['region_name'];
    
    $link_request = xmlrpc_encode_request('link_region', array('region_name' => $region_name));
    $curl = new Curl();
    $response_raw = $curl->simple_post($hguri, $link_request);
    
    $response = xmlrpc_decode($response_raw);
    
    $success = $response['result'];
    if ( $success ) {
        $uuid = $response['uuid'];
        $external_name = $response['external_name'];
        $region_image = $response['region_image'];
        if ( hg_link_region($uuid, $region_name, $external_name, $x, $y, $region_image, $hguri) ) {
            $success = true;
        }
    } else {
        log_message('debug', "result was false didn't link!");
    }
    echo '{"success": '. $success . '}';
    exit();
}

function info_remote_handler($data)
{
    $sceneid = $_POST['sceneid'];
    $hguri = $_POST['hguri'];
    
    $result = hg_get_region($hguri, $sceneid);
    if ( $result == null ) {
        echo '{"success":false}';
    } else {
        $result['Success'] = true;
        echo json_encode($result);
    }
    exit();
}

function refresh_map_handler($data)
{
    $scene_id = $_POST['sceneid'];
    hg_refresh_map($scene_id);
    exit();
}
