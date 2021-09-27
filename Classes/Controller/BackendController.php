<?php

namespace CodeQ\JumpMarkers\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\Service\LinkingService;
use Neos\Neos\TypeConverter\NodeConverter;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class BackendController extends ActionController
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * Allow invisible nodes to be previewed
     *
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeNodeToUriAction(): void
    {
        if ($this->arguments->hasArgument('node') && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption(NodeConverter::class, NodeConverter::INVISIBLE_CONTENT_SHOWN, true);
        }
    }

    /**
     * Get an uri to a link that might now be live yet.
     *
     * @param NodeInterface|null $node
     * @return void
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws \Neos\Neos\Exception
     * @Flow\IgnoreValidation("node")
     */
    public function nodeToUriAction(NodeInterface $node = null)
    {
        exit('wanting to see this message');
        if ($node === null) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
        }
        exit('part one');

        print_r($this->linkingService->createNodeUri($this->controllerContext, $node, null, null, true));
        //print_r($this->linkingService->resolveNodeUri($matches[0], $node, $controllerContext, $absolute);
        exit();
    }
}
