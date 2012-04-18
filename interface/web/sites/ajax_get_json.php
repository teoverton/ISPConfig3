<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses('getconf');

$server_id = intval($_GET["server_id"]);
$web_id = intval($_GET["web_id"]);
$type = $_GET["type"];

//if($_SESSION["s"]["user"]["typ"] == 'admin') {

	if($type == 'getservertype'){
		$json = '{"servertype":"';
		$server_type = 'apache';
		$web_config = $app->getconf->get_server_config($server_id, 'web');
		if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
		$json .= $server_type;
		unset($webconfig);
		$json .= '"}';
	}
	
	if($type == 'getserverid'){
		$json = '{"serverid":"';
		$sql = "SELECT server_id FROM web_domain WHERE domain_id = $web_id";
		$server = $app->db->queryOneRecord($sql);
		$json .= $server['server_id'];
		unset($server);
		$json .= '"}';
	}
	
	if($type == 'getphpfastcgi'){
		$json = '{';
		
		$server_type = 'apache';
		$web_config = $app->getconf->get_server_config($server_id, 'web');
		if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
		if($server_type == 'nginx'){
			$sql = "SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = $server_id";
		} else {
			$sql = "SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = $server_id";
		}
		$php_records = $app->db->queryAllRecords($sql);
		$php_select = "";
		if(is_array($php_records) && !empty($php_records)) {
			foreach( $php_records as $php_record) {
				if($server_type == 'nginx'){
					$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
				} else {
					$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
				}
				$json .= '"'.$php_version.'": "'.$php_record['name'].'",';
			}
		}
		unset($php_records);
		if(substr($json,-1) == ',') $json = substr($json,0,-1);
		$json .= '}';
	}
	
	if($type == 'getphptype'){
		$json = '{"phptype":"';
		$sql = "SELECT php FROM web_domain WHERE domain_id = $web_id";
		$php = $app->db->queryOneRecord($sql);
		$json .= $php['php'];
		unset($php);
		$json .= '"}';
	}

//}

echo $json;
?>