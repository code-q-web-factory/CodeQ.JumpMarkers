<?php

namespace CodeQ\JumpMarkers\Aspects;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\Exception;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * Aspect to generate the Uri to a content node as a link to its parent document node while attaching a Uri fragment.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ContentNodeUriAspect
{
    #[Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
    /**
     * @Flow\Around("method(Neos\Flow\Mvc\Routing\UriBuilder->uriFor())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     * @return string the rendered URI
     * @throws Exception
     */
    public function rewriteContentNodeUris(JoinPointInterface $joinPoint): string
    {
        /** @var UriBuilder $proxy */
        $proxy = $joinPoint->getProxy();
        $request = $proxy->getRequest();
        $arguments = $joinPoint->getMethodArguments();

        if (!$request->getMainRequest()->hasArgument('node')) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $contentNode = $arguments['controllerArguments']['node'] ?? null;
        if (!$contentNode instanceof Node) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $contentRepository = $this->contentRepositoryRegistry->get($contentNode->contentRepositoryId);
        if ((!$contentNode->getProperty('jumpMarkerTitle') && !$contentNode->getProperty('sectionId'))
            || !$contentRepository->getNodeTypeManager()->getNodeType($contentNode->nodeTypeName)?->isOfType('CodeQ.JumpMarkers:Mixin.SectionConfiguration')) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        // SectionConfiguration nodes with a title or id only
        $q = new FlowQuery([$contentNode]);
        $pageNode = $q->closest('[instanceof Neos.Neos:Document]')->get(0);
        return $this->generateUriForNode($joinPoint, $pageNode, $contentNode);
    }

    /**
     * This method generates the Uri through the joinPoint with
     * temporary overriding the used node
     *
     * @param JoinPointInterface $joinPoint The current join point
     * @param Node $pageNode
     * @param Node $contentNode
     * @return string $uri
     */
    public function generateUriForNode(JoinPointInterface $joinPoint, Node $pageNode, Node $contentNode): string
    {
        /** @var UriBuilder $uriBuilder */
        $arguments = $joinPoint->getMethodArguments();
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $joinPoint->getProxy();
        $originalSection = $uriBuilder->getSection();

        // generate the uri for the page node with the content node as uri fragment
        $arguments['controllerArguments']['node'] = $pageNode;
        $joinPoint->setMethodArgument('controllerArguments', $arguments['controllerArguments']);
        $uriBuilder->setSection($contentNode->getProperty('sectionId'));

        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        // restore the content node
        $arguments['controllerArguments']['node'] = $contentNode;
        $joinPoint->setMethodArgument('controllerArguments', $arguments['controllerArguments']);
        $uriBuilder->setSection($originalSection);

        return $result;
    }
}
