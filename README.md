# Nette Navigation Control ([forknuto od janmarek/Navigation](https://github.com/janmarek/Navigation))
Control pro Nette Framework usnadňující tvorbu menu a drobečkové navigace

Autor: Jan Marek
Licence: MIT

```json
{
	"require": {
        "illagrenan/nette-navigation-control": "dev-master"
    }
}
```

## Použití

**Továrnička v presenteru:**

```php
<?php
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
?>
```

**Menu v šabloně:**
```html
{widget navigation}
```

**Drobečková navigace v šabloně:**
```html
{widget navigation:breadcrumbs}
```