<x-mail::message>
Ciao {{ $firstName }},

lo staff di A.S.D. Corriferrara ha reimpostato la password del tuo account.

Accedi con la password provvisoria: **{{ $temporaryPassword }}**
Al primo accesso dovrai sceglierne una nuova.

<x-mail::button :url="$loginUrl">
Accedi al gestionale
</x-mail::button>

Se non hai chiesto tu il reset, contattaci rispondendo a questa email.

—
A.S.D. Corriferrara
Questa email è stata inviata automaticamente dal gestionale certificati medici.
</x-mail::message>
