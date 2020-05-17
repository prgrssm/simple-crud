<div class="form-group">
  <div class="checkbox">
    <label>
      <input type="hidden" name="{{$name}}" value="0">
      <input type="checkbox" name="{{$name}}" @if($value)checked="checked" @endif value="1">
      {{$label}}
    </label>
  </div>
</div>
