<?php

/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2015
 */

namespace OCA\OcSms\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use \OCP\AppFramework\Db\Mapper;
use \OCP\IDb;

class ConfigMapper extends Mapper {

	/**
	 * @var string ownCloud user id
	 */
	private $user;

	/**
	 * @var \OCP\Security\ICrypto
	 */
	private $crypto;

	public function __construct (IDb $api, $user, $crypto){
		parent::__construct($api, 'ocsms_config');
		$this->user = $user;
		$this->crypto = $crypto;
	}

	public function set ($key, $value){
		$value = $this->crypto->encrypt($value);
		if($this->hasKey($key, $value)){
			$sql = "UPDATE `*PREFIX*ocsms_config` SET `value` = ? WHERE `user` = ? AND `key` = ?";
			$this->execute($sql, array($value, $this->user, $key));
		} else {
			$sql = "INSERT INTO `*PREFIX*ocsms_config` (`user`,`key`,`value`) VALUES (?,?,?);";
			$this->execute($sql, array($this->user, $key, $value));
		}
	}

	public function hasKey ($key, $value){
		try {
			$sql = "SELECT `key` FROM `*PREFIX*ocsms_config` WHERE `key` = ? AND `user` = ?";
			$this->findEntity($sql, array($key, $this->user));

			return true;
		} catch (DoesNotExistException $e){
			return false;
		}
	}

	public function getKey ($key) {
		try {
			$query = \OCP\DB::prepare("SELECT `value` FROM `*PREFIX*ocsms_config` WHERE `key` = ? AND `user` = ?");
			$result = $query->execute(array($key, $this->user));
			while($row = $result->fetchRow()) {
				return $this->crypto->decrypt($row["value"]);
			}
			return false;
		} catch (DoesNotExistException $e){
			return false;
		}
	}

	/**
	* Helpers for different config options
	*/
	public function getCountry () { return $this->getKey("country"); }
};

?>
