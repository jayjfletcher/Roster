# Model Conventions

```php
/**
 * @property string $id
 * @property string $slug
 * @property Carbon|null $created_at
 */
final class Prompt extends Model
{
    /** @use HasFactory<PromptFactory> */
    use HasFactory;
    use HasUlids;

    protected $table = 'roster_prompts';

    protected $fillable = ['name', 'slug', 'description'];

    /** @return HasMany<PromptVersion, $this> */
    public function versions(): HasMany { ... }

    protected static function newFactory(): PromptFactory { ... }
}
```

- `final`; explicit `$table` with `roster_` prefix (package tables live in host apps)
- `HasUlids` on every model — ULIDs avoid id collisions on export/import between environments and don't leak row counts; pairs with slug as public identity
- `@property` docblock for every column; relations carry generics (`HasMany<PromptVersion, $this>`)
- Explicit `$fillable` (no `$guarded = []`), explicit FK names in relations
- `newFactory()` points at the package factory namespace
