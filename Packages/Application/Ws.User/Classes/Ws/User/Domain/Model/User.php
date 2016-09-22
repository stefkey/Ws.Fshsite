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
	protected $group;

	/**
	 * @return string
	 */
	public function getGroup()
	{
			return $this->group;
	}

	/**
	 * @param string $group
	 */
	public function setGroup($group)
	{
			$this->group = $group;
	}
}
