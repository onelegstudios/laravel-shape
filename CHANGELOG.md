# Release Notes

## [Unreleased](https://github.com/onelegstudios/laravel-shape/compare/v0.1.0...1.x)

### Added

- Five form controls, styled with theme tokens and no `@tailwindcss/forms` dependency:
  `<shape:select>`, `<shape:textarea>`, `<shape:checkbox>`, `<shape:radio>` and `<shape:file>`. Each
  takes the input's four `size` rungs, its `invalid` handling and its `label`/`description`
  shorthand. See [Components](docs/components.md).
- Three semantic icon names for the marks those controls draw: `select-chevron`, `checkbox-check`
  and `checkbox-indeterminate`. `shape:install` publishes them unasked, and `shape:icon:remove`
  holds them back without `--force`.
- `shape:icon:check` now reports names Shape's own components render that are not published, as an
  error, and fails `--strict`. This is the only one of the four icon verbs that can see an absence,
  and it is how a build catches an upgrade that needs new artwork.
- `size` on `<shape:label>` and `<shape:description>`, for the checkbox and radio rows where the
  label shares a line with the control. Unnamed renders exactly what it did before.
- A `picker` variant in `shape.css`, and date-field styling that uses it: Chromium's calendar
  indicator is knocked back to the weight of the marks beside it and given a pointer cursor. It
  follows the colour scheme already, so no inverted filter is involved.

### Changed

- `aria-describedby` no longer names the validation message when there is no message to name.
  `:invalid="true"` on a field the validator has not seen styles the control without pointing at an
  element that was never rendered.

### Upgrading

Shape's components now render three icons they did not render before. Re-run `shape:install`, or
publish them directly — either is idempotent:

```bash
php artisan shape:icon:add select-chevron checkbox-check checkbox-indeterminate
```

`php artisan shape:icon:check --strict` reports what is missing and exits non-zero, so it can gate a
deploy.


## [v0.1.0](https://github.com/onelegstudios/laravel-shape/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
