<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/database_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowEnd() {
		global $app, $conf, $interfaceConf;

		/*
		 * If the names are restricted -> remove the restriction, so that the
		 * data can be edited
		 */
		
		//* Get the database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbuser_prefix = $app->tools_sites->replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);
		
        if ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.company_name, client.contact_name, client.client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
            
            // Fill the client select field
            $sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(client.company_name,' :: ',client.contact_name) as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY sys_group.name";
            $records = $app->db->queryAllRecords($sql);
            $tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
            $client_select = '<option value="'.$tmp['groupid'].'">'.$client['company_name'].' :: '.$client['contact_name'].'</option>';
            //$tmp_data_record = $app->tform->getDataRecord($this->id);
            if(is_array($records)) {
                foreach( $records as $rec) {
                    $selected = @(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
                    $client_select .= "<option value='$rec[groupid]' $selected>$rec[name] :: $rec[contactname]</option>\r\n";
                }
            }
            $app->tpl->setVar("client_group_id",$client_select);
        } elseif($_SESSION["s"]["user"]["typ"] == 'admin') {
			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(client.company_name,' :: ',client.contact_name) as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					//$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name] :: $client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);
        }
        
        
		if ($this->dataRecord['database_user'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("database_user", str_replace($dbuser_prefix , '', $this->dataRecord['database_user']));
		}
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("database_user_prefix", $global_config['dbuser_prefix']);
		} else {
			$app->tpl->setVar("database_user_prefix", $dbuser_prefix);
		}
		
		parent::onShowEnd();
	}
    
    function onSubmit() {
        global $app;
        
        if($_SESSION['s']['user']['typ'] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
        
        parent::onSubmit();
    }

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		//* Get the database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbuser_prefix = $app->tools_sites->replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);

		//* Database username shall not be empty
		if($this->dataRecord['database_user'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_user_error_empty"].'<br />';

		if(strlen($dbuser_prefix . $this->dataRecord['database_user']) > 16) $app->tform->errorMessage .= str_replace('{user}',$dbuser_prefix . $this->dataRecord['database_user'],$app->tform->wordbook["database_user_error_len"]).'<br />';
		
		//* Check database user against blacklist
		$dbuser_blacklist = array($conf['db_user'],'mysql','root');
		if(in_array($dbuser_prefix . $this->dataRecord['database_user'],$dbuser_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database user not allowed.').'<br />';
		}
		
		if ($app->tform->errorMessage == ''){
			/* restrict the names if there is no error */
            /* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
			$this->dataRecord['database_user'] = substr($dbuser_prefix . $this->dataRecord['database_user'], 0, 16);
		}
		
        $this->dataRecord['server_id'] = $conf['server_id'];
        $this->oldDataRecord = $app->db->queryOneRecord("SELECT * FROM web_database_user WHERE database_user_id = '".$this->id."'");
        
		parent::onBeforeUpdate();
	}

	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;
		
		//* Database username shall not be empty
		if($this->dataRecord['database_user'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_user_error_empty"].'<br />';

		//* Get the database name and database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbuser_prefix = $app->tools_sites->replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);
		
		if(strlen($dbuser_prefix . $this->dataRecord['database_user']) > 16) $app->tform->errorMessage .= str_replace('{user}',$dbuser_prefix . $this->dataRecord['database_user'],$app->tform->wordbook["database_user_error_len"]).'<br />';
		
		//* Check database user against blacklist
		$dbuser_blacklist = array($conf['db_user'],'mysql','root');
		if(is_array($dbuser_blacklist) && in_array($dbuser_prefix . $this->dataRecord['database_user'],$dbuser_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database user not allowed.').'<br />';
		}

		/* restrict the names */
        /* crop user names if they are too long -> mysql: user: 16 chars / db: 64 chars */
		if ($app->tform->errorMessage == ''){
			$this->dataRecord['database_user'] = substr($dbuser_prefix . $this->dataRecord['database_user'], 0, 16);
		}
		
        $this->dataRecord['server_id'] = $conf['server_id'];
        
		parent::onBeforeInsert();
	}

	function onAfterInsert() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_user_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_user_id = ".$this->id);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_user_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_user_id = ".$this->id);
		}
        
        $records = $app->db->queryAllRecords("SELECT DISTINCT server_id FROM web_database WHERE database_user_id = '".$app->functions->intval($this->id)."' UNION SELECT DISTINCT server_id FROM web_database WHERE database_ro_user_id = '".$app->functions->intval($this->id)."'");
        foreach($records as $rec) {
            $new_rec = $this->dataRecord;
            $new_rec['server_id'] = $rec['server_id'];
            $app->db->datalogSave('web_database_user', 'UPDATE', 'database_user_id', $this->id, $this->oldDataRecord, $new_rec);
        }
        unset($new_rec);
	}

}

$page = new page_action;
$page->onLoad();

?>