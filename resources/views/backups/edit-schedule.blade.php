<x-app-layout :title="__('Edit Backup Configuration Schedule')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-pen">
            {{ __('Edit Backup Configuration Schedule') }}
        </x-slot>

        <x-slot name="subtitle">
            {{ $configuration->label }}

            <small>
                ({{ $displayInformation['name'] }})
            </small>
        </x-slot>

        <form action="{{ route('backups.schedule.update', [$configuration]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="schedule" class="md:w-2/3">
                <x-slot name="label">{{ __('Schedule') }}</x-slot>

                <select name="schedule" class="select" id="schedule-input" onchange="updateNextRuns()">
                    <option value="" @selected($configuration->cron_schedule === null)>{{ __('None') }}</option>

                    @foreach ($availableSchedules as $schedule)
                        <option value="{{ $schedule->getValue() }}" @selected($schedule->getValue() === $configuration->cron_schedule)>{{ $schedule->getName() }}</option>
                    @endforeach

                    @if ($hasCustomSchedule)
                        <option value="custom" selected>{{ __('Custom') }}</option>
                    @endif
                </select>
            </x-form-field>

            @if ($hasCustomSchedule)
                <div class="alert bg-base-300 w-fit">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    {{ __('You can\'t update a custom schedule. When setting a new schedule the custom one will be lost.') }}
                </div>
            @endif
        </form>

        <div class="mt-4">
            <div class="font-bold mb-2">{{ __('Next possible runs') }}</div>
            
            <table>
                <tbody id="next-runs">
                    {{-- Is filled by JS below --}}
                </tbody>
            </table>
        </div>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('backups.show', [$configuration])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>

    @push('scripts')
        <script>
            const nextRunsContainer = document.getElementById('next-runs');
            const selectedScheduleInput = document.getElementById('schedule-input');

            const allNextRuns = JSON.parse('{!! json_encode($allNextRuns) !!}');

            function updateNextRuns() {
                const schedule = selectedScheduleInput.value;

                if(schedule === '') {
                    nextRunsContainer.innerHTML = '<tr><td class="italic text-base-content/75">{{ __('Never') }}</td></tr>';
                    return;
                }

                let nextRuns = allNextRuns[schedule];

                nextRunsContainer.innerHTML = nextRuns.map((run) => {
                    return `
                        <tr>
                            <td>${run.diff}</td>
                            <td class="w-4"></td>
                            <td class="text-base-content/75 text-sm">${run.timestamp}</td>
                        </tr>`;
                }).join('\n');
            }

            updateNextRuns();
        </script>
    @endpush
</x-app-layout>