<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 */
namespace Smile\DebugToolbar\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Smile\DebugToolbar\Block\ToolbarFactory;
use Smile\DebugToolbar\Block\Toolbar;
use Smile\DebugToolbar\Block\ToolbarsFactory;
use Smile\DebugToolbar\Block\Toolbars;
use Smile\DebugToolbar\Helper\Data   as HelperData;
use Smile\DebugToolbar\Helper\Config as HelperConfig;

/**
 * Observer Add the Toolbar
 *
 * @author    Laurent MINGUET <lamin@smile.fr>
 * @copyright 2017 Smile
 * @SuppressWarnings("PMD.CouplingBetweenObjects")
 */
class AddToolbar implements ObserverInterface
{
    /**
     * @var ToolbarFactory
     */
    protected $blockToolbarFactory;

    /**
     * @var ToolbarsFactory
     */
    protected $blockToolbarsFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperConfig
     */
    protected $helperConfig;

    /**
     * AddToolbar constructor.
     *
     * @param ToolbarFactory  $blockToolbarFactory
     * @param ToolbarsFactory $blockToolbarsFactory
     * @param HelperData      $helperData
     * @param HelperConfig    $helperConfig
     */
    public function __construct(
        ToolbarFactory  $blockToolbarFactory,
        ToolbarsFactory $blockToolbarsFactory,
        HelperData      $helperData,
        HelperConfig    $helperConfig
    ) {
        $this->blockToolbarFactory  = $blockToolbarFactory;
        $this->blockToolbarsFactory = $blockToolbarsFactory;
        $this->helperData           = $helperData;
        $this->helperConfig         = $helperConfig;
    }

    /**
     * Execute the observer
     *
     * @param Observer $observer Magento Observer Object
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperConfig->isEnabled()) {
            return;
        }

        /** @var RequestHttp $request */
        $request = $observer->getEvent()->getData('request');

        /** @var ResponseHttp $response */
        $response = $observer->getEvent()->getData('response');

        // init the toolbar id
        $this->helperData->initToolbarId($request->getFullActionName());

        // create the content of the current toolbar
        $toolbarContent = $this->getCurrentExecutionToolbarBlock($request, $response)->toHtml();
        $this->helperData->saveCurrentToolbar($toolbarContent);
        $this->helperData->cleanOldToolbars($this->helperConfig->getNbExecutionToKeep());

        // add all the last toolbars to the content
        $content = $response->getContent();
        $endTag = '</body';
        if (strpos($content, $endTag) !== false) {
            $toolbarsContent = $this->getToolbarsBlock()->toHtml();
            $content = str_replace($endTag, $toolbarsContent.$endTag, $content);
            $response->setContent($content);
        }
    }

    /**
     * Generate the Toolbar Block for the current execution
     *
     * @param RequestHttp  $request
     * @param ResponseHttp $response
     *
     * @return Toolbar
     */
    protected function getCurrentExecutionToolbarBlock(
        RequestHttp  $request,
        ResponseHttp $response
    ) {
        /** @var Toolbar $block */
        $block = $this->blockToolbarFactory->create();
        $block->loadZones($request, $response);

        return $block;
    }

    /**
     * Generate the Toolbars Block
     *
     * @return Toolbars
     */
    protected function getToolbarsBlock()
    {
        /** @var Toolbars $block */
        $block = $this->blockToolbarsFactory->create();

        return $block;
    }
}
