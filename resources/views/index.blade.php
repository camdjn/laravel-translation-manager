<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>Translation Manager</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
  <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

  <style>
  a.status-1{
    font-weight: bold;
  }
  </style>

  <script>
  jQuery(document).ready(function($){

    $('.group-select').on('change', function(){
      var group = $(this).val();

      if (group) {
        window.location.href = '{{ action('\camdjn\TranslationManager\Controller@getView') }}/'+$(this).val();
      } else {
        window.location.href = '{{ action('\camdjn\TranslationManager\Controller@getIndex') }}';
      }
    });
  })
  </script>
</head>
<body>



  <div style="width: 80%; margin: auto;">
    <h1>Translation Manager</h1>

    <p>Warning, translations are not visible until they are exported back to the app/lang file, using 'php artisan translation:export' command or publish button.</p>

    <div class="alert alert-success success-find" style="display:none;">
      <p>Done searching for translations, found <strong class="counter">N</strong> items!</p>
    </div>

    @if(session('successPublish'))
      <div class="alert alert-info">
        {{ session('successPublish') }}
      </div>
    @endif

    @if(session('findCounter'))
      <div class="alert alert-success">
          <p>Done searching for translations, found <strong>{{ session('findCounter') }}</strong> items!</p>
      </div>
    @endif

    <p>
      @if(!isset($group))

        @if(session('counter'))
          <div class="alert alert-success">
            <p>Done importing, processed <strong>{{ session('counter') }}</strong> items!</p>
          </div>
        @endif

        <a class="btn btn-success" href="{{ action('\camdjn\TranslationManager\Controller@getImport') }}">Import groups</a>

        <form class="form-inline form-find" method="POST" action="{{action('\camdjn\TranslationManager\Controller@postFind') }}" data-remote="true" role="form" data-confirm="Are you sure you want to scan you app folder? All found translation keys will be added to the database.">
          {{ csrf_field() }}
          <p></p>
          <button type="submit" class="btn btn-info" data-disable-with="Searching.." >Find translations in files</button>
        </form>
      @endif

      @if(isset($group))
        <form class="form-inline form-publish" method="POST" action="{{ action('\camdjn\TranslationManager\Controller@postPublish', $group->id) }}" data-remote="true" role="form" data-confirm="Are you sure you want to publish the translations group '{{ $group->label }}'? This will overwrite existing language files.">
          {{ csrf_field() }}
          <button type="submit" class="btn btn-info" data-disable-with="Publishing.." >Publish translations</button>
          <a href="{{ action('\camdjn\TranslationManager\Controller@getIndex') }}" class="btn btn-default">Back</a>
        </form>
      @endif
    </p>

    <form role="form">

      {{ csrf_field() }}
      <div class="form-group">

        <select name="group" id="group" class="form-control group-select">

          <option value=""> Choose a group</option>
          @foreach($groups as $key => $value)
            <option value="{{ $value }}" {{ $group && $value == $group->id ? ' selected':''}}>
              {{ $key }}
            </option>
          @endforeach
        </select>
      </div>
    </form>

    @if($group)

      @if(session('deletedKey'))
        <div class="alert alert-danger">
          <p><strong>{{ session('deletedKey') }}</strong> has been deleted!</p>
        </div>
      @endif

      <form action="{{ action('\camdjn\TranslationManager\Controller@postAdd', $group->id) }}" method="POST" role="form">
        {{ csrf_field() }}
        <textarea class="form-control" rows="3" name="keys" placeholder="Add 1 key per line, without the group prefix"></textarea>
        <p></p>
        <input type="submit" value="Add keys" class="btn btn-primary">
      </form>
      <hr>


      <table class="table">
        <thead>
          <tr>
            <th  width="15%">key</th>

            @foreach($locales as $locale)
              <th>{{ $locale->label }}</th>
            @endforeach
          </tr>
        </thead>

        <tbody>
          @foreach($allTranslations->groupBy('key') as $keys)
            <tr id="{{ $keys->first()->key}}">
              <td> {{ $keys->first()->key}} </td>

              @foreach($locales as $locale)

                <form method="POST" action="{{ action('\camdjn\TranslationManager\Controller@postEdit', [$group->id, $locale->id, $keys->first()->key]) }}">{{ csrf_field() }}
                  <td>
                    @if(!$allTranslations->where('locale_id',$locale->id)->where('key',$keys->first()->key)->isEmpty() &&
                      !is_null($allTranslations->where('locale_id',$locale->id)->where('key',$keys->first()->key)->first()->value))
                      <textarea name="value" class="form-control" rows="3">{{$allTranslations->where('locale_id',$locale->id)->where('key', $keys->first()->key)->first()->value }}</textarea>
                      <input type="submit" value="edit" class="btn btn-primary">
                      <a class="btn btn-danger" href="{{ action('\camdjn\TranslationManager\Controller@getEmpty',[$group->id, $locale->id, $keys->first()->key])}}"><span class="glyphicon glyphicon-trash"></span></a>

                    @else
                      <textarea name="value" class="form-control" rows="3" placeholder="empty {{ $$keys->first()->key.'-'.$locale}}"></textarea>
                      <input type="submit" value="add" class="btn btn-success">
                    @endif

                  </td>
                </form>
              @endforeach
              @if($deleteEnabled)
                <td>
                  <a href="{{ action('\camdjn\TranslationManager\Controller@getDelete', [$group, $keys->first()->key]) }}"<span class="glyphicon glyphicon-trash"></span></a>
                </td>
              @endif

            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p>Choose a group to display the group translations. If no groups are visible, make sure you have run the migrations and imported the translations.</p>
    @endif
  </div>

</body>
</html>
