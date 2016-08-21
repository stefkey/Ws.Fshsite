<?php
namespace Ws\FshImport;

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use Ttree\ContentRepositoryImporter\DataType\Slug;

class GroupImporter extends Importer
{
	/**
	 * Import data from the given data provider
	 *
	 * @return void
	 */
	public function process()
	{
		$targetNodeName = $this->options['targetNode'];
		$this->storageNode = $this->siteNode->getNode($targetNodeName);
		if ($this->storageNode === null) {
			$storageNodeTemplate = new NodeTemplate();
			$storageNodeTemplate->setNodeType($this->nodeTypeManager->getNodeType('TYPO3.Neos:Shortcut'));
			$storageNodeTemplate->setProperty('title', $targetNodeName);
			$storageNodeTemplate->setProperty('uriPathSegment', $targetNodeName);
			$storageNodeTemplate->setName($targetNodeName);
			$this->storageNode = $this->siteNode->createNodeFromTemplate($storageNodeTemplate);
		}



		$nodeTemplate = new NodeTemplate();
		$nodeTemplate->setNodeType($this->nodeTypeManager->getNodeType('Ws.Fshsite:Group'));
		$this->processBatch($nodeTemplate);
	}

	/**
	 * @param NodeTemplate $nodeTemplate
	 * @param array $data
	 */
	public function processRecord(NodeTemplate $nodeTemplate, array $data)
	{
		$this->unsetAllNodeTemplateProperties($nodeTemplate);

		$externalIdentifier = $data['__externalIdentifier'];
		$desiredNodeName = Slug::create($data['uriPathSegment'])->getValue();
		$nodeTemplate->setProperty('title', $data['title']);
		$nodeTemplate->setProperty('zip', $data['zip']);
		$nodeTemplate->setProperty('originalIdentifier', $externalIdentifier);
		$nodeTemplate->setHidden($data['_hidden']);

		$node = $this->createUniqueNode($this->storageNode, $nodeTemplate, $desiredNodeName);
		$node->setLastPublicationDateTime($data['_lastModificationDateTime']);

		$this->registerNodeProcessing($node, $externalIdentifier);
	}
}
