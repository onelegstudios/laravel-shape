# Media components

App-level photo art direction isn't the framework's job — but the mechanics of displaying
user content *are*, for components like `avatar`, `image`, and `media`:

- **Fix the shape and size.** Wrap uploads in a sized container with `object-cover` (and
  `aspect-*`) so arbitrary dimensions can't break the layout; center the content.
- **Prevent background bleed.** Give edge-to-edge images a subtle inner border/overlay so a
  near-background-colored image doesn't blend into the panel.
- **Don't upscale icons.** Use a correctly-sized asset, or wrap a small icon in a shape —
  scaling small art up looks blurry and cheap.
- **Guarantee contrast for text over images.** Don't depend on the image: add a
  semi-transparent overlay, lower image contrast, colorize/tint it, or add a text shadow.
