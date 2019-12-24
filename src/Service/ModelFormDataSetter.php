<?php

namespace SimpleCrud\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Class ModelFormDataSetter
 * @package SimpleCrud\Service
 */
class ModelFormDataSetter
{
    /**
     * @var array
     */
    private $removeSpacesKeys;

    /**
     * @var array
     */
    private $ignoreKeys;

    /**
     * ModelFormDataSetter constructor.
     *
     * @param array $removeSpacesKeys
     * @param array $ignoreKeys
     */
    public function __construct(array $removeSpacesKeys = [], array $ignoreKeys = [])
    {
        $this->removeSpacesKeys = $removeSpacesKeys;
        $this->ignoreKeys = $ignoreKeys;
    }

    /**
     * @param Model $model
     * @param array $post
     *
     * @return array|Model
     */
    public function setModelData(Model $model, array $post)
    {
        if (isset($post['id'])) {
            $model = $model->where('id', $post['id'])->first();
        }

        $data = $this->getFormData($model, $post);

        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    /**
     * @param Model $model
     * @param array $post
     *
     * @return array
     */
    public function getFormData(Model $model, array $post)
    {
        $data = [];
        $modelKeys = Schema::getColumnListing($model->getTable());

        foreach ($post as $key => $value) {
            if (in_array($key, $modelKeys) && !in_array($key, $this->ignoreKeys)) {
                if (in_array($key, $this->removeSpacesKeys)) {
                    $data[$key] = preg_replace("/\s{2,}/", " ", $value);
                } else {
                    $data[$key] = $value;
                }

                $data[$key] = trim($data[$key]);

                if (strlen($data[$key]) === 0) {
                    $data[$key] = null;
                }
            }
        }

        return $data;
    }
}
