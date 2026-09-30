# Ahmad Jan portfolio — project brief

Static portfolio site for a performance video editor (DTC / ecommerce ads).
Live at **https://supads.ajcreationz.co/**, hosted on Hostinger.
No build step, no framework, no package.json: plain HTML with inline CSS/JS.

## Pages (one folder per page)

| URL | File | What it is |
|---|---|---|
| `/` | `index.html` | **Main page**: short-form / Reels (vertical 9:16). Sections: Reels, Journey, portfolio strip, Speed ramps, Before & after, Core principles, form, FAQ |
| `/ugc-videos/` | `ugc-videos/index.html` | UGC portfolio (the original home page): works masonry, journey, logo/word-cloud strip, full works grid, core principles, form, FAQ |
| `/long-form/` | `long-form/index.html` | Long-form page (landscape 16:9 grid, click-to-play popup with close button) |
| `/short-form/` | `short-form/index.html` | Only a redirect to `/` (old URL). Safe to delete once nothing links to it |
| — | `assets/` | Shared *static files only*: `js/lenis.min.js`, `img/favicon.svg`, `php/send-form.php` (contact form mailer) |

### Rule: a page's code lives only in that page
Every page is **one self-contained file** (its own HTML, CSS and JS). There is no shared CSS/JS between pages, so **editing one page never changes another**. The flip side: something you want on every page (e.g. the hover effect, the FAQ, the form) has to be applied in each of the three files.
When adding a page: create `<name>/index.html` (copy the closest existing page), it is then served at `/<name>/`.

All pages reference assets with root-absolute paths (`/assets/...`) and link to other pages with `/`, `/ugc-videos/`, `/long-form/`, so a file works from any folder depth.

## How each page is built

Sections are stacked top to bottom, each with its own `<style>` and `<script>` block. Class prefix `ng-` is the shared template look (nav, hero, cards, core principles, form, FAQ). Page-specific prefixes: `sf-` (short-form video sections on `/`), `lf-` (long-form).
Journey, Core Principles, contact form and FAQ are copies of the same section in each file.

### Card types (masonry, CSS columns: 5 desktop / 3 tablet / 2 mobile)
- **Video** `ng-card--video` > `.ng-vidwrap` with a Vimeo *background-mode* iframe (muted, looping, autoplay, no controls) and a `vumbnail.com/<id>.jpg` poster behind it.
- **Testimonial** `ng-card--quote`, **promo** `ng-card--promo` (`--orange`, `--dark`, `--green`), CTA card.
- Cards fade in on scroll via `[data-anim]` + IntersectionObserver.

### `/` (short-form) sections
- `#ng-reels`: 13 reels, `#ng-speedramp`: 8 speed ramps, `#ng-beforeafter`: Before / After pair with a testimonial below. Each is a masonry mixing videos, testimonials and promo cards.
- Nav: Home, Reels, Journey, Speed Ramps, FAQs.

### `/long-form/`
- `#ng-longform`: grid (3/2/1 cols) where every cell is 16:9, so videos are never cropped. `.lf-card--video` cards (`data-vimeo-id`, `data-title`) autoplay muted via a Vimeo background iframe, unmute on hover, and open a modal player (`#lf-modal`) with the full Vimeo player on click. The modal has a round close button pinned top-right (also closes on backdrop click and Esc). Testimonial (`.lf-card--quote`) and promo (`.lf-card--promo`) cards are mixed in. `.lf-card--wide` = 2x2 cells. **To swap a video, change `data-vimeo-id` (and the ID inside the iframe `src`/poster URL).**
- Nav has a "Short-form" link back to `/`.

### Videos
- Vimeo ID = the number in the URL (`vimeo.com/reviews/<review-id>/videos/<VIDEO_ID>` → use `VIDEO_ID`).
- Embed: `https://player.vimeo.com/video/<ID>?background=1&autoplay=1&loop=1&muted=1&autopause=0&byline=0&title=0&portrait=0&dnt=1`.
- **Private videos need a hash** (`?h=<hash>`) to embed, and their `vumbnail` posters won't load. If a slot shows "This video does not exist", the video is private or embeds are domain-restricted: get the share/embed link, or set the video to allow embedding on `supads.ajcreationz.co`.
- **Hover-to-unmute** (global script at the bottom of each page): `.ng-vidwrap` clips unmute on hover via the Vimeo Player SDK, one at a time. The two **hero pill clips (`.ng-vid`) are deliberately excluded and always stay muted.**
- **Hover focus** (same script, **all three pages**): while one clip is hovered the others **pause** and get a light black fade (`brightness(.55)`), the hovered one stays bright; leaving restores all. Switch with `var HOVER_MODE = 'both'` (`'pause'` = only pause, `'fade'` = only fade). Desktop only (`hover: hover`). Fade strength: `body.ng-hover-fade .ng-vidwrap:not(.is-hover){filter:brightness(.55)}` in the page's first `<style>`.
- Right-click/drag are blocked on `[data-vimeo-protect]` as a deterrent only. Real protection is Vimeo's own privacy settings.

### Contact form
`#ng-form` POSTs JSON to `/assets/php/send-form.php` (PHP `mail()`, recipient set at the top of that file). Needs PHP hosting (Hostinger has it).

## Git and deploy

- **Single branch: `main`.** It is the only long-lived branch; Hostinger deploys from it. Do not keep parallel copies of the site on other branches (two diverged branches caused "page missing" 404s before).
- Claude sessions must work on a temporary `claude/<name>` branch, open a PR into `main`, and it is deleted after merge. Never treat a `claude/*` branch as a second source of truth.
- Deploy = hPanel → Git → Deploy (merging does not deploy unless auto-deploy is enabled). Then hard-refresh (Ctrl+Shift+R) to get past caching.
- A Hostinger 404 with a skateboarder graphic means the file is not on the server.
- Repo owner's to-do: GitHub → Settings → Branches → set the default branch to `main`, then delete the old `claude/*` branches.

## Placeholders to replace before/when real data exists
Hero stats (60+ brands, $20m+, 5k+), client logo chips, testimonials and avatars (initials), and promo copy are placeholder content shared across the pages.

## Testing locally
`python3 -m http.server` in the repo root, then open `/`, `/ugc-videos/`, `/long-form/`. Vimeo is blocked in some sandboxes, so video slots may render as empty boxes even though the layout is right.
