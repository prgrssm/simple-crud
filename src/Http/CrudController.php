<?php

namespace SimpleCrud\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\View\View;
use LogicException;
use SimpleCrud\Models\ModelActiveTrait;
use SimpleCrud\Service\ModelFormDataSetter;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class Controller
 * @package SimpleCrud\Controllers
 */
abstract class CrudController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    /**
     * @var string
     *
     * Default view for list action
     */
    protected $listView = 'admin.crud.list';
    
    /**
     * @var string
     *
     * Route for editing model
     */
    protected $routePrefix = '';
    
    /**
     * @var Model
     *
     * Model for query
     */
    protected $model = null;
    
    /**
     * @var string|null
     */
    protected $modelClass = null;
    
    /**
     * @var array
     *
     * List of selected fields form DB
     */
    protected $listFields = [];
    
    /**
     * @var array
     *
     *  List of filterable fields in table
     */
    protected $filterFields = ['id'];
    
    /**
     * @var ModelFormDataSetter
     */
    protected $modelDataSetter;
    
    public function __construct(ModelFormDataSetter $dataSetter)
    {
        if (!$this->routePrefix) {
            throw new LogicException('Route prefix is not defined!');
        }
        
        $this->modelDataSetter = $dataSetter;
        
        if ($this->modelClass && class_exists($this->modelClass)) {
            $this->model = new $this->modelClass;
        }
    }
    
    /**
     * @param Request $request
     * @param null $id
     *
     * @return View|JsonResponse
     */
    abstract public function form(Request $request, $id = null);
    
    /**
     * @param Request $request
     *
     * @return View
     *
     * Returns base CRUD template
     */
    public function index(Request $request): View
    {
        return view($this->listView);
    }
    
    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Returns list of filtered records
     */
    public function list(Request $request): JsonResponse
    {
        $length = $request->input('take') ?? 10;
        $filter = $request->input('filter') ?? false;
        $sortBy = $request->input('sortBy') ?? 'id';
        $sortDesc = $request->input('sortDesc') == 1;
        
        $q = $this->model->newModelQuery();
        
        if ($filter) {
            $q->where(function (Builder $query) use ($request, $filter) {
                foreach ($this->filterFields as $field) {
                    $query->orWhere($field, 'like', "%$filter%");
                }
                
                $this->filterQuery($request, $query, $filter);
            });
        }
        
        if ($sortBy) {
            $q->orderBy($sortBy, $sortDesc ? 'DESC' : 'ASC');
        }
        
        $this->listQuery($request, $q);
        
        if ($this->listFields) {
            $q->select($this->listFields);
        }
        
        $data = $q->paginate($length);
        
        $items = [];
        
        foreach ($data->items() as $item) {
            $items[] = $this->serializeItem($item);
        }
        
        return response()->json(['items' => $items, 'total' => $data->total()]);
    }
    
    /**
     * @param Request $request
     *
     * @return JsonResponse|RedirectResponse
     *
     * Model save logic
     *
     * Use preSave and postSave methods to modify entity
     */
    public function save(Request $request)
    {
        $post = $request->toArray();
        /** @var Model $model */
        $model = new $this->model();
        
        if (isset($post['id']) && is_array($post['id'])) {
            $data = $this->modelDataSetter->getFormData($model, $post);
            $success = $this->model->newModelQuery()
                                   ->whereIn('id', $post['id'])
                                   ->update($data)
            ;
        } else {
            try {
                $model = $this->modelDataSetter->setModelData($model, $post);
                $this->preSave($request, $model);
                
                $success = $model->save();
                $this->postSave($request, $model);
            } catch (Exception $exception) {
                if ($request->isMethod('post')) {
                    $message = ['title' => 'Ошибка!', 'text' => $exception->getMessage(), 'type' => 'danger'];
                    
                    return redirect()->back()->with(['message' => $message]);
                }
                
                return response(400)->json(['success' => false, 'error' => $exception->getMessage()]);
            }
        }
        
        if ($request->isMethod('post')) {
            $message = ['title' => 'Ура!', 'text' => 'Действие выполнено успешно!', 'type' => 'success'];
            
            return redirect()->route("$this->routePrefix.edit", ['id' => $model->id])->with(['message' => $message]);
        }
        
        return response()->json(['success' => $success])
                         ->setStatusCode($success ? 200 : 400)
            ;
    }
    
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     *
     * Base delete action
     */
    public function delete(Request $request)
    {
        $ids = is_array($request->input('id')) ? $request->input('id') : [$request->input('id')];
        $itemsToDelete = $this->model->newModelQuery()->whereIn('id', $ids)->get();
        
        foreach ($itemsToDelete as $item) {
            $this->preDelete($request, $item);
            $item->delete();
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Base toggle active action
     */
    public function toggleActive(Request $request)
    {
        if (!$this->isModelSupportActive()) {
            abort(404);
        }
        
        $activeKey = $this->model->getActiveKey();
        
        $data = $request->validate(['id' => 'required', $activeKey => 'required']);
        $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
        
        $query = $this->model->newModelQuery();
        
        $success = $query->whereIn('id', $ids)->update([$activeKey => (bool)$data[$activeKey]]);
        
        return response()->json(['success' => !!$success])->setStatusCode($success ? 200 : 400);
    }
    
    /**
     * @return JsonResponse
     */
    public function crudOptions()
    {
        $data = [];
        
        try {
            $data['add_url'] = route("{$this->routePrefix}.add");
        } catch (RouteNotFoundException $exception) {
        } catch (UrlGenerationException $exception) {
        }
        
        try {
            $data['edit_url'] = route("{$this->routePrefix}.edit");
        } catch (RouteNotFoundException $exception) {
        } catch (UrlGenerationException $exception) {
        }
        
        $data['delete_url'] = route("{$this->routePrefix}.delete");
        
        if ($this->isModelSupportActive()) {
            $data['active_toggle_url'] = route("{$this->routePrefix}.toggle_active");
        }
        
        return response()->json($data + $this->crudSettings());
    }
    
    /**
     * @return array
     */
    abstract protected function crudSettings(): array;
    
    /**
     * @param Request $request
     * @param Model $model
     */
    protected function preSave(Request $request, Model $model): void
    {
    }
    
    /**
     * @param Request $request
     * @param Model $model
     */
    protected function postSave(Request $request, Model $model): void
    {
    }
    
    /**
     * @param Request $request
     * @param Model $model
     */
    protected function preDelete(Request $request, Model $model)
    {
    }
    
    /**
     * @param Request $request
     * @param Builder $builder
     */
    protected function listQuery(Request $request, Builder $builder)
    {
    }
    
    /**
     * @param Request $request
     * @param Builder $builder
     * @param $filterValue
     */
    protected function filterQuery(Request $request, Builder $builder, $filterValue)
    {
    }
    
    /**
     * @param Model $model
     *
     * @return array
     */
    protected function serializeItem(Model $model): array
    {
        return $model->toArray();
    }
    
    /**
     * @return bool
     */
    protected function isModelSupportActive(): bool
    {
        $uses = class_uses(get_class($this->model));
        
        return isset($uses[ModelActiveTrait::class]);
    }
}
