<div class="form-group">
    <label class="control-label">{{$label}}</label>
    <textarea
      class="form-control"
      name="{{$name}}"
    @foreach($attributes as $key => $value)
        {{$key}}="{{$value}}"
    @endforeach
    >{{$value}}</textarea>
</div>
