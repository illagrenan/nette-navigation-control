<?php

namespace Illagrenan\Navigation;

use Nette\ComponentModel\Container;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * @license http://opensource.org/licenses/MIT MIT
 * @author Jan Marek <mail@janmarek.net>
 * @author Vašek Dohnal <hello@vaclavdohnal.cz>
 */
class NavigationNode extends Container
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $plink;

    /**
     * @var bool
     */
    private $isCurrent;

    /**
     * @var bool
     */
    private $hasHiddenCurrentChildren = FALSE;

    /**
     * @var string[]
     */
    private $specialClass = array();

    /**
     * @var boolean
     */
    private $isVisible = TRUE;

    /**
     * @param string $label
     * @param string $url
     */
    function __construct($label, $url)
    {
        $this->label     = $label;
        $this->url       = $url;
        $this->isCurrent = FALSE;
    }

    /**
     * @staticvar int $counter
     * @param string $label
     * @param string $url
     * @return \Illagrenan\Navigation\NavigationNode
     */
    public function addNode($label, $url)
    {
        /* @var $homepageComponent Navigation */
        $homepageComponent = $this->lookup("Illagrenan\Navigation\Navigation", FALSE);

        // Obsahuje FALSE, pokud se nejedná o platný plink
        $parsedPlink       = $homepageComponent->tryParsePlink($url);
        $newNavigationNode = new NavigationNode($label, $url);

        // Odkaz ve tvaru Presenter:action
        if ($parsedPlink !== FALSE)
        {
            $newNavigationNode->setUrl($parsedPlink);
            $newNavigationNode->setPlink($url);
        }
        else // Obyčejná URL (nekontroluje se)
        {
            $newNavigationNode->setUrl($url);
        }

        static $counter;

        $this->addComponent($newNavigationNode, "IllagrenanNavigationNO" . ++$counter);

        return $newNavigationNode;
    }

    public function thisIsCurrent()
    {
        $this->setCurrent();
        $this->lookup('Illagrenan\Navigation\Navigation')->setCurrentNode($this);

        return $this;
    }

    /* ----------------------------------------------
     * *** Getters & Setters ***
     * ---------------------------------------------- */

    public function getIsCurrent()
    {
        return $this->isCurrent;
    }

    public function setIsCurrent($isCurrent)
    {
        $this->isCurrent = $isCurrent;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getSpecialClass()
    {
        return $this->specialClass;
    }

    /**
     * @param string $specialClass
     * @return \Illagrenan\Navigation\NavigationNode
     */
    public function addSpecialClass($specialClass)
    {
        if (Validators::isUnicode($specialClass) === FALSE)
        {
            throw new Exceptions\InvalidSpecialClassException("Given \"" . $specialClass . "\" is not valid special class.");
        }

        $specialClass = Strings::trim($specialClass);
        $specialClass = Strings::normalize($specialClass);
        $specialClass = Strings::toAscii($specialClass);

        $this->specialClass[] = $specialClass;
        return $this;
    }

    public function setNotCurrent()
    {
        $this->isCurrent = FALSE;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isCurrent()
    {
        return $this->isCurrent;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setCurrent()
    {
        $this->isCurrent = TRUE;
        return $this;
    }

    public function isPlinkPresent()
    {
        return (bool) isset($this->plink);
    }

    public function getPlink()
    {
        return $this->plink;
    }

    public function setPlink($plink)
    {
        $this->plink = $plink;
        return $this;
    }

    public function iSVisible()
    {
        return $this->isVisible;
    }

    public function setIsVisible($isVisible)
    {
        $this->isVisible = (bool) $isVisible;
        return $this;
    }

    public function hasHiddenCurrentChildren()
    {
        return $this->hasHiddenCurrentChildren;
    }

    public function setHasHiddenCurrentChildren($hasHiddenCurrentChildren)
    {
        $this->hasHiddenCurrentChildren = (bool) $hasHiddenCurrentChildren;
        return $this;
    }

}
