<?php

namespace Illagrenan\Navigation;

use Nette\ComponentModel\Container;

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
     * @var bool
     */
    private $isCurrent;

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
     * @staticvar type $counter
     * @param type $label
     * @param type $url
     * @return \Illagrenan\Navigation\NavigationNode
     */
    public function addNode($label, $url)
    {
        $navigationNode = new NavigationNode($label, $url);

        static $counter;

        $this->addComponent($navigationNode, ++$counter);

        return $navigationNode;
    }

    /*
     * @return \Illagrenan\Navigation\NavigationNode
      public function setCurrent()
      {
      $this->isCurrent = TRUE;
      $this->lookup('\Illagrenan\Navigation')->setCurrentNode($this);

      return $this;
      }
     * */

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

}
