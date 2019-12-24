<?php

declare(strict_types=1);

namespace SimpleCrud\Service;

use Illuminate\Routing\Router;

/**
 * Class CrudRoutesBinder
 * @package SimpleCrud\Service
 */
class CrudRoutesBinder
{
    /**
     * @var Router
     */
    private $router;

    /**
     * CrudRoutesBinder constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $url
     * @param string $controller
     * @param string $prefix
     */
    public function bindCrud(string $url, string $controller, string $prefix)
    {
        $this->router->get("$url/crud", "$controller@list")->name("$prefix.crud");
        $this->router->options("$url/crud", "$controller@crudOptions");

        $this->router->get($url, "$controller@index")->name($prefix);
        $this->router->get("$url/add", "$controller@form")->name("$prefix.add");
        $this->router->get("$url/{id?}", "$controller@form")->name("$prefix.edit");
        $this->router->match(['put', 'post'], $url, "$controller@save")->name("$prefix.save");
        $this->router->delete($url, "$controller@delete")->name("$prefix.delete");

        $this->router->put("$url/active", "$controller@toggleActive")->name("$prefix.toggle_active");
    }
}
