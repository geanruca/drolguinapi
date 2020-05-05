
<div class="container">
        <div class="row">
            <div class="col">
                <b>Nombre:</b> {{$c->nombre}}
            </div>
            <div class="col">
                <b>Edad:</b> {{$c->edad}}
            </div>
            <div class="col">
                <b>Atendido:</b> {{$c->atendido}}
            </div>
            <div class="col">
                <b>Email:</b> {{$c->email}}
            </div>
            <div class="col">
                <b>Tema:</b> {{$c->tema}}
            </div>
            <div class="col">
                <b>Mensaje:</b> {{$c->mensaje}}
            </div>
            <div class="col">
                @if($c->url_imagen)
                    <img name="imagen" id="imagen" src="{{ $message->embed('https://mg.mobilechile.app/'.$c->url_imagen) }}" style="display:block; " width="200" height="200" data-auto-embed="attachment"/>
                    <label for="imagen">
                        <a style="display:block; " href="{{'https://mg.mobilechile.app/'.$c->url_imagen}}">Link Imagen</a> 
                    </label>
                @else
                    <p>Sin imagen</p>
                @endif
            </div>
        </div>
</div>

<div class="container-fluid">
    <div class="col">
        <h2>Historial del paciente</h2>
    </div>
    <table id="customers" class="table table-striped table-responsive">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Edad</th>
                <th>Atendido</th>
                <th>Tema</th>
                <th>Mensaje</th>
                <th>Imagen</th>
            </tr>
        </thead>
        @forelse ($historial as $historia)
        <tbody>   
            <tr>
                <td>{{$historia->created_at}}</td>
                <td>{{$historia->edad}}</td>
                <td>{{$historia->atendido}}</td>
                <td>{{$historia->tema}}</td>
                <td>{{$historia->mensaje}}</td>
                <td>
                <a href="{{'https://mg.mobilechile.app/'.$historia->url_imagen}}">
                    link!
                </a>
                    
                </td>
            </tr>
        </tbody> 
        @empty
            <p>No hay información sobre este paciente</p>
        @endforelse
    </table>
</div>


      
