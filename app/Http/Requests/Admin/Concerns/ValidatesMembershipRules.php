<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\CertificateType;
use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\Certificate;
use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesMembershipRules
{
    protected function membershipRules(User $user, ?int $ignoreMembershipId = null): array
    {
        return [
            'entity' => ['required', Rule::enum(MembershipEntity::class)],
            'discipline' => [
                'required',
                Rule::enum(Discipline::class),
                $this->clubDisciplineRule(),
                $this->fidalDisciplineRule(),
                $this->fidalRequiresCertificateRule($user),
                $this->uniqueEntityDisciplineRule($user, $ignoreMembershipId),
            ],
            'club_type' => ['required', Rule::enum(ClubType::class)],
            'external_club_id' => [
                Rule::requiredIf(fn () => $this->input('club_type') === ClubType::External->value && ! $this->filled('external_club_name')),
                'nullable', 'exists:external_clubs,id',
            ],
            'external_club_name' => ['nullable', 'string', 'max:255'],
            'card_number' => ['required', 'string', 'max:100'],
            'is_competitive' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * La disciplina deve rientrare tra quelle ammesse per il tipo di società.
     */
    protected function clubDisciplineRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $clubType = $this->input('club_type') ? ClubType::tryFrom($this->input('club_type')) : null;
            $discipline = Discipline::tryFrom((string) $value);

            if (! $clubType || ! $discipline) {
                return;
            }

            $allowed = $clubType->allowedDisciplines();

            if ($allowed !== null && ! in_array($discipline, $allowed, true)) {
                $fail(__('La disciplina scelta non è compatibile con la società selezionata.'));
            }
        };
    }

    /**
     * FIDAL è ammesso solo per la disciplina atletica leggera.
     */
    protected function fidalDisciplineRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $entity = $this->input('entity') ? MembershipEntity::tryFrom($this->input('entity')) : null;
            $discipline = Discipline::tryFrom((string) $value);

            if ($entity === MembershipEntity::Fidal && $discipline !== Discipline::AtleticaLeggera) {
                $fail(__('Il tesseramento FIDAL è ammesso solo per l\'atletica leggera.'));
            }
        };
    }

    /**
     * Non si può aggiungere un tesseramento FIDAL senza un certificato
     * agonistico già valido per l'atletica leggera.
     */
    protected function fidalRequiresCertificateRule(User $user): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($user): void {
            $entity = $this->input('entity') ? MembershipEntity::tryFrom($this->input('entity')) : null;

            if ($entity !== MembershipEntity::Fidal) {
                return;
            }

            $hasValidAthleticsCertificate = Certificate::where('user_id', $user->id)
                ->valid()
                ->where('type', CertificateType::Competitive->value)
                ->where('discipline', Discipline::AtleticaLeggera->value)
                ->exists();

            if (! $hasValidAthleticsCertificate) {
                $fail(__('Per aggiungere un tesseramento FIDAL l\'atleta deve avere già un certificato agonistico valido per l\'atletica leggera.'));
            }
        };
    }

    /**
     * Niente due tesseramenti identici (stessa disciplina e stesso ente).
     */
    protected function uniqueEntityDisciplineRule(User $user, ?int $ignoreMembershipId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($user, $ignoreMembershipId): void {
            $exists = $user->memberships()
                ->where('entity', $this->input('entity'))
                ->where('discipline', $this->input('discipline'))
                ->when($ignoreMembershipId, fn ($query) => $query->whereKeyNot($ignoreMembershipId))
                ->exists();

            if ($exists) {
                $fail(__('L\'atleta ha già un tesseramento per questa disciplina con lo stesso ente.'));
            }
        };
    }
}
