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