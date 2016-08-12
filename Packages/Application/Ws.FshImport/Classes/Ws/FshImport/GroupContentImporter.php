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
		// $this->log(print_r($data, 1));

		$this->unsetAllNodeTemplateProperties($nodeTemplate);

		$externalIdentifier = $data['__externalIdentifier'];
		if ($this->skipNodeProcessing($externalIdentifier, '123', $this->siteNode, false)) {
			return null;
		}

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
			case 'TYPO3.Neos.NodeTypes:Headline':
				$nodeTemplate->setProperty('title', $contentItem['title']);
				break;
			case 'TYPO3.Neos.NodeTypes:Text':
				$nodeTemplate->setProperty('text', $contentItem['text']);
				break;
		}
		return $nodeTemplate;
	}

	protected function getFilePath($fileName) {
		if (in_array(pathinfo(strtolower($fileName), PATHINFO_EXTENSION), ["jpg", "jpeg", "gif", "png"])) {
			$filePath = FLOW_PATH_ROOT . 'uploads/' . $fileName;
			if (!file_exists($filePath)) {
				$this->log("Missing file: " . $filePath);
			} else {
				return $filePath;
			}
		} else {
			$this->log("Illegal mediaItem file extension of file: " . $fileName);
			return null;
		}
	}

	protected function importImage($filename) {
		$resource = $this->resourceManager->importResource($filename);

		$image = new Image($resource);
		$this->imageRepository->add($image);

		$processingInstructions = [];
		return $this->objectManager->get('TYPO3\Media\Domain\Model\ImageVariant', $image, $processingInstructions);
	}
}
