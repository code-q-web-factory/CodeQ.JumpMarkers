<?php

namespace CodeQ\JumpMarkers\Aspects;

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Aop\JoinPointInterface;
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
    #[Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
    /**
     * @Flow\Around("method(Neos\Neos\Controller\Service\NodesController->indexAction())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     * @throws PropertyNotAccessibleException
     */
    public function filterSectionConfigurationsWithoutTitleAndId(JoinPointInterface $joinPoint): string
    {
        /** @var string $searchTerm */
        $searchTerm = $joinPoint->getMethodArgument('searchTerm');
        /** @var array $nodeIds */
        $nodeIds = $joinPoint->getMethodArgument('nodeIds');
        /** @var array $nodeTypes */
        $nodeTypes = $joinPoint->getMethodArgument('nodeTypes');
        /** @var Node $contextNode */
        $contextNode = $joinPoint->getMethodArgument('contextNode');
        $contentRepository = $this->contentRepositoryRegistry->get($contextNode->contentRepositoryId);

        if ($nodeIds !== []
            || !in_array('CodeQ.JumpMarkers:Mixin.SectionConfiguration', $nodeTypes, true)
        ) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        // filter
        $defaultSearchableNodeTypeNames = [];
        foreach ($nodeTypes as $nodeTypeName) {
            if ($nodeTypeName === 'CodeQ.JumpMarkers:Mixin.SectionConfiguration') {
                continue;
            }
            if (!$contentRepository->getNodeTypeManager()->hasNodeType($nodeTypeName)) {
                // let the controller throw an exception
                return $joinPoint->getAdviceChain()->proceed($joinPoint);
            }

            $defaultSearchableNodeTypeNames[$nodeTypeName] = $nodeTypeName;
            foreach ($contentRepository->getNodeTypeManager()->getSubNodeTypes($nodeTypeName, false) as $subNodeTypeName => $subNodeType) {
                $defaultSearchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
            }
        }

        $sectionConfigurationSearchableNodeTypeNames = [];
        foreach ($contentRepository->getNodeTypeManager()->getSubNodeTypes('CodeQ.JumpMarkers:Mixin.SectionConfiguration', false) as $subNodeTypeName => $subNodeType) {
            $sectionConfigurationSearchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
        }

        $searchableNodeTypeNames = array_merge($defaultSearchableNodeTypeNames, $sectionConfigurationSearchableNodeTypeNames);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
        $nodes = $subgraph->findDescendantNodes(
            $contextNode->aggregateId,
            FindDescendantNodesFilter::create(
                nodeTypes: NodeTypeCriteria::create(
                    NodeTypeNames::fromStringArray($searchableNodeTypeNames),
                    NodeTypeNames::createEmpty()
                ),
                searchTerm: $searchTerm
            )
        );
        $filteredNodes = $nodes->filter(static function ($node) use ($defaultSearchableNodeTypeNames) {
            return in_array($node->getNodeType(), $defaultSearchableNodeTypeNames, true)
                || $node->getProperty('jumpMarkerTitle') || $node->getProperty('sectionId');
        });

        $view = ObjectAccess::getProperty($joinPoint->getProxy(), 'view', true);
        $view->assign('nodes', $filteredNodes);

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
