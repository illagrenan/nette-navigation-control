<?php

namespace Illagrenan\Navigation;

use Nette\Application\IPresenter;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\ComponentModel\RecursiveComponentIterator;
use Nette\Http\Request;
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
     * @var Request
     */
    private $httpRequest;

    /**
     * @param IPresenter $parent
     * @param type $name
     */
    public function __construct(IPresenter $parent = NULL, $name = NULL, Request $httpRequest = NULL)
    {
        parent::__construct($parent, $name);

        $this->templatePath = __DIR__ . "/" . self::DEFAULT_TEMPLATE_PATH . "/";
        $this->httpRequest  = $httpRequest;
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
        $url = $this->tryParsePlink($url);

        return $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE)->addNode($label, $url);
    }

    private function tryParsePlink($url)
    {
        try
        {
            $netteLink = $this->presenter->link($url);
        }
        catch (InvalidLinkException $exc)
        {
            $netteLink = FALSE;
        }

        if ($netteLink != FALSE && Strings::startsWith($netteLink, "error") == FALSE)
        {
            return $netteLink;
        }

        return $url;
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

        $url = $this->tryParsePlink($url);
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

    private function getBreadcrumbsPathForCurrentNode()
    {
        /* @var $tempCurrentNode NavigationNode */
        $tempCurrentNode = $this->currentNode;

        /* @var $breadcrumbsQueue NavigationNode[] */
        $breadcrumbsQueue = array();

        /*
         *  Začínáme od poslední (aktivní) a dotazujeme se
         *  až ke kořeni stromu na rodiče
         */
        while ($tempCurrentNode instanceof NavigationNode)
        {
            // Rodič akutálně zpracovávané Node
            $parentNode = $tempCurrentNode->getParent();

            /* Pokud nemáme používat homepage a rodič je typu Navigation, jsme u kořene a končíme
             * (homepage tedy do drobečkové navigace nepřidáváme) */
            if (!$this->useHomepage && !($parentNode instanceof NavigationNode))
            {
                break;
            }

            /*
             * Na začátek fronty s drobečkovou navigací připojíme aktuální Node
             */
            array_unshift($breadcrumbsQueue, $tempCurrentNode);

            $tempCurrentNode = $parentNode;
        }

        return $breadcrumbsQueue;
    }

    /**
     * Najdi aktuální Node z httpRequestu
     * @param RecursiveComponentIterator $components
     * @param \Illagrenan\Navigation\NavigationNode $base
     * @return \Illagrenan\Navigation\NavigationNode|boolean
     */
    private function findCurrent(RecursiveComponentIterator $components, NavigationNode $base)
    {
        $currentUrl = $this->httpRequest->getUrl();

        /* @var $oneNode NavigationNode */
        foreach ($components as $oneNode)
        {
            if ($currentUrl->isEqual($oneNode->getUrl()))
            {
                return $oneNode;
            }
        }

        if ($currentUrl->isEqual($base->getUrl()))
        {
            return $base;
        }

        return FALSE;
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

        /* @var $breadcrumbsQueue NavigationNode[] */
        $breadcrumbsQueue = $this->getBreadcrumbsPathForCurrentNode();

        /* @var $template ITemplate */
        $template = $this->createBreadcrumbsTemplate();

        $template->controlName = $this->getName();
        $template->items       = $breadcrumbsQueue;

        $template->render();
    }

    /**
     * Render menu
     * @param bool $renderChildren
     * @param NavigationNode $base
     * @param bool $renderHomepage
     */
    public function renderMenu($renderChildren = TRUE, NavigationNode $base = NULL, $renderHomepage = TRUE)
    {
        /* @var $template ITemplate */
        $template = $this->createMenuTemplate();

        if ($base == NULL)
        {
            $base = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE);
        }

        $allComponents = $base->getComponents();

        if ($this->httpRequest != NULL)
        {
            /* @var $current NavigationNode */
            $current = $this->findCurrent($allComponents, $base);

            if ($current)
            {
                $this->setCurrentNode($current);
            }
        }

        $template->controlName    = $this->getName();
        $template->homepage       = $base ? $base : $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, true);
        $template->useHomepage    = $this->useHomepage && $renderHomepage;
        $template->renderChildren = $renderChildren;
        $template->children       = $allComponents;

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
