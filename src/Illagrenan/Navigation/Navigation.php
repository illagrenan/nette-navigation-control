<?php

namespace Illagrenan\Navigation;

use Illagrenan\BaseControl\BaseControl;
use Nette\Application\IPresenter;
use Nette\Application\UI\InvalidLinkException;
use Nette\ComponentModel\RecursiveComponentIterator;
use Nette\Http\Request;
use Nette\Utils\Strings;
use RecursiveIteratorIterator;

/**
 * @license http://opensource.org/licenses/MIT MIT
 * @author Jan Marek <mail@janmarek.net>
 * @author Vašek Dohnal <hello@vaclavdohnal.cz>
 */
class Navigation extends BaseControl
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
        $this->httpRequest = $httpRequest;
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
     * Setup homepage
     * @param string $label
     * @param string $url
     * @return NavigationNode
     */
    public function addHomepage($label, $url)
    {
        /* @var $homepage NavigationNode */
        $homepage = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME);

        // Obsahuje FALSE, pokud se nejedná o platný plink
        $parsedPlink = $this->tryParsePlink($url);

        $homepage->setLabel($label);

        // Odkaz ve tvaru Presenter:action
        if ($parsedPlink !== FALSE)
        {
            $homepage->setUrl($parsedPlink);
            $homepage->setPlink($url);
        }
        else // Obyčejná URL (nekontroluje se)
        {
            $homepage->setUrl($url);
        }

        $this->useHomepage = true;
        return $homepage;
    }

    /**
     * Add navigation node as a child
     * @param string $label
     * @param string $url
     * @return NavigationNode
     */
    public function addNode($label, $url)
    {
        // $url = $this->tryParsePlink($url);
        return $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE)->addNode($label, $url);
    }

    /**
     * @param string $url
     * @return boolean|string Return URL if valid plink otherwise FALSE
     */
    public function tryParsePlink($url)
    {
        try
        {
            $netteLink = $this->presenter->link($url);
        }
        catch (InvalidLinkException $exc)
        {
            $netteLink = FALSE;
        }

        // "error" = fix pro vývojové prostředí, kde odkazy začínají error: Presenter... not found
        if ($netteLink != FALSE && Strings::startsWith($netteLink, "error") == FALSE)
        {
            return $netteLink;
        }

        return FALSE;
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
    private function findCurrent(RecursiveIteratorIterator $components, NavigationNode $base)
    {
        $currentUrl = $this->httpRequest->getUrl();

        /* @var $oneNode NavigationNode */
        foreach ($components as $oneNode)
        {
            // Node využívá plink
            if ($oneNode->isPlinkPresent() && ($oneNode->getPlink() == $this->getCurrentNetteRequest()))
            {
                return $oneNode;
            }
            elseif ($currentUrl->isEqual($oneNode->getUrl())) // Node využívá plnout URL
            {
                return $oneNode;
            }
        }

        // Aktuální node může být ta, která se ptá
        // Node využívá plink
        if ($base->isPlinkPresent() && ($base->getPlink() == $this->getCurrentNetteRequest()))
        {
            return $base;
        }
        elseif ($currentUrl->isEqual($base->getUrl())) // Node využívá plnout URL
        {
            return $base;
        }

        return FALSE;
    }

    public function getCurrentNetteRequest()
    {
        $presenterName = $this->getCurrentPresenterName();
        return $presenterName . ":" . $this->presenter->getOperation();
    }

    public function getCurrentPresenterName()
    {
        $moduleAndPresenter = explode(":", $this->presenter->getName());
        return end($moduleAndPresenter);
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
        $template = $this->createTemplateFromName("breadcrumbs");

        $template->controlName = $this->getName();
        $template->items       = $breadcrumbsQueue;

        $template->render();
    }

    /**
     * Render menu
     * @param bool $renderChildren
     * @param $base
     * @param bool $renderHomepage
     */
    public function renderMenu($renderChildren = TRUE, NavigationNode $base = NULL, $renderHomepage = TRUE)
    {
        /* @var $template ITemplate */
        $template = $this->createTemplateFromName("menu");

        if ($base == NULL)
        {
            $base = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE);
        }


        $allComponents = $base->getComponents();

        if ($this->httpRequest != NULL)
        {
            // TRUE = yes, getComponents recursively
            /* @var $current NavigationNode */
            $current = $this->findCurrent($base->getComponents(TRUE), $base);

            if ($current)
            {
                // Aktuální Node je manuálně skrytá, řekneme to předkovi
                if ($current->iSVisible() === FALSE)
                {
                    $current->getParent()->setHasHiddenCurrentChildren(TRUE);
                }
                else
                {
                    // Aktuální Node je běžně viditelná                   
                    $allComponents = $this->getComponent(self::HOMEPAGE_COMPONENT_NAME, TRUE)->getComponents();

                    foreach ($allComponents as $oneComponent)
                    {
                        $oneComponent->setHasHiddenCurrentChildren(FALSE);
                    }
                }

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

}
