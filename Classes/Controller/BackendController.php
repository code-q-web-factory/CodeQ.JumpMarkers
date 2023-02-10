<?php

namespace CodeQ\JumpMarkers\Controller;

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Eel\FlowQuery\FlowQuery;
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
     * Get an uri to a link that might not be live yet.
     *
     * The API is very lazily build, mainly focusing on the best case.
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
     * @throws \Neos\Eel\Exception
     * @Flow\IgnoreValidation("node")
     */
    public function nodeToUriAction(NodeInterface $node = null)
    {
        if ($node === null) {
            exit(json_encode([
                'success' => false,
                'message' => 'The requested node does not exist or isn\'t accessible to the current user.'
            ]));
        }

        $flowQuery = new FlowQuery([$node]);
        $flowQuery->pushOperation('context', [['workspaceName' => 'live']]);
        if($flowQuery->count() == 0) {
            exit(json_encode([
                'success' => false,
                'message' => 'The requested pages does not exist or isn\'t published yet.'
            ]));
        }
        $publicNode = $flowQuery->get(0);

        if(!$publicNode->isVisible()) {
            exit(json_encode([
                'success' => false,
                'message' => 'The requested page is not visible to public visitors.'
            ]));
        }

        exit(json_encode([
            'success' => true,
            'uri' => $this->linkingService->createNodeUri($this->controllerContext, $publicNode, null, null, true)
        ]));
    }
}
