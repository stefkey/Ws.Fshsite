<?php
namespace Ws\User\Command;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use Sandstorm\UserManagement\Domain\Model\PasswordDto;
use Sandstorm\UserManagement\Domain\Model\RegistrationFlow;

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
	 * @Flow\Inject
	 * @var Sandstorm\UserManagement\Domain\Service\UserCreationServiceInterface
	 */
	protected $userCreationService;

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
			$groupId = html_entity_decode(urldecode($account['belong_to_group']));
			$nameArray = explode(' ', $name, 2);
			$firstName = $nameArray[0];
			$lastName = isset($nameArray[1]) ? $nameArray[1] : '-';
			$attributes = [];
			$attributes['groupId'] = $groupId;
			$attributes['creationDateTime'] =  new \DateTime($account['confirmeddate']);

			$password = $this->randomPassword();
			$passwordDto = new PasswordDto();
			$passwordDto->setPassword($password);
			$passwordDto->setPasswordConfirmation($password);
			$registrationFlow = new RegistrationFlow();
			$registrationFlow->setPasswordDto($passwordDto);
			$registrationFlow->setEmail($username);
			$registrationFlow->setFirstName($firstName);
			$registrationFlow->setLastName($lastName);
			$registrationFlow->setAttributes($attributes);
			$registrationFlow->storeEncryptedPassword();
			try {
				$this->userCreationService->createUserAndAccount($registrationFlow);
				$this->outputLine('Added the User <b>"%s"</b> with groupId <b>"%s"</b>.', array($username, $groupId));
			} catch (\Exception $e) {
				$this->outputLine('Caught exception for user "%s": "%s"\n', array($username, $e->getMessage()));
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

	protected function randomPassword() {
		return md5(uniqid(mt_rand(), true));
	}
}
