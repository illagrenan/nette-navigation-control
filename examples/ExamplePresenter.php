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
