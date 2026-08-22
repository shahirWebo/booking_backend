<?php

namespace App\Http\Requests\Vendor;

use App\Models\Turf;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTurfAvailabilityScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turf = $this->route('turf');

        return $turf instanceof Turf
            && $this->user()?->can('update', $turf) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'availability_rules' => ['required', 'array', 'max:7'],
            'availability_rules.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'availability_rules.*.is_active' => ['required', 'boolean'],
            'availability_rules.*.time_ranges' => ['present', 'array'],
            'availability_rules.*.time_ranges.*.starts_at_time' => ['required', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'availability_rules.*.time_ranges.*.ends_at_time' => ['required', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'availability_rules.*.time_ranges.*.ends_next_day' => ['required', 'boolean'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }

            foreach ($this->availabilitySchedule() as $ruleIndex => $rule) {
                if ($rule['is_active'] && $rule['time_ranges'] === []) {
                    $validator->errors()->add(
                        "availability_rules.$ruleIndex.time_ranges",
                        'An active weekday requires at least one availability time range.',
                    );
                }

                $this->validateRangeWindows($validator, $ruleIndex, $rule['time_ranges']);
            }
        });
    }

    /**
     * @return list<array{weekday: int, is_active: bool, time_ranges: list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>}>
     */
    public function availabilitySchedule(): array
    {
        $schedule = [];

        foreach ($this->input('availability_rules', []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $ranges = [];

            foreach ($rule['time_ranges'] ?? [] as $range) {
                if (! is_array($range)) {
                    continue;
                }

                $ranges[] = [
                    'starts_at_time' => $this->normalizeTime((string) ($range['starts_at_time'] ?? '')),
                    'ends_at_time' => $this->normalizeTime((string) ($range['ends_at_time'] ?? '')),
                    'ends_next_day' => (bool) ($range['ends_next_day'] ?? false),
                ];
            }

            usort($ranges, fn (array $left, array $right): int => $left['starts_at_time'] <=> $right['starts_at_time']);

            $schedule[] = [
                'weekday' => (int) ($rule['weekday'] ?? 0),
                'is_active' => (bool) ($rule['is_active'] ?? false),
                'time_ranges' => $ranges,
            ];
        }

        usort($schedule, fn (array $left, array $right): int => $left['weekday'] <=> $right['weekday']);

        return $schedule;
    }

    /**
     * @param  list<array{starts_at_time: string, ends_at_time: string, ends_next_day: bool}>  $ranges
     */
    private function validateRangeWindows(Validator $validator, int $ruleIndex, array $ranges): void
    {
        $normalized = [];

        foreach ($ranges as $rangeIndex => $range) {
            $start = $this->minutesSinceMidnight($range['starts_at_time']);
            $end = $this->minutesSinceMidnight($range['ends_at_time']);
            $endsNextDay = $range['ends_next_day'];

            if ((! $endsNextDay && $end <= $start) || ($endsNextDay && $end >= $start)) {
                $validator->errors()->add(
                    "availability_rules.$ruleIndex.time_ranges.$rangeIndex.ends_at_time",
                    'The end time must be after the start time, accounting for next-day ranges.',
                );
            }

            $normalized[] = [
                'range_index' => $rangeIndex,
                'start' => $start,
                'end' => $endsNextDay ? $end + 1440 : $end,
            ];
        }

        usort($normalized, fn (array $left, array $right): int => $left['start'] <=> $right['start']);

        foreach ($normalized as $position => $range) {
            if ($position > 0 && $range['start'] < $normalized[$position - 1]['end']) {
                $validator->errors()->add(
                    "availability_rules.$ruleIndex.time_ranges.{$range['range_index']}.starts_at_time",
                    'Availability time ranges cannot overlap on the same weekday.',
                );
            }
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "$time:00" : $time;
    }

    private function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
