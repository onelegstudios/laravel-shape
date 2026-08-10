<?php

declare(strict_types=1);

// The page the stage 3 decision actually turns on.
//
// A form driven by data rather than written out: `wire:model` bindings, a loop
// over a field definition with `:label` and `:name` bound to variables, and an
// error bag with messages in it. Folding is a compile-time evaluation, so a prop
// whose value is only known at render declines it -- and this is what a form in a
// Livewire application usually looks like.
//
// It is the fixture that answers the question the timings alone cannot: not how
// fast a folded field is, but what share of real call sites can fold at all.

return [
    'title' => 'Livewire form',
    'summary' => 'A data-driven form with bindings, a field loop and validation errors. The case that declines to fold.',
    'errors' => [
        'form.email' => ['The email address is not valid.'],
        'form.password' => ['The password must be at least 12 characters.'],
    ],
    'data' => fn (): array => [
        'fields' => [
            ['name' => 'form.street', 'label' => 'Street', 'type' => 'text'],
            ['name' => 'form.city', 'label' => 'City', 'type' => 'text'],
            ['name' => 'form.postcode', 'label' => 'Postcode', 'type' => 'text'],
            ['name' => 'form.country', 'label' => 'Country', 'type' => 'text'],
            ['name' => 'form.vat', 'label' => 'VAT number', 'type' => 'text'],
            ['name' => 'form.contact', 'label' => 'Billing contact', 'type' => 'text'],
            ['name' => 'form.contact_email', 'label' => 'Billing email', 'type' => 'email'],
            ['name' => 'form.reference', 'label' => 'Purchase order', 'type' => 'text'],
        ],
        'plans' => ['starter' => 'Starter', 'team' => 'Team', 'business' => 'Business'],
    ],
    'markup' => <<<'BLADE'
        <shape:header>
            <shape:header.brand href="/">Acme</shape:header.brand>
            <shape:header.nav>
                <shape:header.item href="/settings">Settings</shape:header.item>
                <shape:header.item href="/records">Records</shape:header.item>
                <shape:header.item href="/livewire" current>Account</shape:header.item>
            </shape:header.nav>
        </shape:header>

        <main class="mx-auto max-w-2xl space-y-10 px-6 py-10">
            <div>
                <shape:heading level="1" size="lg">Account</shape:heading>
                <p class="mt-1 text-sm text-ink-muted">Bound to a Livewire component.</p>
            </div>

            {{-- Bindings rather than names. `wire:model` is a static attribute, so
                 these fold on the attribute; what they cannot fold on is the error
                 bag, which is read per request. --}}
            <section class="space-y-5">
                <shape:heading level="2">Credentials</shape:heading>

                <shape:input wire:model="form.email" type="email" label="Email address" icon="at-sign" />
                <shape:input wire:model.live="form.password" type="password" label="Password" description="At least 12 characters." />
                <shape:input wire:model="form.password_confirmation" type="password" label="Confirm password" />
            </section>

            {{-- One call site, eight renders, and every prop on it dynamic. This is
                 the loop that decides whether folding reaches a real form. --}}
            <section class="space-y-5">
                <shape:heading level="2">Billing address</shape:heading>

                @foreach ($fields as $field)
                    <shape:input
                        :type="$field['type']"
                        :name="$field['name']"
                        :label="$field['label']"
                        wire:model="{{ $field['name'] }}"
                    />
                @endforeach
            </section>

            <section class="space-y-5">
                <shape:heading level="2">Plan</shape:heading>

                <shape:field name="form.plan" legend="Choose a plan">
                    @foreach ($plans as $value => $label)
                        <shape:radio wire:model="form.plan" :value="$value" :label="$label" />
                    @endforeach
                </shape:field>

                <shape:switch wire:model="form.annual" label="Bill annually" />
            </section>

            <div class="flex items-center justify-end gap-3 border-t border-hairline pt-6">
                <shape:button variant="ghost" wire:click="cancel">Cancel</shape:button>
                <shape:button variant="solid" color="primary" wire:click="save" :loading="false">Save</shape:button>
            </div>
        </main>
        BLADE,
];
