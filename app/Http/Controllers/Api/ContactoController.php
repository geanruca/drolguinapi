<?php

namespace App\Http\Controllers\Api;

use App\Contacto;
use App\Mail\NuevoContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ContactoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function store(Request $r)
    {
        $r->validate([
            "nombre"   => "required",
            "edad"     => "required",
            "atendido" => "required",
            "email"    => "required",
            "tema"     => "required",
            "mensaje"  => "required"
            // "imagen"   => "required"  
        ]);

        $c = new Contacto;
        $c->nombre   = $r->nombre;
        $c->edad     = $r->edad;
        $c->atendido = $r->atendido;
        $c->email    = $r->email;
        $c->tema     = $r->tema;
        $c->mensaje  = $r->mensaje;
        $c->imagen   = $r->imagen;
        if($r->imagen <> null){
            $c->imagen     = $r->file('imagen')->store('contactos/'.$c->nombre,'public');
            $url_imagen    = Storage::url($c->imagen);
            $c->url_imagen = $url_imagen;
        }
        $c->save();

        if(!$c->save()){
            \Log::error("Dr. Olguin App: Error al guardar datos");
            return response()->json([
                "status" => false,
                "msg"    => "Error al guardar los datos"
            ]);
        }else{
            \Log::info("Nuevo contacto Dr. Olguín",[
                "nombre"   => $r->nombre,
                "edad"     => $r->edad,
                "atendido" => $r->atendido,
                "email"    => $r->email,
                "tema"     => $r->tema,
                "mensaje"  => $r->mensaje,
                "imagen"   => $r->imagen
            ]);

            // Mail::to('gerardo.ruiz.spa@gmail.com')
            // // ->from($c->email)
            // ->queue(new NuevoContacto($c));

            return response()->json([
                "status" => true,
                "msg"    => "Contacto guardado2"
            ]);
        }
    }


    public function show($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
