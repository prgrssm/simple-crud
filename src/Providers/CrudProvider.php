<?php
/**
 * Created by PhpStorm.
 * User: sfartdev5
 * Date: 24.12.2019
 * Time: 15:46
 */

declare(strict_types=1);

namespace SimpleCrud\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SimpleCrud\Service\CrudRoutesBinder;
use SimpleCrud\Service\Form;
use SimpleCrud\Service\ModelFormDataSetter;

/**
 * Class CrudProvider
 * @package SimpleCrud\Providers
 */
class CrudProvider extends ServiceProvider
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var CrudRoutesBinder
     */
    private $crudRoutesBinder;

    /**
     * @var ModelFormDataSetter
     */
    private $modelFormDataSetter;

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'simple_crud');

        $configPath = __DIR__ . '/../../config/simple_crud.php';

        if (function_exists('config_path')) {
            $publishPath = config_path('simple_crud.php');
        } else {
            $publishPath = base_path('config/simple_crud.php');
        }

        $this->publishes([
            $configPath => $publishPath,
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/simple_crud')
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/simple_crud.php', 'simple_crud'
        );

        $this->crudRoutesBinder = $this->app->make(CrudRoutesBinder::class);
        $this->form = new Form(config('simple_crud.form.banned_attributes'));
        $this->modelFormDataSetter = new  ModelFormDataSetter(
            config('simple_crud.form_data_setter.remove_spaces_keys'),
            config('simple_crud.form_data_setter.ignore_keys')
        );

        $this->app->bind('crud_routes_binder', function () {
            return $this->crudRoutesBinder;
        });

        $this->app->bind(Form::class, function () {
            return $this->form;
        });

        $this->app->bind(ModelFormDataSetter::class, function () {
            $this->modelFormDataSetter;
        });

        View::share('form', $this->app->make(Form::class));
    }
}
