<?php

namespace Ws\Fshsite\Http;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\AOP\JoinPointInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Sandstorm\TemplateMailer\Domain\Service\EmailService;
use Sandstorm\UserManagement\Controller\RegistrationController;
use Sandstorm\UserManagement\Domain\Model\RegistrationFlow;
use Sandstorm\UserManagement\Domain\Repository\RegistrationFlowRepository;
use Sandstorm\UserManagement\Domain\Service\UserCreationServiceInterface;


/**
 * @Flow\Aspect
 */
class RegistrationAspect
{

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
     * @Flow\InjectConfiguration(package="Ws.Fshsite", path="fshRegistrationMailRecipient")
     * @var string
     */
    protected $fshRegistrationMailRecipient;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    // Membership states
    const UNKNOWN = 'UNKNOWN';
    const IS_MEMBER = 'IS_MEMBER';
    const NOT_MEMBER = 'NOT_MEMBER';

    /**
     * New registration workflow on top of the existing one.
     * Known errors are handled by the original.
     *
     * @param JoinPointInterface $joinPoint
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Neos\Flow\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @Flow\Around("method(Sandstorm\UserManagement\Controller\RegistrationController->activateAccountAction())")
     */
    public function enhancedRegistrationWorkflow(JoinPointInterface $joinPoint)
    {
        /* @var $registrationFlow RegistrationFlow */
        $registrationFlow = $this->registrationFlowRepository->findOneByActivationToken($joinPoint->getMethodArgument('token'));
        /* @var $registrationController RegistrationController */
        $registrationController = $joinPoint->getProxy();

        // let the original activateAccountAction handle errors
        if (!$registrationFlow || !$registrationFlow->hasValidActivationToken()) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $userMail = $registrationFlow->getEmail();
        $userData = $registrationFlow->getAttributes();
        $userName = $userData['firstName'] . ' ' . $userData['lastName'];
        $isRegistrationForced = isset($userData['activateByForce']);
        $membershipStatus = $this->getPersonMembershipStatus($userMail, $isRegistrationForced);

        if ($membershipStatus === self::IS_MEMBER) {
            // create the user
            $this->userCreationService->createUserAndAccount($registrationFlow);
            $this->registrationFlowRepository->remove($registrationFlow);
            $this->persistenceManager->whitelistObject($registrationFlow);

            $this->sendMail(
                'ActivationSuccessFSH',
                $userName . ': Freischaltung erfolgt',
                [$this->fshRegistrationMailRecipient],
                [
                    'userName' => $userName,
                    'userMail' => $userMail,
                    'memberStatus' => $this->membershipStatusToHumanReadableMsg($membershipStatus)
                ]
            );

            $this->sendMail(
                'ActivationSuccessMember',
                'Ihre Registrierung bei der Frauenselbsthilfe Krebs',
                [$userMail],
                [
                    'userName' => $userName,
                    'userMail' => $userMail,
                    'activationNotice' => $isRegistrationForced ? 'Ihre Registrierung wurde freigeschaltet.' : 'Ihre Registrierung war erfolgreich.'
                ]
            );
        } else {
            /**
             * We want to enable the FSH to activate a new user account with 1 click.
             * We reuse the current registrationFlow and create a new token for it,
             * so the user cannot invoke this action again, but the FSH can.
             * Also we set a 'force' attribute to force the Aspect to
             * create the user this time.
             * This should be safe, as the token is only available for FSH.
             */

            $registrationFlow->generateActivationToken();
            $registrationFlow->setAttributes(array_merge(
                $registrationFlow->getAttributes(),
                ['activateByForce' => true,]
            ));
            $this->registrationFlowRepository->update($registrationFlow);
            $this->persistenceManager->whitelistObject($registrationFlow);

            // create new activation Link
            $activationLink = $registrationController
                ->getControllerContext()
                ->getUriBuilder()
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->uriFor(
                    'activateAccount',
                    [
                        'token' => $registrationFlow->getActivationToken()
                    ],
                    'Registration'
                );

            $this->sendMail(
                'ActivationFailureFSH',
                 $userName . ': Registrierungsanfrage',
                [$this->fshRegistrationMailRecipient],
                [
                    'userName' => $userName,
                    'userMail' => $userMail,
                    'activationLink' => $activationLink,
                    'memberStatus' => $this->membershipStatusToHumanReadableMsg($membershipStatus)
                ]
            );

            $this->sendMail(
                'ActivationFailureMember',
                'Ihre Registrierung bei der Frauenselbsthilfe Krebs',
                [$userMail],
                [
                    'userName' => $userName,
                    'userMail' => $userMail,
                ]
            );
        }
    }

    /**
     * @param $userMail string
     * @param $isForced bool
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getPersonMembershipStatus($userMail, $isForced)
    {
        // no need to query the db if the registration is forced
        if ($isForced) {
            return self::IS_MEMBER;
        }

        // connect to member DB
        $this->connection = DriverManager::getConnection($this->databaseSettings);
        $this->connection->connect();
        /**
         * This query returns:
         * 1 - if a person with that email exists and p_mitglied is 1
         * 0 - if a person with that email exists and p_mitglied is 0
         * false - if no person with that email exists
         */
        $result = $this->connection->fetchColumn(
            "SELECT p_mitglied " .
            "FROM pkommunikation " .
            "INNER JOIN personen ON k_p_id = p_id " .
            "WHERE k_number = '" . $userMail . "'"
        );
        $this->connection->close();

        switch (true) {
            case $result === '1':
                return self::IS_MEMBER;
            case $result === '0':
                return self::NOT_MEMBER;
            default:
                return self::UNKNOWN;
        }
    }

    private function sendMail($templateName, $subject, array $recipients, array $variables)
    {
        $this->emailService->sendTemplateEmail(
            $templateName,
            $subject,
            $recipients,
            $variables,
            'sandstorm_usermanagement_sender_email',
            [], // cc
            [], // bcc
            [], // attachments
            'sandstorm_usermanagement_replyTo_email'
        );
    }

    private function membershipStatusToHumanReadableMsg($status)
    {
        switch ($status) {
            case self::UNKNOWN:
                return 'Person ist unbekannt oder mit einer anderen E-Mail-Adresse in der FSH DB eingetragen';
            case self::IS_MEMBER:
                return 'Person ist Mitglied';
            case self::NOT_MEMBER:
                return 'Person ist in Mitglieder DB, aber kein Mitglied';
            default:
                throw new \Exception(
                    'Unknown MembershipStatus: "' . $status .
                    '"! Expected one of ' .
                    [self::UNKNOWN, self::IS_MEMBER, self::NOT_MEMBER]
                );
        }
    }
}
