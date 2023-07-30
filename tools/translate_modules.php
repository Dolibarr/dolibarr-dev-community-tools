<?php require_once __DIR__ . '/inc/__tools_header.php';

require_once __DIR__ . '/../class/modulesManager.class.php';

$devToolScriptName =  'TranslateModules';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('devcommunitytools'.$devToolScriptName));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$module = GETPOST('module', 'aZ09');	// Used by actions_setmoduleoptions.inc.php



$error = 0;



/*
 * Actions
 */

$logManager = new devCommunityTools\LogManager();

if($action = ''){

}


/*
 * View
 */



$help_url = '';
$page_name = $langs->trans("DevCommunityTools").' - '.$langs->trans($devToolScriptName);
$arrayofjs = array(
	'devcommunitytools/js/devtools.js'
);

$arrayofcss = array(
	'devcommunitytools/css/devtools.css'
);

llxHeader('', $page_name, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : dol_buildpath('/devcommunitytools/admin/tools.php', 1)).'">'.$langs->trans("BackToToolsList").'</a>';

print load_fiche_titre($page_name, $linkback, 'title_setup');

/*
 * Start Script View
 */

$modulesManager = new \devCommunityTools\ModulesManager($db);
$modulesManager->fetchAll('external');
$logManager->addError($modulesManager->errors);
// Search modules dirs



$logManager->output(true);



if($modulesManager->modules) {

	print '<div class="dev-tools-search-container"><input autofocus name="search_dev_tools" value="" class="dev-tools-search-input" data-target="#module-list-table tr" data-target-find=""   placeholder="'.$langs->trans('Search').'" autocomplete="off"></div>';


	print $langs->trans('XExternalModulesFounds', count($modulesManager->modules));
	print '<div class="div-table-responsive">';
	print '<table id="module-list-table" class="tagtable liste" >' . "\n";
	foreach ($modulesManager->modules as $modName => $objMod) {

		$modulenameshort = strtolower(preg_replace('/^mod/i', '', get_class($objMod)));
		$const_name = 'MAIN_MODULE_' . strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

		// Check filters
		$modulename = $objMod->getName();
		$moduletechnicalname = $objMod->name;
		$moduledesc = $objMod->getDesc();
		$moduledesclong = $objMod->getDescLong();
		$moduleauthor = $objMod->getPublisher();
		$status = !empty($conf->global->$const_name);
		$modulePath = dol_buildpath(strtolower($objMod->name));


		// Load all language files of the qualified module
		if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
			foreach ($objMod->langfiles as $domain) {
				$langs->load($domain);
			}
		}


		print '<tr class="oddeven" module-type="' . dol_escape_htmltag($objMod->isCoreOrExternalModule()) . '">' . "\n";


		// Picto + Name of module
		print '  <td class="tdoverflowmax300" title="' . dol_escape_htmltag($objMod->getName()) . '">';

		print '<div class="module-list-name tdoverflowmax300">';
		$alttext = '';
		//if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
		//if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
		if (!empty($objMod->picto)) {
			if (preg_match('/^\//i', $objMod->picto)) {
				print img_picto($alttext, $objMod->picto, 'class="valignmiddle pictomodule paddingrightonly"', 1);
			} else {
				print img_object($alttext, $objMod->picto, 'class="valignmiddle pictomodule paddingrightonly"');
			}
		} else {
			print img_object($alttext, 'generic', 'class="valignmiddle paddingrightonly"');
		}
		print ' <span class="valignmiddle">' . $objMod->getName() . '</span>';
		print '</div>';

		print '<div class="module-list-desc">';
		print nl2br($objMod->getDesc());
		print '</div>';

		print "</td>\n";

		// Langs
		print '<td class="valignmiddle">';

		$useLang = file_exists($modulePath."/langs");
		if($useLang){
			$langsAvailables = $langs->get_available_languages($modulePath, 0 , 0, 0);

			if($langsAvailables){

				foreach ($langsAvailables as $langCode => $countryAssociated){

					$langCodeArr = explode('_', $langCode);
					$countryCode = strtolower(end($langCodeArr));

					$flag = $langCode;
					if (file_exists(DOL_DOCUMENT_ROOT.'/theme/common/flags/'.$countryCode.'.png')) {
						$flag = ' '.img_picto($countryCode, DOL_URL_ROOT.'/theme/common/flags/'.$countryCode.'.png', '', 1, 0, 1);
					}

					print '<span class="classfortooltip" title="'.dol_escape_htmltag($countryAssociated).'" >'.$flag.'</span>';
				}
			}
		}
		else{
			print '--';
		}

		print "</td>\n";


		print "</tr>\n";


	}


	print "</table>\n";
}
print '</div>';


/*
 * End Script View
 */


// Page end
print dol_get_fiche_end();


require_once __DIR__.'/inc/__tools_footer.php';

/**
 * @param string $oldUrl
 * @param string $newUrl
 * @param devCommunityTools\LogManager $logManager
 * @return false|void
 */
function __processUrlReplace($oldUrl, $newUrl, $logManager, $replaceDomaineOnly = false){
	global $db, $langs;

	require_once DOL_DOCUMENT_ROOT . '/core/class/validate.class.php';
	$validate = new Validate($db, $langs);

	if (empty($oldUrl) || !$validate->isUrl($oldUrl)){
		$logManager->addError($validate->error);
		return false;
	}

	if (empty($newUrl) || !$validate->isUrl($newUrl)){
		$logManager->addError($validate->error);
		return false;
	}

	if($replaceDomaineOnly){
		$parseOldUrl = parse_url($oldUrl);
		$parseNewUrl = parse_url($newUrl);
		$oldUrl = $parseOldUrl['host'];
		$newUrl = $parseNewUrl['host'];
	}


	$logManager->addLog($langs->trans('ReplaceUrlXByY', $oldUrl, $newUrl));

	$tables = array(
		'c_email_templates' => array( 'content'),
		'user' => array( 'signature'),
		'mailing' => array( 'sujet', 'body')
	);

	foreach ($tables as $tableName => $cols){
		$tableName = MAIN_DB_PREFIX.$tableName;
		$sqlShowTable = "SHOW TABLES LIKE '".$db->escape($tableName)."' ";
		$resST = $db->query($sqlShowTable);
		if($resST && $db->num_rows($resST) > 0) {
			foreach ($cols as $col){
				$sql = "UPDATE `".$db->escape($tableName)."` SET `".$db->escape($col)."` = REPLACE(`".$db->escape($col)."`,'".$db->escape($oldUrl)."' ,'".$db->escape($newUrl)."');";
				$resCol = $db->query($sql);
				if(!$sql){
					$logManager->addError($tableName. " :  ".$col." UPDATE ERROR ".$db->error());
				}else{
					$num = $db->affected_rows($resCol);
					$logManager->addSuccess($tableName. " :  ".$col." => ".$num);
				}
			}
		}
		else{
			$logManager->addError("Error : " .$sqlShowTable. " ". $db->error());
		}
	}
}