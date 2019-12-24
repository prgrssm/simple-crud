<?php

namespace SimpleCrud\Service;

use Illuminate\Database\Eloquent\Model;

class Form
{
    /**
     * @var array
     */
    private $bannedAttributes;

    /**
     * Form constructor.
     *
     * @param array $bannedAttributes
     */
    public function __construct(array $bannedAttributes = [])
    {
        $this->bannedAttributes = $bannedAttributes;
    }

    public function renderFiled(Model $item, string $type, string $valueKey, array $config)
    {
        $data = '';

        $viewData = [
            'name' => $valueKey,
            'required' => false,
            'attributes' => []
        ];

        $attributes = [
            'value' => $item->$valueKey ?? null,
        ];

        $viewData = $config + $viewData;

        if (isset($config['attributes'])) {
            $viewData['attributes'] = array_filter($config['attributes'] + $attributes, function ($key) {
                return !in_array($key, $this->bannedAttributes);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $viewData['attributes'] = $attributes;
        }

        if (isset($config['required'])) {
            $viewData['required'] = (bool)$config['required'];
        }

        switch ($type) {
            case 'text':
                $data = view('simple_crud.bootstrap_forms.text_type', $viewData);
                break;
            case 'number':
                $data = view('simple_crud.bootstrap_forms.number_type', $viewData);
                break;
            case 'checkbox':
                $viewData['value'] = $viewData['attributes']['value'];
                unset($viewData['attributes']['value']);

                $data = view('simple_crud.bootstrap_forms.checkbox-type', $viewData);
                break;
            case 'select':
                $viewData['value'] = $viewData['attributes']['value'];
                unset($viewData['attributes']['value']);

                $data = view('simple_crud.bootstrap_forms.select_type', $viewData);
                break;
            case 'textarea':
                $viewData['value'] = $viewData['attributes']['value'];
                unset($viewData['attributes']['value']);

                $data = view('simple_crud.bootstrap_forms.textarea_type', $viewData);
                break;
            case 'ckeditor':
                $viewData['value'] = $viewData['attributes']['value'];
                unset($viewData['attributes']['value']);

                $data = view('simple_crud.bootstrap_forms.ckeditor_type', $viewData);
                break;
        }

        return $data;
    }
}
