<?php
namespace Ws\User\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Sandstorm\UserManagement\Domain\Model\User as OriginalUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class User extends OriginalUser
{
	/**
	 * @var string
	 * @ORM\Column(nullable=TRUE)
	 */
	protected $groupId;

	/**
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
	 */
	protected $creationDateTime;

	/**
	 * @return string
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param string $groupId
	 */
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDateTime()
	{
		return $this->creationDateTime;
	}

	/**
	 * @param \DateTime $creationDateTime
	 */
	public function setCreationDateTime($creationDateTime)
	{
		$this->creationDateTime = $creationDateTime;
	}
}
