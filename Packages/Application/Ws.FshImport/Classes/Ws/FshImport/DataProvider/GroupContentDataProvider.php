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
				$entry = [];
				$entry['_type'] = 'TYPO3.Neos.NodeTypes:Headline';
				$entry['title'] = strip_tags($value);
				$nodes = [$entry];
			} else if ($record['idtype'] == 2) {
				$nodes = $this->parseHtml($value);
			}

			$result[] = [
				'__externalIdentifier' => (int)$record['idcontent'],
				'__parentIdentifier' => (int)$record['idartlang'],
				'type' => (int)$record['idtype'],
				'main' => $nodes
			];
		}
		$this->count = count($result);
		return $result;
	}

	private function DomElementToHtml($element) {
		$tempDom = new \DOMDocument();
		$clonedNode = $element->cloneNode(true);
		$tempDom->appendChild($tempDom->importNode($clonedNode, true));
		return html_entity_decode(trim($tempDom->saveHTML()));
	}

	private function parseHtml($html) {
		if (!$html) {
			return null;
		}
		$dom = new \DOMDocument;
		// Ignore warnings
		libxml_use_internal_errors(true);
		// Fix encoding issues
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
		libxml_use_internal_errors(false);
		$xpath = new \DomXPath($dom);

		// Remove style from span tags
		$items = $xpath->query("//*[@style]");
		foreach($items as $item) {
			$item->removeAttribute("style");
		}

		// TODO:
		// B->strong, em->i
		// edgecases!

		$pointer = 0;
		$nodes = [];

		// Iterate over all top-level nodes
		foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $element) {
			// TODO: handel multiple assets in one paragraph
			if ($asset = $xpath->query(".//a[starts-with(@href, 'upload/')]", $element)->item(0)) {
				$pointer++;
				$nodes[$pointer] = array(
					'_type' => 'TYPO3.Neos.NodeTypes:Asset',
					'asset' => $asset->getAttribute('href'),
					'description' => $asset->nodeValue
				);
			} else if ($img = $xpath->query(".//img[starts-with(@src, 'upload/')]", $element)->item(0)) {
				$pointer++;
				$nodes[$pointer] = array(
					'_type' => 'TYPO3.Neos.NodeTypes:Image',
					'image' => $img->getAttribute('src'),
					'alt' => $img->getAttribute('alt'),
					'floated' => $img->getAttribute('class') === 'bild_links'
				);
			} else {
				$text = $this->DomElementToHtml($element);
				if ($text) {
					$prevValue = '';
					if (isset($nodes[$pointer])) {
						if ($nodes[$pointer]['_type'] === 'TYPO3.Neos.NodeTypes:Text') {
							$prevValue = $nodes[$pointer]['text'];
						} else {
							$pointer++;
						}
					}
					$nodes[$pointer] = array(
						'_type' => 'TYPO3.Neos.NodeTypes:Text',
						'text' => $prevValue . $text
					);
				}
			}
		}
		return $nodes;
	}
}
