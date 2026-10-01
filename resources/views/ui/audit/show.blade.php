@inject('directory', 'JayI\Roster\Support\Users')

<x-atrium::layout :title="$entry->action">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$entry->action" :description="$entry->created_at->toDayDateTimeString()">
        <x-slot:actions>
            <x-atrium::button variant="ghost" :href="route('atrium.roster.audit.index')">{{ __('roster::roster.audit_log') }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <x-atrium::card :title="__('roster::roster.details')">
            <dl class="grid grid-cols-3 gap-2 text-sm">
                <dt class="opacity-70">{{ __('roster::roster.source_label') }}</dt><dd class="col-span-2">{{ $entry->source }}</dd>
                <dt class="opacity-70">{{ __('roster::roster.subject') }}</dt><dd class="col-span-2">{{ $entry->subject_label ?? __('roster::roster.none') }} <span class="opacity-60">{{ $entry->subject_type }} {{ $entry->subject_id }}</span></dd>
                <dt class="opacity-70">{{ __('roster::roster.actor') }}</dt><dd class="col-span-2">{{ $entry->actor ? ($directory->name($entry->actor) ?? $directory->email($entry->actor)) : __('roster::roster.system') }}</dd>
                <dt class="opacity-70">{{ __('roster::roster.organization') }}</dt><dd class="col-span-2">{{ $entry->organization?->name ?? __('roster::roster.none') }}</dd>
                <dt class="opacity-70">{{ __('roster::roster.surface') }}</dt><dd class="col-span-2">{{ $entry->surface }}</dd>
                <dt class="opacity-70">{{ __('roster::roster.hash') }}</dt><dd class="col-span-2 break-all font-mono text-xs">{{ $entry->hash }}</dd>
            </dl>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.changes')">
            @if (empty($entry->changes))
                <p class="text-sm">{{ __('roster::roster.no_changes') }}</p>
            @else
                <x-atrium::table>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.field') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.before') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.after') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($entry->changes as $field => [$old, $new])
                        <x-atrium::table.row>
                            <x-atrium::table.cell><code>{{ $field }}</code></x-atrium::table.cell>
                            <x-atrium::table.cell>{{ is_scalar($old) || $old === null ? var_export($old, true) : json_encode($old) }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ is_scalar($new) || $new === null ? var_export($new, true) : json_encode($new) }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
            @endif

            @if (! empty($entry->context))
                <pre class="mt-4 overflow-x-auto text-xs">{{ json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </x-atrium::card>
    </div>
</x-atrium::layout>
