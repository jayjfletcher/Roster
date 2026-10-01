# MCP Model Binding

Slug→model resolution lives in an abstract per-model request base — the MCP analog of route-model binding:

```php
abstract class PromptMcpRequest extends Request
{
    private ?Prompt $prompt = null;

    protected function prompt(): Prompt
    {
        return $this->prompt ??= Prompt::query()
            ->where('slug', $this->get('slug'))
            ->firstOrFail();
    }
}
```

- Always create the per-model base for any slug→model lookup, even for a single tool — consistency over YAGNI
- Memoize with `??=` — rules and handle may both need the model
- Use `firstOrFail()`; base `persist()` catches ModelNotFoundException and returns `Response::error('Not found.')` — no manual existence checks
- Concrete requests add `'slug' => ['required', 'string']` to rules alongside the action's rules
