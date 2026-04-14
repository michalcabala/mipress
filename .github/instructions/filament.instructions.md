---
applyTo: "packages/mipress/**/src/Filament/**"
---

# Filament Wrapper Instructions

Primární pravidla jsou v:

- `.github/instructions/mipress.instructions.md`

Tento wrapper přidává pouze Filament-specific akcenty:

- Respektuj aktuální cluster/navigation strukturu (`ContentCluster`, `WebCluster`) a existující group/label naming v kódu.
- `Entry` formuláře drž jako Mason (`data.content`) + Blueprint custom fields.
- `Page` formuláře drž jako Mason (`content`).
- Blueprint dynamiku stav na `FieldTypeRegistry` + `BlueprintFieldResolver`.
- Breadcrumbs vypínej pouze na konkrétních list stránkách (`getBreadcrumbs(): array`), ne globálně v panelu.
- Používej Curator komponenty pro media fields.
