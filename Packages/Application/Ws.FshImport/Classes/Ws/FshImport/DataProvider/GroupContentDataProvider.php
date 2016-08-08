<?php
namespace Ws\FshImport\DataProvider;

use Ttree\ContentRepositoryImporter\DataProvider\DataProvider;
use Ttree\ContentRepositoryImporter\DataType\StringValue;

class GroupContentDataProvider extends DataProvider {
	/**
	 * @return array
	 */
	public function fetch() {
		$result = [];

		$query = $this->createQuery()
		->select('*')
		->from('con_content', 'n');
		$statement = $query->execute();

		while ($record = $statement->fetch()) {
			$value = html_entity_decode(urldecode($record['value']));
			if ($record['idtype'] == 1) {
				$entry['_type'] = 'TYPO3.Neos.NodeTypes:Headline';
				$entry['text'] = $value;
			} else if ($record['idtype'] == 2) {
				$entry['_type'] = 'TYPO3.Neos.NodeTypes:Text';
				$entry['title'] = $value;
			}
			$result[] = [
				'__externalIdentifier' => (int)$record['idcontent'],
				'__parentIdentifier' => (int)$record['idartlang'],
				'type' => (int)$record['idtype'],
				'main' => [$entry]
			];
		}
		$this->count = count($result);
		return $result;
	}
}
