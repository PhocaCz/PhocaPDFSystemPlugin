<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
jimport( 'joomla.plugin.plugin' );

class plgSystemPhocaPDF extends JPlugin
{	
	
	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
	}
	
	function onAfterRender() {
		
		$app 	= JFactory::getApplication();
		if ($app->getName() != 'site') { return true;}
		
		$format = $app->input->get('format', '', 'string');
		if ($format == 'feed') { return true;}
		
		$option = $app->input->get('option', '', 'string');
		if ($option != 'com_content') { return true;}
		
		$view = $app->input->get('view', '', 'string');
		if ($view != 'article') { return true;}
		/*$prm						= array();
		$prm['display_article'] 	= $this->params->get('display_article', 1);
		$prm['display_featured'] 	= $this->params->get('display_featured', 1);
		
		$view = $app->input->get('view', '', 'string');
		if ($view == 'article' || $view == 'featured') {
			if ($view == 'article' && (int)$prm['display_article'] != 1) {
				return "";
			}
			if ($view == 'featured' && (int)$prm['display_featured'] != 1) {
				return "";
			}
		} else {
			return "";
		}*/
	
		
		$id 		= $app->input->get('id', '', 'string');
		$item 	= new StdClass();
		
		
		if ((int)$id > 0) {
			$db		= JFactory::getDBO();
			$query 	= 'SELECT a.id, a.alias, a.attribs, a.catid, '
			.' c.alias AS category_alias'
			.' FROM #__content AS a'
			.' LEFT JOIN #__categories AS c on c.id = a.catid'
			.' WHERE a.id = '.(int) $id;
			$db->setQuery($query, 0, 1);
			$item = $db->loadObject();
			
			if (!empty($item)) {
				$params = new JRegistry();
				$params->loadString($item->attribs);
				
				$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
				$item->catslug = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;

				$buffer = JResponse::getBody();
		
				$phocaPDF = false;
				if (JPluginHelper::isEnabled('phocapdf', 'content')) {
					include_once(JPATH_ADMINISTRATOR.'/components/com_phocapdf/helpers/phocapdf.php');
					$phocaPDF = PhocaPDFHelper::getPhocaPDFContentIcon($item, $params);
				}
				
				//$pattern = '/<ul class="dropdown-menu actions">(.*?)<\/ul>/s';
				$pattern = '/<ul class="dropdown-menu"(.*?)>(.*?)<\/ul>/s';
				
				$replacement = '<ul class="dropdown-menu actions">$2'.$phocaPDF.'</ul>';

				$buffer2 = preg_replace($pattern, $replacement, $buffer);
				JResponse::setBody($buffer2);
			
			}
		}
		return true;
	}
}
?>