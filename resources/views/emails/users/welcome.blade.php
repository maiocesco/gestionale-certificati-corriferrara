<x-mail::message>
Ciao {{ $firstName }},

lo staff di A.S.D. Corriferrara ha creato il tuo account sul gestionale dei certificati medici.
Da qui potrai controllare la scadenza del tuo certificato e caricare quello nuovo quando serve.

Per iniziare:
1. accedi con questi dati:
   - email: {{ $email }}
   - password provvisoria: **{{ $temporaryPassword }}**
2. al primo accesso ti verrà chiesto di scegliere una nuova password.

<x-mail::button :url="$loginUrl">
Accedi al gestionale
</x-mail::button>

Se non ti aspettavi questa email, scrivici rispondendo a questo messaggio.

—
A.S.D. Corriferrara
Questa email è stata inviata automaticamente dal gestionale certificati medici.
</x-mail::message>
