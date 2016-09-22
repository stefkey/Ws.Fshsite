<?php
namespace Ws\User\Domain\Service;

use Sandstorm\UserManagement\Domain\Model\RegistrationFlow;
use Sandstorm\UserManagement\Domain\Service\UserCreationServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Security\Policy\Role;
use Ws\User\Domain\Model\User;
use Ws\User\Domain\Repository\UserRepository;

/**
 * @Flow\Scope("singleton")
 */
class UserCreationService implements UserCreationServiceInterface
{

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.UserManagement", path="rolesForNewUsers")
     */
    protected $rolesForNewUsers;

    /**
     * In this method, actually create the user / account.
     *
     * NOTE: After this method is called, the $registrationFlow is DESTROYED, so you need to store all attributes
     * in your object as you need them.
     *
     * @param RegistrationFlow $registrationFlow
     * @return void
     */
    public function createUserAndAccount(RegistrationFlow $registrationFlow)
    {
        if (count($this->userRepository->findByEmail($registrationFlow->getEmail())) > 0) {
            throw new \Exception('User with such email already exists: ' . $registrationFlow->getEmail());
        }
        // Create the account
        $account = new \TYPO3\Flow\Security\Account();
        $account->setAccountIdentifier($registrationFlow->getEmail());
        $account->setCredentialsSource($registrationFlow->getEncryptedPassword());
        $account->setAuthenticationProviderName('Sandstorm.UserManagement:Login');

        // Assign preconfigured roles
        foreach ($this->rolesForNewUsers as $roleString){
            $account->addRole(new Role($roleString));
        }

        $attributes = $registrationFlow->getAttributes();
        $groupId = isset($attributes['groupId']) ? $attributes['groupId'] : '';
        $creationDateTime = isset($attributes['creationDateTime']) ? $attributes['creationDateTime'] : null;
        // Create the user
        $user = new User();
        $user->setFirstName($registrationFlow->getFirstName());
        $user->setLastName($registrationFlow->getLastName());
        $user->setEmail($registrationFlow->getEmail());
        $user->setGroupId($groupId);
        $user->setCreationDateTime($creationDateTime);
        $user->setAccount($account);

        // Persist user
        $this->userRepository->add($user);
        $this->persistenceManager->persistAll();
    }
}
