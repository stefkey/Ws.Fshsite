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
     * @Flow\InjectConfiguration(path="rolesForNewUsers")
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
        // Create the account
        $account = new \TYPO3\Flow\Security\Account();
        $account->setAccountIdentifier($registrationFlow->getEmail());
        $account->setCredentialsSource($registrationFlow->getEncryptedPassword());
        $account->setAuthenticationProviderName('Sandstorm.UserManagement:Login');
        $account->addRole(new Role('Flowpack.Neos.FrontendLogin:FSH_Mitglied'));

        $attributes = $registrationFlow->getAttributes();
        $group = isset($attributes['group']) ? $attributes['group'] : '';
        // Create the user
        $user = new User();
        $user->setFirstName($registrationFlow->getFirstName());
        $user->setLastName($registrationFlow->getLastName());
        $user->setEmail($registrationFlow->getEmail());
        $user->setGroup($group);
        $user->setAccount($account);

        // Persist user
        $this->userRepository->add($user);
        $this->persistenceManager->persistAll();
    }
}
