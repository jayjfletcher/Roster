{{-- Protocol settings. Which apply depends on the protocol; the Action checks the right ones are present. --}}
<div class="grid gap-3 sm:grid-cols-2">
    <x-atrium::form.input name="issuer" :label="__('roster::roster.protocol_oidc').': issuer'" :value="old('issuer', $settings['issuer'] ?? null)" />
    <x-atrium::form.input name="tenant" :label="__('roster::roster.protocol_azure').': tenant'" :value="old('tenant', $settings['tenant'] ?? null)" :hint="__('roster::roster.tenant_hint')" />
    <x-atrium::form.input name="client_id" label="Client ID" :value="old('client_id', $settings['client_id'] ?? null)" />
    <x-atrium::form.input name="client_secret" type="password" label="Client secret" :hint="$editing ? __('roster::roster.client_secret_hint') : null" />
    <x-atrium::form.input name="metadata_url" :label="__('roster::roster.protocol_saml').': metadata URL'" :value="old('metadata_url', $settings['metadata_url'] ?? null)" />
    <x-atrium::form.input name="entity_id" :label="__('roster::roster.protocol_saml').': entity ID'" :value="old('entity_id', $settings['entity_id'] ?? null)" />
    <x-atrium::form.input name="sso_url" :label="__('roster::roster.protocol_saml').': SSO URL'" :value="old('sso_url', $settings['sso_url'] ?? null)" />
    <x-atrium::form.textarea name="certificate" :label="__('roster::roster.protocol_saml').': certificate'" rows="2" />
</div>
<div class="flex flex-wrap gap-4">
    <x-atrium::form.checkbox name="jit" value="1" :checked="$jit ?? true" :label="__('roster::roster.jit')" />
    <x-atrium::form.checkbox name="enforced" value="1" :checked="$enforced ?? false" :label="__('roster::roster.enforced')" />
    <x-atrium::form.checkbox name="enabled" value="1" :checked="$enabled ?? true" :label="__('roster::roster.enabled')" />
</div>
