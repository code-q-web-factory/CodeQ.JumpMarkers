<?php
namespace CodeQ\JumpMarkers\Aspects;

use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * Aspect to generate the Uri to a content node as a link to its parent document node while attaching a Uri fragment.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ContentNodeUriAspect
{
    /**
     * @Flow\Around("method(Neos\Flow\Mvc\Routing\UriBuilder->uriFor())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     * @throws Exception
     * @throws NodeException
     * @return string the rendered URI
     */
    public function rewriteContentNodeUris(JoinPointInterface $joinPoint)
    {
        /** @var ActionRequest $request */
        $request = $joinPoint->getProxy()->getRequest();
        $arguments = $joinPoint->getMethodArguments();

        $contentNode = $arguments['controllerArguments']['node'] ?? null;
        if (!$request->getMainRequest()->hasArgument('node') || !$contentNode instanceof Node
            || !$contentNode->getNodeType()->isOfType('CodeQ.JumpMarkers:Mixin.SectionConfiguration')
            || (!$contentNode->hasProperty('jumpMarkerTitle') && $contentNode->hasProperty('sectionId'))) {
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
     * @param NodeInterface $pageNode
     * @param NodeInterface $contentNode
     * @return string $uri
     * @throws NodeException
     */
    public function generateUriForNode(JoinPointInterface $joinPoint, NodeInterface $pageNode, NodeInterface $contentNode)
    {
        /** @var UriBuilder $uriBuilder */
        $arguments = $joinPoint->getMethodArguments();
        $uriBuilder = $joinPoint->getProxy();
        $originalSection = $uriBuilder->getSection();

        // generate the uri for the page node with the content node as uri fragment
        $arguments['controllerArguments']['node'] = $pageNode;
        $joinPoint->setMethodArgument('controllerArguments', $arguments['controllerArguments']);
        $joinPoint->getProxy()->setSection($contentNode->getProperty('sectionId'));

        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        // restore the content node
        $arguments['controllerArguments']['node'] = $contentNode;
        $joinPoint->setMethodArgument('controllerArguments', $arguments['controllerArguments']);
        $joinPoint->getProxy()->setSection($originalSection);

        return $result;
    }
}
