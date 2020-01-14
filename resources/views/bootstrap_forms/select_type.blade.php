<div class="form-group">
  <label class="control-label">{{$label}} @if($required)<strong style="color: #f71c1c">*</strong>@endif</label>
  <select
    name="{{$name}}"
    class="form-control">
    @if(!$required)
      <option value="">Не задано</option>
    @endif
    @foreach($options as $option)
      <option @if($value == $option['value']) selected="selected" @endif
      value="{{$option['value']}}">{{isset($option['label']) ? $option['label'] : $option['value']}}</option>
    @endforeach
  </select>
</div>
