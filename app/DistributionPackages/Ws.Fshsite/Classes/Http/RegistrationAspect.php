<?php

namespace Ws\Fshsite\Http;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Sandstorm\TemplateMailer\Domain\Service\EmailService;
use Sandstorm\UserManagement\Domain\Repository\RegistrationFlowRepository;
use Sandstorm\UserManagement\Domain\Service\UserCreationServiceInterface;


/**
 * @Flow\Aspect
 */
class RegistrationAspect
{

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var RegistrationFlowRepository
     */
    protected $registrationFlowRepository;

    /**
     * @Flow\Inject
     * @var UserCreationServiceInterface
     */
    protected $userCreationService;

    /**
     * @Flow\Inject
     * @var EmailService
     */
    protected $emailService;

    /**
     * @Flow\InjectConfiguration(package="Ws.Fshsite", path="memberDatabase")
     * @var array
     */
    protected $databaseSettings;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Log a message if a post is deleted
     *
     * @param \Neos\Flow\AOP\JoinPointInterface $joinPoint
     * @Flow\Around("method(Sandstorm\UserManagement\Controller\RegistrationController->activateAccountAction())")
     * @return void
     */
    public function logRegistration(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        /* @var $registrationFlow \Sandstorm\UserManagement\Domain\Model\RegistrationFlow */
        $registrationFlow = $this->registrationFlowRepository->findOneByActivationToken($joinPoint->getMethodArgument('token'));
        if (!$registrationFlow) {
            //TODO
            return;
        }

        if (!$registrationFlow->hasValidActivationToken()) {
            //TODO
            return;
        }

        $userMail = $registrationFlow->getEmail();
        if (!$userMail) {
            //TODO
            return;
        }

        $this->connection = DriverManager::getConnection($this->databaseSettings);
        $this->connection->connect();
        $result = $this->connection->fetchArray("SELECT k_p_id FROM pkommunikation WHERE k_number = '" . $userMail . "'");
        if (is_array($result) && !empty($result)) {
            $member = $this->connection->fetchArray("SELECT p_nachname FROM personen WHERE p_id ='" . $result[0] . "'");
            $subject = 'Berechtigt: ' . $member[0] . ', ' . $userMail;

            $this->emailService->sendTemplateEmail(
                'ActivationSuccessMember',
                $subject,
                [$userMail],
                [
                    'member' => $member
                ],
                'sandstorm_usermanagement_sender_email',
                [], // cc
                [], // bcc
                [], // attachments
                'sandstorm_usermanagement_replyTo_email'
            );

            $this->userCreationService->createUserAndAccount($registrationFlow);
            $this->registrationFlowRepository->remove($registrationFlow);
            $this->persistenceManager->whitelistObject($registrationFlow);
        } else {
            //TODO no match
        }
        $this->connection->close();
    }

}
