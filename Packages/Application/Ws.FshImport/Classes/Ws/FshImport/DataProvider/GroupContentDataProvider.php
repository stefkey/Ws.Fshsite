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
		->from('con_content', 'n')
		->orderBy('n.idtype');
		$statement = $query->execute();

		while ($record = $statement->fetch()) {
			// Decode weirdly-encoded content
			$value = html_entity_decode(urldecode($record['value']));
            // SJ Remove String from title
            $value = str_replace('Frauenselbsthilfe nach Krebs: ', '', $value);
			if ($record['idtype'] == 1) {
				$entry = [];
				$entry['_type'] = 'TYPO3.Neos.NodeTypes:Headline';
				$entry['title'] = '<h1>' . strip_tags($value) . '</h1>';
				$nodes = [$entry];
			} else if ($record['idtype'] == 2) {
				$nodes = $this->parseHtml($value);
			}

			$result[] = [
				'__externalIdentifier' => (int)$record['idcontent'],
				'__parentIdentifier' => (int)$record['idartlang'],
				'type' => (int)$record['idtype'],
				'main' => $nodes,
				'source' => $value
			];
		}
		$this->count = count($result);
		return $result;
	}

	protected function domElementToHtml($element) {
		$tempDom = new \DOMDocument();
		$clonedNode = $element->cloneNode(true);
		$tempDom->appendChild($tempDom->importNode($clonedNode, true));
		return html_entity_decode(trim($tempDom->saveHTML()));
	}

	protected function domElementInnerHtml(\DOMElement $element) {
		$innerHTML = "";
		$children  = $element->childNodes;

		foreach ($children as $child) {
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}

		return $innerHTML;
	}

	protected function parseHtml($html) {
		if (!$html) {
			return null;
		}

		// Replace b and i tags
		$html = str_replace('<b>', '<strong>', $html);
		$html = str_replace('</b>', '</strong>', $html);
		$html = str_replace('<i>', '<em>', $html);
		$html = str_replace('</i>', '</em>', $html);

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

		// SJ Remove class from a tags
		$items = $xpath->query("//a[@class]");
		foreach($items as $item) {
			$item->removeAttribute("class");
		}

		// Remove "pdf" class
		$items = $xpath->query("//*[contains(@class, 'pdf')]");
		foreach($items as $item) {
			$item->removeAttribute("class");
		}

		// p.headline -> h3
		$headings = $xpath->query("//p[contains(@class, 'headline')]");
		foreach($headings as $item) {
			$newNode = $dom->createElement('h3', $item->nodeValue);
			$item->parentNode->replaceChild($newNode, $item);
			$item->removeAttribute("style");
		}


		// SJ span.headline -> h3
		$headings = $xpath->query("//span[contains(@class, 'headline')]");
		foreach($headings as $item) {
			$newNode = $dom->createElement('h3', $item->nodeValue);
			$item->parentNode->replaceChild($newNode, $item);
			$item->removeAttribute("style");
		}

		// SJ Remove br tags previous and following of h3
		$items = $xpath->query("//h3/preceding-sibling::node()[position() < 10][self::br] | //h3/following-sibling::node()[position() < 10][self::br]");
		foreach($items as $item) {
			$item->parentNode->removeChild($item);
		}

		// Remove strong or span tags that contain links
		while($item = $xpath->query("//a[parent::strong or parent::span]")[0]) {
			$innerHtml = $this->domElementInnerHtml($item->parentNode);
			$newNode = $dom->createTextNode($innerHtml);
			$item->parentNode->parentNode->replaceChild($newNode, $item->parentNode);
		}

		// Iterate over all top-level nodes, and split them into Image nodes and Text nodes
		$pointer = 0;
		$nodes = [];

		// Iterate over all top-level nodes
		foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $element) {
			// Find all images
			foreach($xpath->query(".//img[starts-with(@src, 'upload/')]", $element) as $img) {
				$pointer++;
				$nodes[$pointer] = array(
					'_type' => 'TYPO3.Neos.NodeTypes:Image',
					'image' => $img->getAttribute('src'),
					'alt' => $img->getAttribute('alt'),
					'floated' => $img->getAttribute('class') === 'bild_links'
				);
				// Remove image tag of newly added image
				$img->parentNode->removeChild($img);
			}

			$text = trim($this->domElementToHtml($element));

			// Hardcoded regexp to remove empty tags, will do for now
			$text = preg_replace('/<span[^>]*>[\s\xC2\xA0]*<\/span>/siu', '', $text);
			$text = preg_replace('/<p[^>]*>[\s\xC2\xA0]*<\/p>/i', '', $text);
			$text = preg_replace('/<h3[^>]*>[\s\xC2\xA0]*<\/h3>/i', '', $text);

			// If there's any text left, append it as a Text node
			if ($text) {
				$prevValue = '';
				if (isset($nodes[$pointer])) {
					// If previous element is of type Text, then merge with it, if not increment the pointer
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
		return $nodes;
	}
}
