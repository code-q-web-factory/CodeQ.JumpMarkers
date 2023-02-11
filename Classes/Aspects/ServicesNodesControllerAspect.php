<?php
namespace CodeQ\JumpMarkers\Aspects;

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;

/**
 * Aspect filter only SectionConfigurations with a jumpMarkerTitle or sectionId as possible linking targets.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ServicesNodesControllerAspect
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

    /**
     * @Flow\Around("method(Neos\Neos\Controller\Service\NodesController->indexAction())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     * @throws PropertyNotAccessibleException|NodeException
     */
    public function filterSectionConfigurationsWithoutTitleAndId(JoinPointInterface $joinPoint)
    {
        [
            'searchTerm' => $searchTerm,
            'nodeIdentifiers' => $nodeIdentifiers,
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'nodeTypes' => $nodeTypes,
            'contextNode' => $contextNode,
        ] = $joinPoint->getMethodArguments();

        if($nodeIdentifiers !== []
            || array_search('CodeQ.JumpMarkers:Mixin.SectionConfiguration', $nodeTypes) == false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        // filter
        $defaultSearchableNodeTypeNames = [];
        foreach ($nodeTypes as $nodeTypeName) {
            if($nodeTypeName == 'CodeQ.JumpMarkers:Mixin.SectionConfiguration') {
                continue;
            }
            if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
                // let the controller throw an exception
                return $joinPoint->getAdviceChain()->proceed($joinPoint);
            }

            $defaultSearchableNodeTypeNames[$nodeTypeName] = $nodeTypeName;
            foreach ($this->nodeTypeManager->getSubNodeTypes($nodeTypeName, false) as $subNodeTypeName => $subNodeType) {
                $defaultSearchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
            }
        }

        $sectionConfigurationSearchableNodeTypeNames = [];
        foreach ($this->nodeTypeManager->getSubNodeTypes('CodeQ.JumpMarkers:Mixin.SectionConfiguration', false) as $subNodeTypeName => $subNodeType) {
            $sectionConfigurationSearchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
        }

        $contentContext = $this->createContentContext($workspaceName, $dimensions);
        $searchableNodeTypeNames = array_merge($defaultSearchableNodeTypeNames, $sectionConfigurationSearchableNodeTypeNames);
        $nodes = array_filter(
            $this->nodeSearchService->findByProperties($searchTerm, $searchableNodeTypeNames, $contentContext, $contextNode),
            function($node) use ($defaultSearchableNodeTypeNames) {
                return array_search($node->getNodeType(), $defaultSearchableNodeTypeNames) != false
                    || $node->getProperty('jumpMarkerTitle') || $node->getProperty('sectionId');
            }
        );

        $view = ObjectAccess::getProperty($joinPoint->getProxy(), 'view', true);
        $view->assign('nodes', $nodes);
    }
}
