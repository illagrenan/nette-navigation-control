<?php

namespace Illagrenan\Navigation;

use Nette\Application\IPresenter;
use Nette\Application\UI\Control;
use Nette\Utils\Strings;

/**
 * @license http://opensource.org/licenses/MIT MIT
 * @author Jan Marek <mail@janmarek.net>
 * @author Vašek Dohnal <hello@vaclavdohnal.cz>
 */
class Navigation extends Control
{

    /**
     * Node držící homepage
     * @var NavigationNode
     */
    private $homepage;

    /**
     * Aktuální Node
     * @var NavigationNode
     */
    private $currentNode;

    /**
     * Používat homepage?
     * @var bool
     */
    private $useHomepage = false;

    /**
     * Šablona menu
     * @var string
     */
    private $menuTemplate;

    /**
     * Šablona drobečkové navigace
     * @var string
     */
    private $breadcrumbsTemplate;

    /**
     * Klíč pro komponentu "homepage"
     */

    const HOMEPAGE_COMPONENT_NAME = "homepage";

    /**
     * Koncovka latte šablon
     */
    const LATTE_EXTEMSION = ".latte";

    /**
     * Výchozí cesta úložiště šablon
     */
    const DEFAULT_TEMPLATE_PATH = "templates";

    /**
     * Cesta k šablonám
     * @var string
     */
    private $templatePath;

    /**
     * @param \Nette\Application\IPresenter $parent
     * @param type $name
     */
    public function __construct(IPresenter $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->templatePath = __DIR__ . "/" . self::DEFAULT_TEMPLATE_PATH . "/";
    }

    /**
     * Set node as current
     * @param NavigationNode $node
     */
    public function setCurrentNode(NavigationNode $node)
    {
        if (isset($this->currentNode))
        {
            $this->currentNode->setNotCurrent();
        }

        $node->setCurrent();
        $this->currentNode = $node;
    }

    /**
     * Add navigation node as a child
     * @param string $label
     * @param string $url
     * @return NavigationNode
     */
    public function addNode($label, $url)
    {
        return $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE)->addNode($label, $url);
    }

    /**
     * Setup homepage
     * @param string $label
     * @param string $url
     * @return NavigationNode
     */
    public function addHomepage($label, $url)
    {
        /* @var $homepage NavigationNode */
        $homepage = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME);

        $homepage->setLabel($label)->setUrl($url);

        $this->useHomepage = true;
        return $homepage;
    }

    /**
     * @param type $name
     * @return \Illagrenan\Navigation\NavigationNode
     */
    protected function createComponentHomepage($name)
    {
        return new NavigationNode($this, $name);
    }

    /**
     * @param string $breadcrumbsTemplate
     */
    public function setBreadcrumbsTemplate($breadcrumbsTemplate)
    {
        $this->breadcrumbsTemplate = $breadcrumbsTemplate;
    }

    /**
     * @param string $menuTemplate
     */
    public function setMenuTemplate($menuTemplate)
    {
        $this->menuTemplate = $menuTemplate;
    }

    /**
     * @return \Navigation\NavigationNode
     */
    public function getCurrentNode()
    {
        return $this->currentNode;
    }

    /* ----------------------------------------------
     * *** RENDER METHODS ***
     * ---------------------------------------------- */

    /**
     * Vykreslí celé menu
     * <code>
     * {widget navigation}
     * </code>
     */
    public function render()
    {
        $this->renderMenu();
    }

    /**
     * Vykreslí hlavní menu
     * <code>
     * {widget navigation:mainMenu}
     * </code>
     */
    public function renderMainMenu()
    {
        $this->renderMenu(FALSE);
    }

    /**
     * Vykreslí drobečkovou navigaci
     * <code>
     * {widget navigation:breadcrumbs}
     * </code>
     */
    public function renderBreadcrumbs()
    {
        // Nemáme aktivní stránku
        if (empty($this->currentNode))
        {
            return;
        }

        $breadcrumbsQueue = array();
        $currentNode      = $this->currentNode;

        while ($currentNode instanceof NavigationNode)
        {
            $parentNode = $currentNode->getParent();

            if (!$this->useHomepage && !($parentNode instanceof NavigationNode))
            {
                break;
            }

            array_unshift($breadcrumbsQueue, $currentNode);

            $currentNode = $parentNode;
        }

        $template = $this->createBreadcrumbsTemplate();

        $template->items = $breadcrumbsQueue;
        $template->render();
    }

    /**
     * Render menu
     * @param bool $renderChildren
     * @param NavigationNode $base
     * @param bool $renderHomepage
     */
    public function renderMenu($renderChildren = TRUE, $base = NULL, $renderHomepage = TRUE)
    {
        $template = $this->createMenuTemplate();

        $template->homepage       = $base ? $base : $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, true);
        $template->useHomepage    = $this->useHomepage && $renderHomepage;
        $template->renderChildren = $renderChildren;
        $template->children       = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE)->getComponents();

        $template->render();
    }

    /* ----------------------------------------------
     * *** TVORBA ŠABLON ***
     * ---------------------------------------------- */

    private function createMenuTemplate()
    {
        /* @var $template ITemplate */
        $template = $this->createTemplate();

        if ($this->menuTemplate)
        {
            $template->setFile($this->menuTemplate);
        }
        else
        {
            $menuTemplatePath = $this->getTemplatePath("menu");
            $template->setFile($menuTemplatePath);
        }

        return $template;
    }

    private function createBreadcrumbsTemplate()
    {
        /* @var $template ITemplate */
        $template = $this->createTemplate();

        if ($this->breadcrumbsTemplate)
        {
            $template->setFile($this->breadcrumbsTemplate);
        }
        else
        {
            $menuTemplatePath = $this->getTemplatePath("breadcrumbs");
            $template->setFile($menuTemplatePath);
        }

        return $template;
    }

    private function getTemplatePath($templateName)
    {
        if (Strings::endsWith($templateName, self::LATTE_EXTEMSION) == FALSE)
        {
            $templateName = $templateName . self::LATTE_EXTEMSION;
        }

        return $this->templatePath . $templateName;
    }

}
