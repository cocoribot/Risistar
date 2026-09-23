<?php

/**
 *  2Moons 
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

if (!allowedTo(str_replace(array(dirname(__FILE__), '\\', '/', '.php'), '', __FILE__))) throw new Exception("Permission error!");

function ShowModulePage()
{
	global $LNG;

	$config	= Config::get(Universe::getEmulated());
	$module	= explode(';', $config->moduls);
	$module[MODULE_TELEMETRY] = $module[MODULE_TELEMETRY] ?? 0;
	$module = array_replace(array_fill(0, MODULE_AMOUNT, 1), $module);
	
	$mode = HTTP::_GP('mode', '');
	if(!empty($mode)) {
		$id = HTTP::_GP('id', -1);
		if (HTTP::_GP('sid', '') !== session_id() || $id < 0 || $id >= MODULE_AMOUNT) {
			throw new Exception("Permission error!");
		}
		$module[$id] = ($mode == 'aktiv') ? 1 : 0;
		$config->moduls = implode(";", $module);
		$config->save();
		ClearCache();
	}
	
	$IDs	= range(0, MODULE_AMOUNT - 1);
	foreach($IDs as $ID => $Name) {
		$Modules[$ID]	= array(
			'name'	=> $LNG['modul_'.$ID],
			'state'	=> isset($module[$ID]) ? $module[$ID] : 1,
		);
	}
	
	asort($Modules);
	$template	= new template();

	$template->assign_vars(array(
		'sid' => session_id(),
		'Modules'				=> $Modules,
		'mod_module'			=> $LNG['mod_module'],
		'mod_info'				=> $LNG['mod_info'],
		'mod_active'			=> $LNG['mod_active'],
		'mod_deactive'			=> $LNG['mod_deactive'],
		'mod_change_active'		=> $LNG['mod_change_active'],
		'mod_change_deactive'	=> $LNG['mod_change_deactive'],
	));
	
	$template->show('ModulePage.tpl');
}