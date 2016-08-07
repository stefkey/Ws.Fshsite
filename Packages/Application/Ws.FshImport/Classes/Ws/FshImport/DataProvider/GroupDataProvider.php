<?php
namespace Ws\FshImport\DataProvider;

use Ttree\ContentRepositoryImporter\DataProvider\DataProvider;
use Ttree\ContentRepositoryImporter\DataType\StringValue;

class GroupDataProvider extends DataProvider {
	/**
	 * @return array
	 */
	public function fetch() {
		$result = [];

		$sql = "SELECT a.* FROM con_art_lang as a JOIN con_cat_art as c ON a.idart=c.idart WHERE c.idcat=69;";
		$statement = $this->getDatabaseConnection()->prepare($sql);
		$statement->execute();

		while ($record = $statement->fetch()) {
			$created = new \DateTime();
			$created->setTimestamp(strtotime($record['created']));
			$lastmodified = new \DateTime();
			$lastmodified->setTimestamp(strtotime($record['lastmodified']));
			$published = new \DateTime();
			$published->setTimestamp(strtotime($record['published']));

			$result[] = [
				'__externalIdentifier' => (int)$record['idart'],
				'_hidden' => $record['online'] ? false : true,
				'_creationDateTime' => $created,
				'_lastModificationDateTime' => $lastmodified,
				'_lastPublicationDateTime' => $published,
				'title' => StringValue::create($record['title'])->getValue(),
				'uriPathSegment' => StringValue::create(strtolower($record['urlname']))->getValue(),
				'zip' => $record['summary']
			];
		}
		$this->count = count($result);
		return $result;
	}
}
