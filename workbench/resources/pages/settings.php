<?php

declare(strict_types=1);

// Plain PHP rather than Blade, for the reason the gallery fixtures give: the
// package's compile-time preprocessor rewrites <shape:*> in every template it
// compiles, so a page written as a view could not also be read as a string.
//
// The static settings form. Every call site here names its props as literals,
// which makes this the best case for folding -- the upper bound on what turning
// `fold: true` on for the field family could ever buy. Nothing on this page is
// unusual; that is the point of it.

return [
    'title' => 'Settings',
    'summary' => 'A profile and preferences form. Every call site static, which is the ceiling for folding.',
    'markup' => <<<'BLADE'
        <shape:header>
            <shape:header.brand href="/">Acme</shape:header.brand>
            <shape:header.nav>
                <shape:header.item href="/" current>Settings</shape:header.item>
                <shape:header.item href="/records">Records</shape:header.item>
                <shape:header.item href="/dashboard">Dashboard</shape:header.item>
            </shape:header.nav>
        </shape:header>

        <main class="mx-auto max-w-2xl space-y-10 px-6 py-10">
            <div>
                <shape:heading level="1" size="lg">Settings</shape:heading>
                <p class="mt-1 text-sm text-ink-muted">Manage your profile, notifications and appearance.</p>
            </div>

            <section class="space-y-5">
                <shape:heading level="2">Profile</shape:heading>

                <shape:input name="first_name" label="First name" />
                <shape:input name="last_name" label="Last name" />
                <shape:input type="email" name="email" label="Email address" icon="at-sign" description="We never share it." />
                <shape:input type="tel" name="phone" label="Phone" />
                <shape:input type="url" name="website" label="Website" prefix="https://" />
                <shape:textarea name="bio" label="Bio" description="A short paragraph for your public profile." />
                <shape:file name="avatar" label="Profile photo" />
            </section>

            <section class="space-y-5">
                <shape:heading level="2">Organisation</shape:heading>

                <shape:input name="company" label="Company" />
                <shape:select name="industry" label="Industry">
                    <option>Software</option>
                    <option>Manufacturing</option>
                    <option>Retail</option>
                </shape:select>
                <shape:select name="size" label="Team size">
                    <option>1-10</option>
                    <option>11-50</option>
                    <option>51-200</option>
                </shape:select>
                <shape:input name="vat" label="VAT number" prefix="EU" />
                <shape:input type="number" name="seats" label="Seats" suffix="users" />
            </section>

            <section class="space-y-5">
                <shape:heading level="2">Notifications</shape:heading>

                <shape:field name="channels" legend="How should we reach you?">
                    <shape:checkbox name="channels[]" value="email" label="Email" />
                    <shape:checkbox name="channels[]" value="sms" label="SMS" />
                    <shape:checkbox name="channels[]" value="push" label="Push notifications" />
                </shape:field>

                <shape:field name="digest" legend="Digest frequency">
                    <shape:radio name="digest" value="daily" label="Daily" />
                    <shape:radio name="digest" value="weekly" label="Weekly" />
                    <shape:radio name="digest" value="never" label="Never" />
                </shape:field>

                <shape:switch name="marketing" label="Product announcements" />
                <shape:switch name="security" label="Security alerts" />
            </section>

            <section class="space-y-5">
                <shape:heading level="2">Appearance</shape:heading>

                <shape:color name="accent" label="Accent colour" />
                <shape:range name="density" label="Interface density" />
            </section>

            <div class="flex items-center justify-end gap-3 border-t border-hairline pt-6">
                <shape:button variant="ghost">Cancel</shape:button>
                <shape:button variant="solid" color="primary" icon="check">Save changes</shape:button>
            </div>
        </main>
        BLADE,
];
