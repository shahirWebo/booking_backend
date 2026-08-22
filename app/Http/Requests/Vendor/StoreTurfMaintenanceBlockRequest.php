<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

final class StoreTurfMaintenanceBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf && $this->user()?->can('update', $turf) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'starts_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at_local' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at_local'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $turf = $this->route('turf');

            if (! $turf instanceof Turf || $validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (['starts_at_local', 'ends_at_local'] as $key) {
                $value = (string) $this->input($key);
                $instant = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $value, $turf->location->timezone);

                if ($instant === null || $instant->format('Y-m-d\\TH:i') !== $value) {
                    $validator->errors()->add($key, 'Choose a valid local time in the turf timezone.');
                }
            }
        }];
    }

    public function startsAt(Turf $turf): CarbonImmutable
    {
        return $this->localInstant((string) $this->validated('starts_at_local'), $turf);
    }

    public function endsAt(Turf $turf): CarbonImmutable
    {
        return $this->localInstant((string) $this->validated('ends_at_local'), $turf);
    }

    public function reason(): ?string
    {
        return filled($this->validated('reason')) ? trim((string) $this->validated('reason')) : null;
    }

    private function localInstant(string $value, Turf $turf): CarbonImmutable
    {
        $instant = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $value, $turf->location->timezone);

        if ($instant === null || $instant->format('Y-m-d\\TH:i') !== $value) {
            throw new RuntimeException('The local maintenance time could not be resolved.');
        }

        return $instant->utc();
    }
}
