<?php
namespace wcf\acp\form;
use wcf\data\style\StyleEditor;
use wcf\form\AbstractForm;
use wcf\system\cache\builder\StyleCacheBuilder;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\package\PackageArchive;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;
use wcf\util\HeaderUtil;

/**
 * Shows the style import form.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	acp.form
 * @category	Community Framework
 */
class StyleImportForm extends AbstractForm {
	/**
	 * @see	\wcf\page\AbstractPage::$activeMenuItem
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.style.import';
	
	/**
	 * @see	\wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('admin.style.canManageStyle');
	
	/**
	 * upload data
	 * @var	array<string>
	 */
	public $source = array();
	
	/**
	 * style editor object
	 * @var	\wcf\data\style\StyleEditor
	 */
	public $style = null;
	
	/**
	 * @see	\wcf\form\IForm::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		if (isset($_FILES['source'])) $this->source = $_FILES['source'];
	}
	
	/**
	 * @see	\wcf\form\IForm::validate()
	 */
	public function validate() {
		parent::validate();
		
		if (empty($this->source['name'])) {
			throw new UserInputException('source');
		}
		
		if (empty($this->source['tmp_name'])) {
			throw new UserInputException('source', 'uploadFailed');
		}
		
		try {
			// check if the uploaded file is a package
			$archive = new PackageArchive($this->source['tmp_name']);
			$archive->openArchive();
			
			// check if the package is an application
			if ($archive->getPackageInfo('isApplication')) {
				throw new SystemException("Package is application");
			}
			
			// check if the package includes a style
			$containsStyle = false;
			$installInstructions = $archive->getInstallInstructions();
			foreach ($installInstructions as $instruction) {
				if ($instruction['pip'] == 'style') {
					$containsStyle = true;
					break;
				}
			}
			
			if (!$containsStyle) {
				throw new SystemException("Package contains no style");
			}
			
			$filename = FileUtil::getTemporaryFilename('package_', preg_replace('!^.*(?=\.(?:tar\.gz|tgz|tar)$)!i', '', basename($this->source['name'])));
			
			if (!@move_uploaded_file($this->source['tmp_name'], $filename)) {
				throw new SystemException("Cannot move uploaded file");
			}
			
			WCF::getSession()->register('stylePackageImportLocation', $filename);
			
			HeaderUtil::redirect(LinkHandler::getInstance()->getLink('PackageStartInstall', array(
				'action' => 'install'
			)));
			exit;
		}
		catch (SystemException $e) {
			// ignore errors
		}
		
		try {
			$this->style = StyleEditor::import($this->source['tmp_name']);
		}
		catch (\Exception $e) {
			@unlink($this->source['tmp_name']);
			throw new UserInputException('source', 'importFailed');
		}
	}
	
	/**
	 * @see	\wcf\form\IForm::save()
	 */
	public function save() {
		parent::save();
		
		StyleCacheBuilder::getInstance()->reset();
		
		@unlink($this->source['tmp_name']);
		$this->saved();
		
		WCF::getTPL()->assign('success', true);
	}
}
