# Checkbox

A box Shape draws itself, with its tick and its indeterminate bar from your icon set:

```blade
<shape:checkbox label="Email me about releases" description="About once a month." name="notify" value="1" />
```

The label sits **beside** the box rather than above it — a checkbox's words name the option next to
them, not the field over them — so this component is a row where the others are a column. A long
label wraps under its own first line instead of under the box.

`size` gives boxes of 16, 18, 20 and 24px, each paired with a mark that leaves a 2px inset and each
sitting inside its label's own line box, so the row aligns with no margin to tune. `xs` keeps its
smallness in the type and the gap: the box floor is 16px, because 14 with a 12 inside it is a target
nobody can hit. Only `lg` clears
[WCAG 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)'s 24px on the box
alone — what covers the rest is the label, which is part of the same target.

## Groups

A group is a [`<shape:field>`](field.md) and some boxes. There is no group component to learn:

```blade
<shape:field name="tags" legend="Tags" description="Pick any that apply.">
    <shape:checkbox value="php" label="PHP" />
    <shape:checkbox value="laravel" label="Laravel" />
</shape:field>
```

Each box derives its own id from its `value` — `tags-php`, `tags-laravel` — so each label clicks
through to its own box rather than all of them to the first, and each help text answers to an id of
its own. The **message belongs to the field**, which prints it once: a validator has one opinion per
name however many controls carry it, and three copies of one sentence is not a message.

### `legend` rather than `label`

Naming `legend` is what opens a `<fieldset>` with a `<legend>` instead of a `<div>` with a `<label>`,
and a group wants one. Boxes wired by `name` are visually a set and, without the element, unrelated
controls to anything reading the page — a screen reader announces three checkboxes and a floating
word. A `label` on a group is also a `for` pointing at `tags`, which is an id no box carries; the
options are `tags-php` and `tags-laravel`. The legend has no `for` at all, because it names the
fieldset it opens by sitting in it.

The two props are one decision, so naming both draws the legend and drops the label rather than
rendering a dangling pair.

The group's **help text is named on the fieldset** via `aria-describedby`, because the field drew it
and knows the id exists. The **message is not**, and deliberately: every box already points at
`tags-error` itself, so a fieldset naming it too would have the sentence read on entering the group
and again on the first box.

One thing measures differently from a plain field. A rendered `<legend>` is painted into its
fieldset's border box rather than laid out as a child, so no `gap` can reach it — the column lives on
a wrapper inside and the legend carries its own margin. That means `class` on a group styles the
outer box, which is what you want for `max-w-sm` or a border of your own, and what you do not want
for `gap-4`.

## Standing alone

Standing on its own, a checkbox *is* the whole field, so it prints its own message. A consent box
that fails validation silently is the bug that covers.

## Indeterminate

Indeterminate has no HTML attribute — set the property:

```blade
<shape:checkbox label="Select all" id="all" />
<script>document.getElementById('all').indeterminate = true</script>
```

A box can be checked and indeterminate at once, and the bar wins.

The two marks resolve through `checkbox-check` and `checkbox-indeterminate`, which both libraries
happen to spell `check` and `minus`. They are separate names anyway, so repointing the mark inside a
checkbox does not repoint every `<shape:icon name="check">` you wrote yourself.

---

[← Textarea](textarea.md) · [Index](../README.md) · [Radio →](radio.md)
