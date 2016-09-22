<?php
namespace Ws\User\Command;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var Sandstorm\UserManagement\Command\SandstormUserCommandController
	 */
	protected $userManagementController;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * Import accounts
	 *
	 * @return string
	 */
	public function importAccountsCommand() {
		$accounts = $this->getAccounts();
		foreach ($accounts as $account) {
			$username =  html_entity_decode(urldecode($account['username']));
			$name = html_entity_decode(urldecode($account['name']));
			$group = html_entity_decode(urldecode($account['belong_to_group']));
			$nameArray = explode(" ", $name, 2);
			$firstName = $nameArray[0];
			$lastName = isset($nameArray[1]) ? $nameArray[1] : '-';
			$additionalAttributes = 'group:' . $group;
			try {
				$this->userManagementController->createCommand($username, 'dontknowhowtosetittonull', $firstName, $lastName);
			} catch (\Exception $e) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}
		return "Done!";
	}

	protected function getAccounts() {
		$sql = "SELECT f.username, f.belong_to_group, n.confirmeddate, n.name FROM con_frontendusers AS f JOIN con_news_rcp AS n ON f.username = n.email WHERE f.active = 1";
		$statement = $this->entityManager->getConnection()->prepare($sql);
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}
}
