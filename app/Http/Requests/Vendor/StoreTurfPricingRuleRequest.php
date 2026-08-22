<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Vendor\Concerns\InteractsWithPricingRuleInput;
use App\Models\Turf;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTurfPricingRuleRequest extends FormRequest
{
    use InteractsWithPricingRuleInput;

    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf && $this->user()?->can('update', $turf) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return $this->pricingRuleRules();
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validatePricingRule($validator);
        }];
    }
}
