<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * User migration
 */
class Version20160922095236 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE ws_user_domain_model_user (persistence_object_identifier VARCHAR(40) NOT NULL, `group` VARCHAR(255) DEFAULT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ws_user_domain_model_user ADD CONSTRAINT FK_983C7A6247A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES sandstorm_usermanagement_domain_model_user (persistence_object_identifier) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE ws_user_domain_model_user');
    }
}
