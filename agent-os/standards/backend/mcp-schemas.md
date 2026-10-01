# MCP Schema Conventions

Every schema field gets a `->description()` — it's the model-facing documentation:

```php
'slug' => $schema->string()->description('Unique identifier (letters, numbers, dashes, underscores).')->required(),
'prompt_version' => $schema->integer()->description('Pin a specific prompt version. Omit to follow the published version.')->min(1),
```

- Descriptions state defaults and behavior ("Defaults to true.", "Replaces the whole list.") — the model can't read the code
- Any schema fragment used by 2+ tools goes into a `Mcp/Tools/Concerns` trait (e.g. `DescribesAgentPayload` for create/update agent fields) so tools never drift
- Tool descriptions: `#[Description('...')]` attribute — keep it accurate; it is the model's only documentation of the tool
