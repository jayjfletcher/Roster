# Atrium Views

Atrium owns every component and style in the jayi suite. Roster ships no stylesheet and no Blade component namespace.

- Views use `x-atrium::*` components (`form.actions` for a form row's buttons, `description-list` for label/value pairs, `flash` for the status and first error, `guest` for standalone pages such as the invitation page) and only the utilities safelisted in Atrium's `resources/css/atrium.css`
- No `<style>` blocks and no `style=` attributes. `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are empty for `resources/views`
- A class missing from Atrium's safelist: use the closest safelisted one and ask for it in Atrium; never add CSS here
- Shared markup is a plain partial included with every key it reads (`@include('roster::ui.partials.status-dot', ['status' => …, 'variant' => null, 'label' => null, 'testid' => null])`), so the including view's variables never leak in
- Markup shown on the host application's own pages, where Atrium's stylesheet is absent, uses Atrium's `standalone` components: the impersonation banner (`roster::impersonation-banner`) is `<x-atrium::banner standalone>` with `<x-atrium::banner.button standalone>`
- A record's history is `<x-atrium::audit-trail source="roster" :subject="$model" />`, behind `@rosterCan('roster.audit.view', $scope)`; it renders nothing until jayi/keen is installed
- Test hooks are `data-testid` attributes
