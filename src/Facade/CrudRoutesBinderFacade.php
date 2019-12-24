<?php

declare(strict_types=1);

namespace SimpleCrud\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class CrudRoutesBinderFacade
 * @package SimpleCrud\Facade
 */
class CrudRoutesBinderFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'crud_routes_binder';
    }
}
