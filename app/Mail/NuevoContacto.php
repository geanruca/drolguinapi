<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NuevoContacto extends Mailable
{
    use Queueable, SerializesModels;

    public $c;
    public $historial;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($c, $historial)
    {
        $this->c = $c;
        $this->historial = $historial;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // dd($this->c);
        $c = $this->c;
        $historial = $this->historial;
        return $this->from($c->email, $c->nombre)
        ->replyTo($c->email, $c->nombre)
        ->subject("Dr. Olguin App: ".$c->tema)
        // ->to("jriquelme92@gmail.com")
        // ->attach($c->url_imagen)
        // ->text("emails.contacto");
        ->view('emails.contacto', compact('c','historial'));
    }
}
