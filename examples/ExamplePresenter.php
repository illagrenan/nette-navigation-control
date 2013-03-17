<?php

class ExamplePresenter extends Nette\Application\UI\Presenter
{

    /**
     * @var \Nette\Http\Request
     */
    protected $httpRequest;

    public function injectRequest(\Nette\Http\Request $request)
    {
        $this->httpRequest = $request;
    }

    protected function createComponentNavigation($name)
    {
        $nav = new \Illagrenan\Navigation\Navigation($this, $name);

        $sitemap = array();

        $sitemap[0]["name"]  = "Přehled obsahu";
        $sitemap[0]["plink"] = "Homepage:default";

        $sitemap[1]["name"]  = "Materiály";
        $sitemap[1]["plink"] = "Source:list";

        $sitemap[2]["name"]  = "Diskuze";
        $sitemap[2]["plink"] = "Discussion:list";


        for ($index = 0; $index < count($sitemap); $index++)
        {
            /* @var $added \Illagrenan\Navigation\NavigationNode */

            $link = $this->link($sitemap[$index]["plink"]);

            $name = $sitemap[$index]["name"];

            if ($index == 0)
            {
                $added = $nav->addHomepage($name, $link);
            }
            else
            {
                $added = $nav->addNode($name, $link);
            }

            if ($this->httpRequest->getUrl()->isEqual($link))
            {
                $nav->setCurrentNode($added);
            }
        }
    }

}
