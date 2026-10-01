# Ahmad Jan portfolio — project brief

Static portfolio site for a performance video editor (DTC / ecommerce ads).
Live at **https://supads.ajcreationz.co/**, hosted on Hostinger.
No build step, no framework, no package.json: plain HTML with inline CSS/JS.

## Pages (one folder per page)

| URL | File | What it is |
|---|---|---|
| `/` | `index.html` | **Main page**: the "Raw-to-Reel" short-form funnel (copy doc: *Raw-to-Reel Funnel Page Copy*). Hero → proof bar → Reels → Problem → Before/After + Speed ramps → Viral Edit Formula → Offer → Testimonials → How it works → About → Free-sample form → FAQ → Final CTA |
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

### `/` (Raw-to-Reel funnel) sections
Follows the funnel copy top to bottom. Section ids: `#ng-hero`, `#ng-reels`, `#ng-problem`, `#ng-beforeafter`, `#ng-speedramp`, `#ng-formula`, `#ng-offer`, `#ng-testimonials`, `#ng-how`, `#ng-about`, `#ng-form`, `#ng-qa` (+ final CTA `.rr-final` just above the footer).
- Nav: Home, Reels, How it works, Pricing, FAQs, plus a "Free sample" button that scrolls to `#ng-form`.
- **Video grids** (`.sf-masonry`, class prefix `sf-`): Reels = 12 videos, Speed ramps = 7, Before/After = 2 (raw on the left, final on the right). Videos are mixed with testimonial and promo cards. A small script deals the cards left-to-right into the shortest column, so **the first cards in the HTML are the top row** (to put a video "on top", move it earlier in the list). Without JS it falls back to plain CSS columns.
- Removed on purpose: Reels `1231023666`, Speed ramps `1220462113` (the owner flagged them).
- New sections use class prefix `rr-` (problem, formula, offer, testimonials, how it works, about). The **Problem** section has editing-tool tiles on both sides (Premiere Pro, After Effects, Final Cut Pro, CapCut, Higgsfield, DaVinci Resolve): they are CSS letter-mark stand-ins, not official logos; swap in official logo files under `assets/img/` if wanted.
- **Form** = free-sample request (`name`, `email`, `clip` link, `platform`, `about`) → `/assets/php/send-form.php` (extra fields are optional there, so the other pages' forms still work).
- Still placeholders from the copy doc: prices (Offer tiers show "Get a quote"), the "[X]+ videos edited" and "[X]M+ views" proof numbers (left out), the scarcity line (left out, only add it if true), result numbers under each reel, and the three testimonials (reused placeholder quotes).

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
Testimonials/avatars (initials), client logo chips and stats on `/ugc-videos/` and `/long-form/`, and the pricing / proof numbers on `/` (see the `/` section above) are placeholders until real data exists.

## Testing locally
`python3 -m http.server` in the repo root, then open `/`, `/ugc-videos/`, `/long-form/`. Vimeo is blocked in some sandboxes, so video slots may render as empty boxes even though the layout is right.
