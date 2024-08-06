@extends('layouts.app')

@section('content')
    <div class="container">
        <p>
            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample"
                    aria-expanded="false" aria-controls="collapseExample">
                Оновити дані
            </button>
        </p>
        <div class="collapse" id="collapseExample">
            <div class="row">
                <div class="card-body border border-primary p-4">
                    <form name="add-blog-post-form" id="add-blog-post-form" method="post"
                          enctype="multipart/form-data"
                          action="{{ url('update-profile') }}">
                        @csrf
                        <div class="form-group">
                            <label for="exampleInputEmail1">Прізвище Ім'я</label>
                            <input type="text" id="title" name="name" class="form-control" required=""
                                   value="{{ $user->name }}">
                        </div>
                        <div class="form-group">
                            <label style="color: black;">Фото</label>
                            <br>
                            <input type="file" name="file">
                        </div>
                        <br>
                        <button type="submit" class="btn btn-primary">Оновити</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-2 p-4">
                    <table class="legend-table">
                        <tr class="legend-tr">
                            <th class="legend-th">Легенда</th>
                            <th class="legend-th">Фото</th>
                            <th class="legend-th">Моя оцінка</th>
                            <th class="legend-th">Загальний Рейтинг</th>
                        </tr>
                        @foreach($legends as $legend)
                            <tr>
                                <td class="legend-td">{{ $legend->name }}</td>
                                @if (isset($images[$legend->id]))
                                    <td class="legend-td">
                                        <img class="card-img legend-img" src="{{ $images[$legend->id] }}" alt="Card image cap">
                                    </td>
                                @else
                                    <td class="legend-td">ще не додав фото</td>
                                @endif
                                    @if ($user->id === $legend->id)
                                        <td class="legend-td">
                                            Я легенда
                                        </td>
                                    @elseif (!isset($myValues[$legend->id]['is_disabled']) || $myValues[$legend->id]['is_disabled'] === 0)
                                        <td class="legend-td">
                                            @if (isset($myValues[$legend->id]['next_update']))
                                                Зможеш оновити оцінку після {{ $myValues[$legend->id]['next_update']  }}
                                            @else
                                                Зможеш оновити оцінку пізже
                                            @endif
                                        </td>
                                    @else
                                        <td class="legend-td">
                                            <form name="add-blog-post-form-2" id="add-blog-post-form-2" method="post"
                                                  action="{{ url('set-value') }}">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="number" id="title" name="value" class="form-control"
                                                           required="" value="{{ $myValues[$legend->id]['value'] ?? 0 }}" max="100" min="0">
                                                    <input type="hidden" name="legend_id" class="form-control" value="{{ $legend->id }}">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="submit">Ляп</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    @endif
                                <td class="legend-td">{{ $legend->rating }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
