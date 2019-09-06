<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif
        <div class="container">

            @forelse($contactos as $c)
            <div class="row">
                <div class="col-md-3">
                        {{$c->nombre}}<br>
                </div>
                <div class="col-md-3">
                        {{$c->edad}}<br>
                </div>
                <div class="col-md-3">
                        {{$c->atendido}}<br>
                </div>
                <div class="col-md-3">
                        {{$c->email}}<br>
                </div>
                <div class="col-md-3">
                        {{$c->tema}}<br>
                </div>
                <div class="col-md-3">
                        {{$c->mensaje}}<br>
                </div>
                <div class="col-md-3">
                        <img src="{{$c->url_imagen}}" alt="Smiley face" height="42" width="42"><br>
                        <a href="{{$c->url_imagen}}">Link Imagen</a> <br>
                </div>
            </div>
        </div>           
                    
                    
                    
                    
                    
                    
                @empty
                    <p>No hay resultados que mostrar</p>
                @endforelse
        </div>
    </body>
</html>
