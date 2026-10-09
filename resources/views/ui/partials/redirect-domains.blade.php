{{--
    An owner's MCP redirect domains, kept by Cortex: the origins MCP clients
    may register OAuth redirect URIs on. Expects $domains, $manage (whether
    the viewer may add and remove them), $storeRoute and $destroyRoute (a
    closure from a domain to its delete URL).
--}}
<p class="text-sm opacity-70">{{ __('roster::roster.redirect_domains_hint') }}</p>

@if ($manage)
    <form method="POST" action="{{ $storeRoute }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <x-atrium::form.input name="domain" :label="__('roster::roster.domain')" :hint="__('roster::roster.redirect_domain_input_hint')" placeholder="https://claude.ai" wrapper="w-80" required />
        <x-atrium::form.actions>
            <x-atrium::icon-button icon="plus" :label="__('roster::roster.add_domain')" type="submit" variant="primary" data-testid="add-redirect-domain" />
        </x-atrium::form.actions>
    </form>
@endif

@if ($domains->isEmpty())
    <x-atrium::empty-state :title="__('roster::roster.no_redirect_domains')" />
@else
    <x-atrium::table striped>
        <x-slot:head>
            <x-atrium::table.row>
                <x-atrium::table.cell heading>{{ __('roster::roster.domain') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading>{{ __('roster::roster.created') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
            </x-atrium::table.row>
        </x-slot:head>

        @foreach ($domains as $domain)
            <x-atrium::table.row>
                <x-atrium::table.cell><code class="text-xs">{{ $domain->domain }}</code></x-atrium::table.cell>
                <x-atrium::table.cell>{{ $domain->created_at?->diffForHumans() }}</x-atrium::table.cell>
                <x-atrium::table.cell>
                    @if ($manage)
                        <form method="POST" action="{{ $destroyRoute($domain) }}" class="flex justify-end">
                            @csrf
                            @method('DELETE')
                            <x-atrium::icon-button icon="trash" :label="__('roster::roster.remove')" type="submit" size="sm" variant="ghost" data-testid="remove-redirect-domain" />
                        </form>
                    @endif
                </x-atrium::table.cell>
            </x-atrium::table.row>
        @endforeach
    </x-atrium::table>
@endif
