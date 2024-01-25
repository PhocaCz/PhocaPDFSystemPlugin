<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

class plgSystemPhocaPDF extends CMSPlugin
{

	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
	}

	function onAfterRender() {

		$app 	= Factory::getApplication();
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
			$db		= Factory::getDBO();
			$query 	= 'SELECT a.id, a.alias, a.attribs, a.catid, '
			.' c.alias AS category_alias'
			.' FROM #__content AS a'
			.' LEFT JOIN #__categories AS c on c.id = a.catid'
			.' WHERE a.id = '.(int) $id;
			$db->setQuery($query, 0, 1);
			$item = $db->loadObject();

			if (!empty($item)) {
				$params = new Registry();
				$params->loadString($item->attribs);

				$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
				$item->catslug = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;

				$buffer = $app->getBody();

				$phocaPDF = false;
				if (PluginHelper::isEnabled('phocapdf', 'content')) {
					include_once(JPATH_ADMINISTRATOR.'/components/com_phocapdf/helpers/phocapdf.php');
					$phocaPDF = PhocaPDFHelper::getPhocaPDFContentIcon($item, $params);
				}

				//$pattern = '/<ul class="dropdown-menu actions">(.*?)<\/ul>/s';
				//$pattern = '/<ul class="dropdown-menu"(.*?)>(.*?)<\/ul>/s';

				//$replacement = '<ul class="dropdown-menu actions">$2'.$phocaPDF.'</ul>';
				// BOOTSTRAP
				// Be aware - item_page added in Joomla 5
				$pattern = '/<div class="com-content-article item-page(.*?)>(.*?)<\/div>/s';
				$replacement = '<div class="com-content-article$1">'.$phocaPDF.'$2</div>';
				$buffer2 = preg_replace($pattern, $replacement, $buffer);

                //UIkit
                $phocaPDF = str_replace('class="btn btn-danger"', 'class="uk-button uk-button-danger"', $phocaPDF);
                $pattern = '/<article(.*?) class="uk-article(.*?)>(.*?)<\/article>/s';
				$replacement = '<article$1 class="uk-article$2">'.$phocaPDF.'$3</article>';

				$buffer3 = preg_replace($pattern, $replacement, $buffer2);
				$app->setBody($buffer3);

			}
		}
		return true;
	}

	public function onAfterDispatch() {
		$app 	= Factory::getApplication();
		$doc	= $app->getDocument();

		if ($app->getName() == 'administrator') { return true;}

		$format     = $app->input->get('format', '', 'string');
		if ($format == 'feed') { return true;}
		if ($format == 'pdf') { return true;}
		if ($format == 'raw') { return true;}
		if ($format == 'xml') { return true;}

		$wa = $doc->getWebAssetManager();
		$wa->addInlineStyle('
			.pdf-print-icon {
				float: right;
			}
		');

	}
}
?>
