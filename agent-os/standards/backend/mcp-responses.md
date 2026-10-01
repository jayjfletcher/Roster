# MCP Response Shape

All structured MCP responses wrap payloads in a `data` envelope:

```php
// lists
return $this->structuredCollection(PromptResource::collection($prompts)->resolve());
// → { "data": [ ... ] }

// single items — same envelope
return Response::structured(['data' => (new PromptResource($prompt))->resolve()]);
```

- Serialize through the same `Http/Resources` classes as the HTTP API — never hand-build arrays
- Envelope is mandatory for lists: `Response::structured([])` throws, so an empty list must ship as `{ "data": [] }` (that's why `structuredCollection()` exists)
- Some older single-item responses return the bare resource with no envelope — legacy; wrap in `data` when touched, don't copy
- Errors: `Response::error('...')` — base request maps ModelNotFoundException → `Not found.`
