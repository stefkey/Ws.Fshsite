<?php
namespace Ws\FshImport;

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use Ttree\ContentRepositoryImporter\DataType\Slug;

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\TagRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

class GroupContentImporter extends Importer
{
	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @Flow\Inject
	 * @var TagRepository
	 */
	protected $tagRepository;

	/**
	 * @Flow\Inject
	 * @var AssetRepository
	 */
	protected $assetRepository;

	public function process()
	{
		$nodeTemplate = new NodeTemplate();
		$this->processBatch($nodeTemplate);
	}
	/**
	 * @param NodeTemplate $nodeTemplate
	 * @param array $data
	 * @return NodeInterface
	 */
	public function processRecord(NodeTemplate $nodeTemplate, array $data)
	{
		if ($data['__parentIdentifier'] == 113) {
			//$this->log(print_r($data, 1));
		}
		//return;
		$this->unsetAllNodeTemplateProperties($nodeTemplate);

		$externalIdentifier = $data['__externalIdentifier'];

		$groupRecordMapping = $this->processedNodeService->get('Ws\FshImport\GroupImporter', $data['__parentIdentifier']);
		if ($groupRecordMapping === null) {
			$this->log(sprintf('Skip "%s", missing node', $data['__parentIdentifier']), LOG_ERR);
			return null;
		}
		$groupNode = $this->siteNode->getNode($groupRecordMapping->getNodePath());

		if (is_array($data['main'])) {
			$mainCollection = $groupNode->getNode('main');
			foreach ($data['main'] as $contentItem) {
				$nodeTemplate = $this->processContentItem($contentItem);
				$mainCollection->createNodeFromTemplate($nodeTemplate);
			}
		}

		$this->registerNodeProcessing($groupNode, $externalIdentifier);
	}

	protected function processContentItem($contentItem) {
		$nodeTemplate = new NodeTemplate();
		$nodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($contentItem['_type']));
		switch ($contentItem['_type']) {
			case 'TYPO3.Neos.NodeTypes:Image':
				$asset = $this->downloadAndImportImage($contentItem['image']);
				$nodeTemplate->setProperty('image', $asset);
				$nodeTemplate->setProperty('alternativeText', $contentItem['alt']);
				$nodeTemplate->setProperty('floated', $contentItem['floated']);
				break;
			case 'TYPO3.Neos.NodeTypes:Headline':
				$nodeTemplate->setProperty('title', $contentItem['title']);
				break;
			case 'TYPO3.Neos.NodeTypes:Text':
				$html = $this->replaceResourceLinks($contentItem['text']);
				$nodeTemplate->setProperty('text', $html);
				break;
		}
		return $nodeTemplate;
	}

	protected function replaceResourceLinks($html) {
		$dom = new \DOMDocument;
		// Ignore warnings
		libxml_use_internal_errors(true);
		// Fix encoding issues
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
		libxml_use_internal_errors(false);
		$xpath = new \DomXPath($dom);

		// Remove style from span tags
		$items = $xpath->query("//a[starts-with(@href, 'upload/')]");
		foreach($items as $item) {
			$fileUrl = $item->getAttribute('href');
			$asset = $this->downloadAndImportFile($fileUrl);
			$item->setAttribute('href', 'asset://' . $asset->getIdentifier());
		}
		return $dom->saveHTML();
	}

	protected function importResource($url) {
		$filePath = FLOW_PATH_ROOT . 'assetsCache/' . $url;
		// Cache file in local folder
		if (!file_exists($filePath)) {
			if (!file_exists(dirname($filePath))) {
				mkdir(dirname($filePath), 0777, true);
			}
			$fullUrl = "http://www.frauenselbsthilfe.de/" . $url;
			file_put_contents($filePath, fopen($fullUrl, 'r'));
		}
		$resource = $this->resourceManager->importResource($filePath);
		return $resource;
	}

	protected function downloadAndImportFile($url) {
		$resource = $this->importResource($url);
		$asset = new Asset($resource);

		foreach(explode(",", $this->options['assetTags']) as $tagLabel) {
			/** @var Tag $tag */
			$tag = $this->tagRepository->findOneByLabel(trim($tagLabel));
			$asset->addTag($tag);
		}
		$this->assetRepository->add($asset);
		return $asset;
	}

	protected function downloadAndImportImage($url) {
		if (!in_array(pathinfo(strtolower($url), PATHINFO_EXTENSION), ["jpg", "jpeg", "gif", "png"])) {
			$this->log("Illegal image extenstion: " . $url, LOG_ERR);
			return null;
		}

		$resource = $this->importResource($url);
		$image = new Image($resource);

		foreach(explode(",", $this->options['imageTags']) as $tagLabel) {
			/** @var Tag $tag */
			$tag = $this->tagRepository->findOneByLabel(trim($tagLabel));
			$image->addTag($tag);
		}
		$this->imageRepository->add($image);
		$processingInstructions = [];
		return $this->objectManager->get('TYPO3\Media\Domain\Model\ImageVariant', $image, $processingInstructions);
	}
}
