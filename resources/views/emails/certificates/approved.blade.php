<x-mail::message>
Ciao {{ $firstName }},

il tuo certificato medico {{ $typeDescription }} è stato verificato e approvato.
È valido fino al **{{ $expiresAt }}**.

Ti avviseremo noi quando si avvicinerà la scadenza. Puoi controllarlo in qualunque momento dal tuo profilo.

<x-mail::button :url="$profileUrl">
Vai ai tuoi certificati
</x-mail::button>

—
A.S.D. Corriferrara
Questa email è stata inviata automaticamente dal gestionale certificati medici.
</x-mail::message>
