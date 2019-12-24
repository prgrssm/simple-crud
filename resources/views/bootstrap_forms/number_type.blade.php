<div class="form-group">
  <label class="control-label">{{$label}} @if($required)<strong style="color: #f71c1c">*</strong>@endif</label>
  <input
    class="form-control"
    name="{{$name}}"
    type="number"
    @if($required) required @endif
    @foreach($attributes as $key => $value)
    {{$key}}="{{$value}}"
    @endforeach
  />
</div>
