<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if (!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}
include_once libfile('function/cache');
include_once libfile('function/organization');
$operation = empty($_GET['operation']) ? 'basic' : trim($_GET['operation']);
$setting = C::t('setting') -> fetch_all(null);
$checkLanguage = checkLanguage();
if ($setting['thumbsize']) {
	$setting['thumbsize'] = unserialize($setting['thumbsize']);
	foreach ($setting['thumbsize'] as $key => $value) {
		$value['width'] = intval($value['width']);
		if (!$value['width']) {
			$value['width'] = ($key == 'samll' ? 256 : ($key == 'middle' ? 800 : 1440));
		}
		$value['height'] = intval($value['height']);
		if (!$value['height']) {
			$value['height'] = ($key == 'samll' ? 256 : ($key == 'middle' ? 600 : 900));
		}
		$setting['thumbsize'][$key] = $value;
	}
}
if (!submitcheck('settingsubmit')) {
	if ($operation == 'basic') {
		$navtitle = lang('members_verify_base');
		$spacesize = DB::result_first("select maxspacesize from " . DB::table('usergroup_field') . " where groupid='9'");
		include_once  libfile('function/organization');

		if ($setting['defaultdepartment']) {
			$patharr = getPathByOrgid($setting['defaultdepartment']);
			$defaultdepartment = implode(' - ', array_reverse($patharr));

		}
		if (empty($defaultdepartment)) {
			$defaultdepartment = lang('no_join_agency_department');
			$setting['defaultdepartment'] = 'other';
		}
		//$orgtree=getDepartmentOption(0);
	} elseif ($operation == 'qywechat') {
		if ($setting['synorgid']) {
			$patharr = getPathByOrgid($setting['synorgid']);
			$syndepartment = implode(' - ', array_reverse($patharr));

		}
		if (empty($syndepartment)) {
			$syndepartment = lang('all_username');
			$setting['syndepartment'] = '0';
		}
	} elseif ($operation == 'desktop') {
		if ($setting['desktop_default'] && !is_array($setting['desktop_default'])) {
			$setting['desktop_default'] = unserialize($setting['desktop_default']);
		}
		if (!$setting['desktop_default']) {
			$setting['desktop_default'] = array('iconview' => 2, 'taskbar' => 'bottom', 'iconposition' => 0, 'direction' => 0, );
		}
		if ($_G['setting']['dzz_iconview']) {
			$iconview = $_G['setting']['iconview'];
		} else {
			$iconview = C::t('iconview') -> fetch_all();
		}
	} elseif ($operation == 'upload') {
		$setting['maxChunkSize'] = round($setting['maxChunkSize'] / (1024 * 1024), 2);
		$navtitle = lang('upload_set');
		$setting['unRunExts'] = implode(',', dunserialize($setting['unRunExts']));
		$usergroups = DB::fetch_all("select f.*,g.grouptitle from %t f LEFT JOIN %t g ON g.groupid=f.groupid where f.groupid IN ('1','2','9') order by groupid DESC", array('usergroup_field', 'usergroup'));
	} elseif ($operation == 'at') {
		$navtitle = '@'.lang('sector_set');
		$setting['at_range'] = dunserialize($setting['at_range']);
		$usergroups = DB::fetch_all("select f.*,g.grouptitle from %t f LEFT JOIN %t g ON g.groupid=f.groupid where f.groupid IN ('1','2','9') order by groupid DESC", array('usergroup_field', 'usergroup'));
	} elseif ($operation == 'access') {
		$navtitle = lang('register_visit');
		/*if($setting['welcomemsg'] == 1) {
		 $welcomemsg[] = '1';
		 } elseif($setting['welcomemsg'] == 2) {
		 $welcomemsg[] = '2';
		 } elseif($setting['welcomemsg'] == 3) {
		 $welcomemsg[] = '1';
		 $welcomemsg[] = '2';
		 } else {
		 $welcomemsg[] = '0';
		 }*/
		$setting['strongpw'] = dunserialize($setting['strongpw']);
	} elseif ($operation == 'qqlogin') {
		$navtitle = lang('register_visit');
	} elseif ($operation == 'datetime') {
		$navtitle = lang('time_or_date');
		$checktimeformat = array($setting['timeformat'] == 'H:i' ? 24 : 12 => 'checked');
		$setting['userdateformat'] = dateformat($setting['userdateformat']);
		$setting['dateformat'] = dateformat($setting['dateformat']);
		$timezones = lang('setting_timezone');
	} elseif ($operation == 'sec') {
		$navtitle = lang('verification_code_set');
		$seccodecheck = /*$secreturn =*/1;
		$sectpl = '<br /><sec>: <sec><sec>';
		$checksc = array();
		$setting['seccodedata'] = dunserialize($setting['seccodedata']);
		$setting['reginput'] = dunserialize($setting['reginput']);
		$seccodestatus[1] = $setting['seccodestatus'] & 1;
		$seccodestatus[2] = $setting['seccodestatus'] & 2;
		$seccodestatus[3] = $setting['seccodestatus'] & 4;
	} elseif ($operation == 'desktop') {
		$navtitle = lang('desktop_set');
	} elseif ($operation == 'loginset') {
		$navtitle = lang('login_page_set');
		if ($setting['loginset'] && !is_array($setting['loginset'])) {
			$setting['loginset'] = unserialize($setting['loginset']);
		}
	} elseif ($operation == 'smiley') {
		$navtitle = lang('expression_set');
	} elseif ($operation == 'mail') {
		$navtitle = lang('mail');
		$setting['mail'] = dunserialize($setting['mail']);
		$passwordmask = $setting['mail']['auth_password'] ? $setting['mail']['auth_password']{0} . '********' . substr($setting['mail']['auth_password'], -2) : '';
		$smtps = array();
		foreach ($setting['mail']['smtp'] as $id => $smtp) {
			$smtp['authcheck'] = $smtp['auth'] ? 'checked' : '';
			$smtp['auth_password'] = $smtp['auth_password'] ? $smtp['auth_password']{0} . '********' . substr($smtp['auth_password'], -2) : '';
			$smtps[$id] = $smtp;
		}
	} elseif ($operation == 'censor') {
		$navtitle = lang('words_set');
		loadcache('censor');
		$badwords = $_G['cache']['censor']['words'];
		$replace = empty($_G['cache']['censor']['replace']) ? '*' : $_G['cache']['censor']['replace'];
	}
} else {

	$settingnew = $_GET['settingnew'];
	if ($operation == 'basic') {
		$settingnew['bbname'] = $settingnew['sitename'];
		foreach ($settingnew['thumbsize'] as $key => $value) {
			$value['width'] = intval($value['width']);
			if (!$value['width']) {
				$value['width'] = ($key == 'samll' ? 256 : ($key == 'middle' ? 800 : 1440));
			}
			$value['height'] = intval($value['height']);
			if (!$value['height']) {
				$value['height'] = ($key == 'samll' ? 256 : ($key == 'middle' ? 600 : 900));
			}
			$settingnew['thumbsize'][$key] = $value;
		}

	} elseif ($operation == 'upload') {
		if ($settingnew['unRunExts'])
			$settingnew['unRunExts'] = explode(',', trim($settingnew['unRunExts'], ','));
		else
			$settingnew['unRunExts'] = array();
		if (!in_array('php', $settingnew['unRunExts']))
			$settingnew['unRunExts'][] = 'php';
		$settingnew['maxChunkSize'] = intval($settingnew['maxChunkSize'] * 1024 * 1024);
		$group = $_GET['group'];
		foreach ($group as $key => $value) {
			C::t('usergroup_field') -> update(intval($key), array('maxspacesize' => intval($value['maxspacesize']), 'maxattachsize' => intval($value['maxattachsize']), 'attachextensions' => trim($value['attachextensions'])));
		}
		include_once libfile('function/cache');
		updatecache('usergroups');
	} elseif ($operation == 'mail') {
		$setting['mail'] = dunserialize($setting['mail']);
		$oldsmtp = $settingnew['mail']['mailsend'] == 3 ? $settingnew['mail']['smtp'] : $settingnew['mail']['esmtp'];
		$deletesmtp = $settingnew['mail']['mailsend'] != 1 ? ($settingnew['mail']['mailsend'] == 3 ? $settingnew['mail']['smtp']['delete'] : $settingnew['mail']['esmtp']['delete']) : array();

		$settingnew['mail']['smtp'] = array();
		foreach ($oldsmtp as $id => $value) {
			if ((empty($deletesmtp) || !in_array($id, $deletesmtp)) && !empty($value['server']) && !empty($value['port'])) {
				$passwordmask = $setting['mail']['smtp'][$id]['auth_password'] ? $setting['mail']['smtp'][$id]['auth_password']{0} . '********' . substr($setting['mail']['smtp'][$id]['auth_password'], -2) : '';
				$value['auth_password'] = $value['auth_password'] == $passwordmask ? $setting['mail']['smtp'][$id]['auth_password'] : $value['auth_password'];
				$settingnew['mail']['smtp'][] = $value;
			}
		}

		if (!empty($_GET['newsmtp'])) {
			foreach ($_GET['newsmtp']['server'] as $id => $server) {
				if (!empty($server) && !empty($_GET['newsmtp']['port'][$id])) {
					$settingnew['mail']['smtp'][] = array('server' => $server, 'port' => $_GET['newsmtp']['port'][$id] ? intval($_GET['newsmtp']['port'][$id]) : 25, 'auth' => $_GET['newsmtp']['auth'][$id] ? 1 : 0, 'from' => $_GET['newsmtp']['from'][$id], 'auth_username' => $_GET['newsmtp']['auth_username'][$id], 'auth_password' => $_GET['newsmtp']['auth_password'][$id]);
				}

			}
		}
	} elseif ($operation == 'access') {
		isset($settingnew['reglinkname']) && empty($settingnew['reglinkname']) && $settingnew['reglinkname'] = lang('register_immediately');
		$settingnew['pwlength'] = intval($settingnew['pwlength']);
		$settingnew['regstatus'] = intval($settingnew['regstatus']);

		/*if(in_array('open', $settingnew['regstatus']) && in_array('invite', $settingnew['regstatus'])) {
		 $settingnew['regstatus'] = 3;
		 } elseif(in_array('open', $settingnew['regstatus'])) {
		 $settingnew['regstatus'] = 1;
		 } elseif(in_array('invite', $settingnew['regstatus'])) {
		 $settingnew['regstatus'] = 2;
		 } else {
		 $settingnew['regstatus'] = 0;
		 }*/
		/*$settingnew['welcomemsg'] = (array)$settingnew['welcomemsg'];
		 if(in_array('1', $settingnew['welcomemsg']) && in_array('2', $settingnew['welcomemsg'])) {
		 $settingnew['welcomemsg'] = 3;
		 } elseif(in_array('1', $settingnew['welcomemsg'])) {
		 $settingnew['welcomemsg'] = 1;
		 } elseif(in_array('2', $settingnew['welcomemsg'])) {
		 $settingnew['welcomemsg'] = 2;
		 } else {
		 $settingnew['welcomemsg'] = 0;
		 }*/

		if (empty($settingnew['strongpw'])) {
			$settingnew['strongpw'] = array();
		}
	} elseif ($operation == 'datetime') {
		if (isset($settingnew['timeformat'])) {
			$settingnew['timeformat'] = $settingnew['timeformat'] == '24' ? 'H:i' : 'h:i A';
		}
		if (isset($settingnew['dateformat'])) {
			$settingnew['dateformat'] = dateformat($settingnew['dateformat'], 'format');
		}
	} elseif ($operation == 'sec') {
		$settingnew['seccodestatus'] = bindec(intval($settingnew['seccodestatus'][3]) . intval($settingnew['seccodestatus'][2]) . intval($settingnew['seccodestatus'][1]));

	} elseif ($operation == 'qqlogin') {
		if (empty($settingnew['qq_appid']) || empty($settingnew['qq_appkey'])) {
			$settingnew['qq_login'] = 0;
		}

	} elseif ($operation == 'censor') {
		$data = array('replace' => trim($_GET['replace']), 'words' => $_GET['badwords']);
		savecache('censor', $data);
		showmessage('do_success', dreferer());
	} elseif ($operation == 'loginset') {
		if ($back = trim($settingnew['loginset']['background'])) {
			if (strpos($back, '#') === 0) {
				$settingnew['loginset']['bcolor'] = $back;
			} else {
				$arr = explode('.', $back);
				$ext = array_pop($arr);
				if ($ext && in_array(strtolower($ext), array('jpg', 'jpeg', 'gif', 'png'))) {
					$settingnew['loginset']['img'] = $back;
					$settingnew['loginset']['bcolor'] = 'rgb(58, 110, 165)';
				} else {
					$settingnew['loginset']['url'] = $back;
					$settingnew['loginset']['bcolor'] = 'rgb(58, 110, 165)';
				}
			}
		} else {
			$settingnew['loginset']['bcolor'] = 'rgb(58, 110, 165)';
		}

	} elseif ($operation == 'qywechat') {
		switch($_GET['fbind']) {
			case 'bind' :
				$wechat = new qyWechat( array('appid' => $settingnew['CorpID'], 'appsecret' => $settingnew['CorpSecret']));
				if (!$wechat -> checkAuth()) {
					showmessage(lang('verification_unsuccessful').',errCode：' . $wechat -> errCode . '; errMsg:' . $wechat -> errMsg, dreferer());
				}
				if (empty($setting['token_0']))
					$settingnew['token_0'] = random(8);
				if (empty($setting['encodingaeskey_0']))
					$settingnew['encodingaeskey_0'] = random(43);
				break;
			case 'unbind' :
				$settingnew['CorpID'] = '';
				$settingnew['CorpSecret'] = '';
				break;
		}
	}

	$updatecache = FALSE;
	$settings = array();
	foreach ($settingnew as $key => $val) {
		if ($setting[$key] != $val) {
			$updatecache = TRUE;
			if (in_array($key, array('timeoffset', 'regstatus', 'oltimespan', 'seccodestatus'))) {
				$val = (float)$val;
			}

			$settings[$key] = $val;
		}
	}

	if ($settings) {
		C::t('setting') -> update_batch($settings);
	}
	if ($updatecache) {
		updatecache('setting');
	}
	if ($operation == 'upload') {
		dfsockopen($_G['siteurl'] . 'misc.php?mod=setunrun', 0, '', '', FALSE, '', 1);
	}
	showmessage('do_success', dreferer());
}
function dateformat($string, $operation = 'formalise') {
	$string = dhtmlspecialchars(trim($string));
	$replace = $operation == 'formalise' ? array( array('n', 'j', 'y', 'Y'), array('mm', 'dd', 'yy', 'yyyy')) : array( array('mm', 'dd', 'yyyy', 'yy'), array('n', 'j', 'Y', 'y'));
	return str_replace($replace[0], $replace[1], $string);
}

include template('main');
?>
