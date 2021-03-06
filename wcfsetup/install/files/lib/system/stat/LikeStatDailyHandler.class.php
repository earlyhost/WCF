<?php
namespace wcf\system\stat;
use wcf\data\like\Like;
use wcf\system\WCF;

/**
 * Stat handler implementation for like stats.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.stat
 * @category	Community Framework
 */
class LikeStatDailyHandler extends AbstractStatDailyHandler {
	protected $likeValue = Like::LIKE;
	
	/**
	 * @see	\wcf\system\stat\IStatDailyHandler::getData()
	 */
	public function getData($date) {
		$sql = "SELECT	COUNT(*)
			FROM	wcf".WCF_N."_like
			WHERE	time BETWEEN ? AND ?
				AND likeValue = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($date, $date + 86399, $this->likeValue));
		$counter = intval($statement->fetchColumn());
		
		$sql = "SELECT	COUNT(*)
			FROM	wcf".WCF_N."_like
			WHERE	time < ?
				AND likeValue = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($date + 86400, $this->likeValue));
		$total = intval($statement->fetchColumn());
		
		return array(
			'counter' => $counter,
			'total' => $total
		);
	}
}
