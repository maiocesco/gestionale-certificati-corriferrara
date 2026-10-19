<x-mail::message>
Ciao {{ $firstName }},

purtroppo il certificato medico che hai caricato non è stato approvato, per questo motivo:

{{ $motivazione }}

Ti chiediamo di caricarne uno corretto dal tuo profilo.

<x-mail::button :url="$profileUrl">
Carica un nuovo certificato
</x-mail::button>

Per qualsiasi dubbio rispondi pure a questa email.

—
A.S.D. Corriferrara
Questa email è stata inviata automaticamente dal gestionale certificati medici.
</x-mail::message>
