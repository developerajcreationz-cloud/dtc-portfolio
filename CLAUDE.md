# Ahmad Jan portfolio — project brief

Static portfolio site for a performance video editor (DTC / ecommerce ads).
Live at **https://supads.ajcreationz.co/**, hosted on Hostinger.
No build step, no framework, no package.json: plain HTML with inline CSS/JS.

## Pages

| URL | File | What it is |
|---|---|---|
| `/` | `index.html` | Main page: hero, works masonry, journey, logo/word-cloud strip, full works grid, core principles, contact form, FAQ |
| `/short-form/` | `short-form/index.html` | Reels landing page (vertical 9:16). Not linked from the home page |
| `/long-form/` | `long-form/index.html` | Long-form landing page (landscape, click-to-play popup) |
| — | `assets/` | `js/lenis.min.js` (smooth scroll, vendored), `img/favicon.svg`, `php/send-form.php` (contact form mailer) |

Subpages use `../assets/...` paths. The home page uses `assets/...`.

## How each page is built

Every page is one self-contained file: sections are stacked top to bottom, each with its own `<style>` and `<script>` block. Class prefix `ng-` is the shared template (nav, hero, cards, core principles, form, FAQ). Section-specific prefixes: `sf-` (short-form video sections), `lf-` (long-form).
The Journey, Core Principles, contact form and FAQ sections are copied unchanged from the home page into the subpages. Edit one, and you have to edit it in all three files.

### Card types (masonry, CSS columns: 5 desktop / 3 tablet / 2 mobile)
- **Video** `ng-card--video` > `.ng-vidwrap` with a Vimeo *background-mode* iframe (muted, looping, autoplay, no controls) and a `vumbnail.com/<id>.jpg` poster behind it.
- **Testimonial** `ng-card--quote`, **promo** `ng-card--promo` (`--orange`, `--dark`, `--green`), CTA card.
- Cards fade in on scroll via `[data-anim]` + IntersectionObserver.

### `/short-form/` sections
- `#ng-reels`: 13 reels, `#ng-speedramp`: 8 speed ramps, `#ng-beforeafter`: Before / After pair with a testimonial below. Each is a masonry mixing videos, testimonials and promo cards, like the home page grid.
- Nav: Home, Reels, Journey, Speed Ramps, FAQs.

### `/long-form/`
- `#ng-longform`: masonry-style grid (3/2/1 cols) where every cell is 16:9, so videos are never cropped. `.lf-card--video` cards (`data-vimeo-id`, `data-title`) autoplay muted via a Vimeo background iframe, unmute on hover, and open a modal player (`#lf-modal`) with the full Vimeo player on click. Testimonial (`.lf-card--quote`) and promo (`.lf-card--promo`) cards are mixed in. `.lf-card--wide` = 2x2 cells. **To swap a video, change `data-vimeo-id` (and the ID inside the iframe `src`/poster URL).**

### Videos
- Vimeo ID = the number in the URL (`vimeo.com/reviews/<review-id>/videos/<VIDEO_ID>` → use `VIDEO_ID`).
- Embed: `https://player.vimeo.com/video/<ID>?background=1&autoplay=1&loop=1&muted=1&autopause=0&byline=0&title=0&portrait=0&dnt=1`.
- **Private videos need a hash** (`?h=<hash>`) to embed, and their `vumbnail` posters won't load. If a slot shows "This video does not exist", the video is private or embeds are domain-restricted: get the share/embed link, or set the video to allow embedding on `supads.ajcreationz.co`.
- **Hover-to-unmute** (global script at the bottom of each page): `.ng-vidwrap` clips unmute on hover via the Vimeo Player SDK, one at a time. The two **hero pill clips (`.ng-vid`) are deliberately excluded and always stay muted.**
- **Hover focus** (same script, `/` and `/short-form/`): while one clip is hovered the others pause and fade to dark black, the hovered one stays bright; leaving restores all. Switch with `var HOVER_MODE = 'both'` (`'pause'` = only pause, `'fade'` = only fade). Desktop only (`hover: hover`).
- Right-click/drag are blocked on `[data-vimeo-protect]` as a deterrent only. Real protection is Vimeo's own privacy settings.

### Contact form
`#ng-form` POSTs JSON to `assets/php/send-form.php` (PHP `mail()`, recipient set at the top of that file). Needs PHP hosting (Hostinger has it).

## Git and deploy

- Hosting: Hostinger **Git deployment**. Deploy = hPanel → Git → Deploy (merging does not deploy unless auto-deploy is enabled). Confirm which branch Hostinger tracks: it is most likely **`main`**.
- Branches: `main` (deploy branch) and `claude/ahmad-jan-portfolio-review-nsh4wy` (GitHub default branch). Their histories diverged once, so **keep both in sync** or you will get "page missing" 404s.
- Claude sessions work on a `claude/<name>` branch and open a PR. Merge, redeploy, then hard-refresh (Ctrl+Shift+R) to get past caching.
- A new page = a new folder with `index.html`, giving the clean URL `/<folder>/`. It only goes live once the folder is on the deployed branch.
- A Hostinger 404 with a skateboarder graphic means the file is not on the server.

## Placeholders to replace before/when real data exists
Hero stats (60+ brands, $20m+, 5k+), client logo chips, testimonials and avatars (initials), and promo copy are placeholder content shared across the pages.

## Testing locally
`python3 -m http.server` in the repo root, then open `/`, `/short-form/`, `/long-form/`. Vimeo is blocked in some sandboxes, so video slots may render as empty boxes even though the layout is right.
