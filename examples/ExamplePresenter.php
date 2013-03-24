<?php

namespace Illagrenan\Navigation\Example;

use Nette\Application\UI\Presenter;
use Nette\Http\Request;

class ExamplePresenter extends Presenter
{

    /**
     * @var Request
     */
    protected $httpRequest;

    public function injectRequest(Request $request)
    {
        $this->httpRequest = $request;
    }

    protected function createComponentNavigation($name)
    {
        $navigation = new \Illagrenan\Navigation\Navigation($this, $name, $this->httpRequest);

        $navigation->addHomepage("Přehled obsahu", "Homepage:default");
        $navigation->addNode("Materiály", "Publishable:list");
        $navigation->addNode("Diskuze", "Discussion:list");

        $administration = $navigation->addNode("Administrace", "Admin:default")
                            ->addSpecialClass("special");

        $administration->addNode("Vytvořit obsah", "Admin:createPublishable");
        $administration->addNode("Správa diskuzí", "Admin:manageDiscussion");
        $administration->addNode("Správa tagů", "Admin:manageTags");

        return $navigation;
    }
}
