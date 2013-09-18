<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * $Id: browse_links.php 1421 2006-04-10 09:27:15Z mundaun $
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   78: class SC_browse_links
 *   99:     function init ()
 *  120:     function main()
 *  174:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

if (!ereg('typo3conf', $_SERVER['PHP_SELF'])) {
  $BACK_PATH = '../../';
  define('TYPO3_MOD_PATH', 'ext/rgfolderselector/');
} else {
  $BACK_PATH = '../../../typo3/';
  define('TYPO3_MOD_PATH', '../typo3conf/ext/rgfolderselector/');
}

require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:lang/locallang_browse_links.xml');

#require_once (PATH_typo3.'/class.browse_links.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');
require_once (PATH_t3lib.'class.t3lib_foldertree.php');
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');


	// Include classes
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_typo3.'/class.db_list.inc');
require_once (PATH_typo3.'/class.db_list_extra.inc');
require_once (PATH_t3lib.'/class.t3lib_pagetree.php');


class localFolderTree extends t3lib_folderTree {
	var $ext_IconMode=1;


	/**
	 * Initializes the script path
	 *
	 * @return	void
	 */
	function localFolderTree() {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
//		$this->t3lib_folderTree();
		 parent::__construct();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, ready for output.
	 * @param	array		The "record"
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
                     $relPath = substr($v['path'],strlen(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT').'/'));
                     /* fix relPath if in subdirectory */
           $tmpPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
           $tmpPath = substr($tmpPath, strpos($tmpPath, "/", 8) + 1 );
           $relPath = substr($relPath, strlen($tmpPath));
                     $aOnClick='return link_folder(\''.$relPath.'\');';

		  if(t3lib_div::_GP('type')=='folder') {
	#	  $v['path'] = 'fileadmin/log/rggooglemap-log.txt';
        				#$test = t3lib_div::view_array($v);
        				$aOnClick='return link_folder(\''.$relPath.'\');';
      }


			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$test.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Returns true if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		True is returned if the path is found in the web-part of the server and is NOT a recycler or temp folder
	 */
	function ext_isLinkable($v)	{
		$webpath=t3lib_BEfunc::getPathType_web_nonweb($v['path']);	// Checking, if the input path is a web-path.
		if (strstr($v['path'],'_recycler_') || strstr($v['path'],'_temp_') || $webpath!='web')	{
			return 0;
		}
		return 1;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($bMark)	{
			$anchor = '#'.$bMark;
			$name=' name="'.$bMark.'"';
		}
		$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?PM='.$cmd.'\',\''.$anchor.'\');';
		return '<a href="#"'.$name.' onclick="'.htmlspecialchars($aOnClick).'">'.$icon.'</a>';
	}


	/**
	 * Create the folder navigation tree in HTML
	 *
	 * @param	mixed		Input tree array. If not array, then $this->tree is used.
	 * @return	string		HTML output of the tree.
	 */
	function printTree($treeArr='')	{
		global $BACK_PATH;
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

			// Preparing the current-path string (if found in the listing we will see a red blinking arrow).
		if (!$GLOBALS['SOBE']->browser->curUrlInfo['value'])	{
			$cmpPath='';
		} else if (substr(trim($GLOBALS['SOBE']->browser->curUrlInfo['info']),-1)!='/')	{
			$cmpPath=PATH_site.dirname($GLOBALS['SOBE']->browser->curUrlInfo['info']).'/';
		} else {
			$cmpPath=PATH_site.$GLOBALS['SOBE']->browser->curUrlInfo['info'];
		}

			// Traverse rows for the tree and print them into table rows:
		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass=($c+1)%2 ? 'bgColor' : 'bgColor-10';

				// Creating blinking arrow, if applicable:
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act']=='file' && $cmpPath==$v['row']['path'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}
				// Create arrow-bullet for file listing (if folder path is linkable):
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['row']['path']).'\');';
			$cEbullet = $this->ext_isLinkable($v['row']) ? '<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' : '';

				// Put table row with folder together:
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap">'.$v['HTML'].$this->wrapTitle(t3lib_div::fixed_lgd_cs($v['row']['title'],$titleLen),$v['row']).'</td>
					'.$arrCol.'
					<td>&nbsp;</td>
				</tr>';
		}

		$out='

			<!--
				Folder tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				'.$out.'
			</table>';
		return $out;
	}
}

class rteFolderTree extends localFolderTree {
}



/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_browse_links {


	/**
	 * The mode determines the main kind of output from the element browser.
	 * There are these options for values: rte, db, file, filedrag, wizard.
	 * "rte" will show the link selector for the Rich Text Editor (see main_rte())
	 * "db" will allow you to browse for pages or records in the page tree (for TCEforms, see main_db())
	 * "file"/"filedrag" will allow you to browse for files or folders in the folder mounts (for TCEforms, main_file())
	 * "wizard" will allow you to browse for links (like "rte") which are passed back to TCEforms (see main_rte(1))
	 *
	 * @see main()
	 */
	var $mode;

	/**
	 * holds Instance of main browse_links class
	 * needed fo intercommunication between various classes that need access to variables via $GLOBALS['SOBE']
	 * Not the most nice solution but introduced since we don't have another general way to return class-instances or registry for now
	 *
	 * @var browse_links
	 */

	var $browser;


	/**
	 * not really needed but for backwards compatibility ...
	 *
	 * @return	void
	 */
	function init ()	{

			// Find "mode"
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode = 'rte';
		}
					// Main GPvars:
		$this->pointer = t3lib_div::_GP('pointer');
		$this->bparams = t3lib_div::_GP('bparams');
		$this->P = t3lib_div::_GP('P');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->PM = t3lib_div::_GP('PM');

			// Find "mode"
		$this->mode=t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode='rte';
		}

			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode=='wizard')	{
			$currentLinkParts = t3lib_div::trimExplode(' ',$this->P['currentValue']);
			$this->curUrlArray = array(
				'target' => $currentLinkParts[1]
			);
			$this->curUrlInfo=$this->parseCurUrl($this->siteURL.'?id='.$currentLinkParts[0],$this->siteURL);
		} else {
			$this->curUrlArray = t3lib_div::_GP('curUrl');
			if ($this->curUrlArray['all'])	{
				$this->curUrlArray=t3lib_div::get_tag_attributes($this->curUrlArray['all']);
			}
			$this->curUrlInfo=$this->parseCurUrl($this->curUrlArray['href'],$this->siteURL);
		}

			// Determine nature of current url:
		$this->act=t3lib_div::_GP('act');
		if (!$this->act)	{
			$this->act=$this->curUrlInfo['act'];
		}

			// Rich Text Editor specific configuration:
		$addPassOnParams='';
		if ((string)$this->mode=='rte')	{
			$RTEtsConfigParts = explode(':',$this->RTEtsConfigParams);
			$addPassOnParams.='&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams);
			$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}

			// Initializing the target value (RTE)
		$this->setTarget = $this->curUrlArray['target'];
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target']))	{
			$this->setTarget=$this->thisConfig['defaultLinkTarget'];
		}

			// Initializing the title value (RTE)
		$this->setTitle = $this->curUrlArray['title'];



			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';



			// Finally, add the accumulated JavaScript to the template object:

		$this->doc->JScodeArray['rgfolderselector'] = $JScode;
			// Creating backend template object:
			// this might not be needed but some classes refer to $GLOBALS['SOBE']->doc, so ...
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
	}


	/**
	 * Main function, detecting the current mode of the element browser and branching out to internal methods.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER, $BACK_PATH;

			// Main GPvars:
		$this->pointer = t3lib_div::_GP('pointer');
		$this->bparams = t3lib_div::_GP('bparams');
		$this->P = t3lib_div::_GP('P');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->PM = t3lib_div::_GP('PM');

			// Find "mode"
		$this->mode=t3lib_div::_GP('mode');
		#$this->content = 'xxx';

			unset($this->P['fieldChangeFunc']['alert']);
			#reset($this->P['fieldChangeFunc']);
			$update='';
			if ($this->P['fieldChangeFunc']) {
      while(list($k,$v)=each($this->P['fieldChangeFunc']))	{
				$update.= '	window.opener.'.$v;
			}
			}
						$P2=array();
			$P2['itemName']=$this->P['itemName'];
			$P2['formName']=$this->P['formName'];
			$P2['fieldChangeFunc']=$this->P['fieldChangeFunc'];
			$addPassOnParams.=t3lib_div::implodeArrayForUrl('P',$P2);

  					// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode.= '
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";

			var cur_href="'.($this->curUrlArray['href']?$this->curUrlArray['href']:'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";

			function setTarget(target)	{	//
				cur_target=target;
				add_target="&curUrl[target]="+escape(target);
			}
			function setTitle(title)	{	//
				cur_title=title;
				add_title="&curUrl[title]="+escape(title);
			}
			function setValue(value)	{	//
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
							function checkReference()	{	//
					if (window.opener && window.opener.document && window.opener.document.'.$this->P['formName'].' && window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"] )	{
						return window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"];
					} else {
						close();
					}
				}
				function updateValueInMainForm(input)	{	//
					var field = checkReference();
					if (field)	{
						field.value = input;
						'.$update.'
					}
				}
		';

		$JScode.='
				function link_folder(folder)	{	//
					//var theLink = \''.$this->siteURL.'\'+folder;
		updateValueInMainForm(folder+" "+cur_target);
		close();
		return false;
				}
			';
			// General "jumpToUrl" function:
		$JScode.='
			function jumpToUrl(URL,anchor)	{	//
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$this->mode.'" : "";
				var theLocation = URL+add_act+add_mode+add_href+add_target+add_title+add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':'').'+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';


			// This is JavaScript especially for the TBE Element Browser!
		$pArr = explode('|',$this->bparams);
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';
		$JScode.='
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$BACK_PATH.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener
				&& parent.window.opener.content
				&& parent.window.opener.content.document.editform
				&& parent.window.opener.content.document.editform["'.$formFieldName.'"]
						) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}

		';
  	$this->doc->JScodeArray['rggooglemap_wizard_loadfunc'] = $JScode;



				$content=$this->doc->startPage('RTE link');

			// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(explode(',','page,file,url,mail,spec'),t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		reset($allowedItems);
		if (!in_array($this->act,$allowedItems))	$this->act = current($allowedItems);

	#	$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';


				$foldertree = t3lib_div::makeInstance('rteFolderTree');
				$foldertree->thisScript = $this->thisScript;
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder))			$this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
			#	$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

				$content.= '

			<!--
				Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
				// Add some space
			$content.='<br /><br />';


			// End page, return content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);

		$this->content .=$content;


	}

	/**
	 * Print module content
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
	function checkFolder($folder)	{
		$fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

		return $fileProcessor->checkPathAgainstMounts(ereg_replace('\/$','',$folder).'/') ? TRUE : FALSE;
	}
	function parseCurUrl($href,$siteUrl)	{
		$href = trim($href);
		if ($href)	{
			$info=array();

				// Default is "url":
			$info['value']=$href;
			$info['act']='url';

			$specialParts = explode('#_SPECIAL',$href);
			if (count($specialParts)==2)	{	// Special kind (Something RTE specific: User configurable links through: "userLinks." from ->thisConfig)
				$info['value']='#_SPECIAL'.$specialParts[1];
				$info['act']='spec';
			} elseif (t3lib_div::isFirstPartOfStr($href,$siteUrl))	{	// If URL is on the current frontend website:
				$rel = substr($href,strlen($siteUrl));
				if (@file_exists(PATH_site.rawurldecode($rel)))	{	// URL is a file, which exists:
					$info['value']=rawurldecode($rel);
					$info['act']='file';
				} else {	// URL is a page (id parameter)
					$uP=parse_url($rel);
					if (!trim($uP['path']))	{
						$pp = explode('id=',$uP['query']);
						$id = $pp[1];
						if ($id)	{
								// Checking if the id-parameter is an alias.
							if (!t3lib_div::testInt($id))	{
								list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$id);
								$id=intval($idPartR['uid']);
							}

							$pageRow = t3lib_BEfunc::getRecordWSOL('pages',$id);
							$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
							$info['value']=$GLOBALS['LANG']->getLL('page',1)." '".htmlspecialchars(t3lib_div::fixed_lgd_cs($pageRow['title'],$titleLen))."' (ID:".$id.($uP['fragment']?', #'.$uP['fragment']:'').')';
							$info['pageid']=$id;
							$info['cElement']=$uP['fragment'];
							$info['act']='page';
						}
					}
				}
			} else {	// Email link:
				if (strtolower(substr($href,0,7))=='mailto:')	{
					$info['value']=trim(substr($href,7));
					$info['act']='mail';
				}
			}
			$info['info'] = $info['value'];
		} else {	// NO value inputted:
			$info=array();
			$info['info']=$GLOBALS['LANG']->getLL('none');
			$info['value']='';
			$info['act']='page';
		}
		return $info;
	}

	function expandFolder($expandFolder=0,$extensionList='')	{
		global $BACK_PATH;

		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{

				// Create header for filelisting:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('files').':');

				// Prepare current path value for comparison (showing red arrow)
			if (!$this->curUrlInfo['value'])	{
				$cmpPath='';
			} else {
				$cmpPath=PATH_site.$this->curUrlInfo['info'];
			}


				// Create header element; The folder from which files are listed.
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
			$picon='<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($expandFolder,strlen(PATH_site))).'\');">'.$picon.'</a>';
			$out.=$picon.'<br />';

				// Get files from the folder:
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')
			$c=0;
			$cc=count($files);

			if (is_array($files))	{
				foreach($files as $filepath)	{
					$c++;
					$fI=pathinfo($filepath);

						// File icon:
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));

						// If the listed file turns out to be the CURRENT file, then show blinking arrow:
					if ($this->curUrlInfo['act']=="file" && $cmpPath==$filepath)	{
						$arrCol='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
					} else {
						$arrCol='';
					}

						// Get size and icon:
					$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes)';
					$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$icon.'','width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';

						// Put it all together for the file element:
					$out.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
							$arrCol.
							'<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site))).'\');">'.
							$icon.
							htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).
							'</a><br />';
				}
			}
		}
		return $out;
	}
	function barheader($str)	{
		return '

			<!--
				Bar header:
			-->
			<h3 class="bgColor5">'.htmlspecialchars($str).'</h3>
			';
	}

}


// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rgfolderselector/browse_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rgfolderselector/browse_links.php']);
}








// Make instance:
$SOBE = t3lib_div::makeInstance('SC_browse_links');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
