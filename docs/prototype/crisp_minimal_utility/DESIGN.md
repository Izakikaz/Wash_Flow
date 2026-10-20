---
name: Crisp Minimal Utility
colors:
  surface: '#f8f9ff'
  surface-dim: '#d0dbed'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dee9fc'
  surface-container-highest: '#d9e3f6'
  on-surface: '#121c2a'
  on-surface-variant: '#3c4a42'
  inverse-surface: '#27313f'
  inverse-on-surface: '#eaf1ff'
  outline: '#6c7a71'
  outline-variant: '#bbcabf'
  surface-tint: '#006c49'
  primary: '#006c49'
  on-primary: '#ffffff'
  primary-container: '#10b981'
  on-primary-container: '#00422b'
  inverse-primary: '#4edea3'
  secondary: '#006a61'
  on-secondary: '#ffffff'
  secondary-container: '#86f2e4'
  on-secondary-container: '#006f66'
  tertiary: '#005ac2'
  on-tertiary: '#ffffff'
  tertiary-container: '#71a1ff'
  on-tertiary-container: '#00367a'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#6ffbbe'
  primary-fixed-dim: '#4edea3'
  on-primary-fixed: '#002113'
  on-primary-fixed-variant: '#005236'
  secondary-fixed: '#89f5e7'
  secondary-fixed-dim: '#6bd8cb'
  on-secondary-fixed: '#00201d'
  on-secondary-fixed-variant: '#005049'
  tertiary-fixed: '#d8e2ff'
  tertiary-fixed-dim: '#adc6ff'
  on-tertiary-fixed: '#001a42'
  on-tertiary-fixed-variant: '#004395'
  background: '#f8f9ff'
  on-background: '#121c2a'
  surface-variant: '#d9e3f6'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '500'
    lineHeight: 24px
    letterSpacing: 0em
  body-default:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: 0em
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.03em
  caption:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.01em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  margin: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

The design system is crafted for high-turnover operational environments—specifically counter staff and shop managers in neighborhood laundry and fabric care businesses. The core brand character is crisp, methodical, and calm under pressure. It bridges everyday utility with modern digital efficiency, eliminating cognitive clutter while providing immediate status clarity.

The visual direction follows **Modern Minimal Utility with Tactile Clarity**. It rejects heavy ornamentation, opting for clinical precision, generous whitespace, and sharp functional accents. High-contrast typography and unmistakable status signifiers mirror the sensory associations of clean linens, fresh water, and organized workflow batches. 

The interface evokes confidence, speed, and tidiness. Every tap on small counter tablets or handheld smartphones registers decisively, ensuring high-stress operational moments (peak drop-off hours, lost receipt lookups, rapid batch processing) feel controlled and straightforward.

## Colors

The palette balances clinical cleanliness with high-legibility contrasts suited for varied ambient store lighting.

### Palette Construction
- **Primary Mint (`#10B981`):** Represents success, completion, and the primary system action. Reserved for conclusive states and focal conversion points.
- **Deep Teal (`#0D9488`):** Serves as the anchor secondary tone, lending authority to navigation, key metrics, and brand headers.
- **Tints & Washes:** Pale mint tints (`#ECFDF5`, `#D1FAE5`) provide low-friction surface fills for badges, active states, and non-blocking announcements.
- **Surfaces & Grounds:** Pure white (`#FFFFFF`) forms the elevated card base, seated upon a cool off-white app canvas (`#F8FAFC`).
- **Dividers & Structural Borders:** Light boundary lines (`#E2E8F0`, `#E5E7EB`) delimit structural elements without fragmenting screen flow.
- **Text & Contrast Tiers:** Dense charcoal (`#111827`) commands headings and primary values; neutral body text sits at (`#1F2937`). De-emphasized metadata and helper instructions use balanced gray (`#64748B`).

### Functional Workflow Status Badges
Status indicators rely on established mental models for order states:
- **Pending / Received:** Surface `#F1F5F9`, Text `#475569`, Border `#CBD5E1`.
- **Washing:** Surface `#EFF6FF`, Text `#2563EB`, Border `#BFDBFE` (`#3B82F6` core).
- **Drying:** Surface `#FFFBEB`, Text `#D97706`, Border `#FDE68A` (`#F59E0B` core).
- **Ready for Pickup:** Surface `#ECFDF5`, Text `#059669`, Border `#A7F3D0` (`#10B981` core).
- **Claimed / Archived:** Surface `#F8FAFC`, Text `#334155`, Border `#CBD5E1` (`#334155` core).

## Typography

The design system implements a strict, legible typographic hierarchy powered exclusively by Inter. To withstand wet, glancing, or mobile operational usage, the minimum font size for interactive elements and contextual body data is capped at 16px. This eradicates inadvertent iOS zoom-on-focus behaviors and secures immediate legibility at arm's length.

Numerical weights are restricted to 400 (Regular for long reads and secondary labels), 500 (Medium for standard labels and inputs), 600 (Semibold for structure and badges), and 700 (Bold for titles, tracking IDs, and financial summaries). Letter spacing tightens moderately on large headings to conserve horizontal real estate on narrow screens, and broadens slightly on 12px uppercase indicators to maintain crisp glyph distinction.

## Layout & Spacing

The design system adopts a **mobile-first fluid column structure** designed to anchor seamlessly to single-hand operation before scaling outward.

### Breakpoint & Layout Rhythm
- **Mobile (<640px):** 1-column stack. Page margins use `margin` (`1rem` / 16px). Cards span the full safe width. Vertical rhythm strictly follows multiples of 4px and 8px.
- **Tablet (640px - 1024px):** 2-column masonry or side-by-side split screen (e.g., live intake list on the left, order inspector on the right). Canvas margin expands to 24px (`space-lg`), column gutters sit at `1rem`.
- **Desktop (>1024px):** Fixed max-width container (1200px) anchored with a narrow persistent navigation rail (72px) or collapsible sidebar (240px) and a multi-column operational board.

### Safe Zones & Touch Constraints
- All active touch nodes feature an uncompromising floor height of **48px** (exceeding the 44px baseline) to prevent mistaps on damp mobile screens.
- Mobile screens reserve a fixed bottom safe area (72px to 88px) to host the single primary screen action. Content scrolls beneath this pinned utility bar with an 80px bottom padding buffer.

## Elevation & Depth

Visual hierarchy uses **low-contrast outlines complemented by subtle ambient containment**. Avoid theatrical drop shadows, skeuomorphic bevels, or colored underglows; clarity and low visual fatigue are paramount.

- **Level 0 (Canvas Base):** Flat `#F8FAFC`. Houses background operations and structural column tracks.
- **Level 1 (Card & Content Containers):** Pure `#FFFFFF` surface bordered by a clean 1px stroke of `#E2E8F0`. Elevation is conveyed through clean contrast against the off-white base, supported by an ambient drop: `0px 1px 3px rgba(15, 23, 42, 0.05)`.
- **Level 2 (Interactive Floating / Sticky Bars):** Sticky action bars, persistent footers, and active dropdown sheets. Surface is `#FFFFFF` with a 1px border `#E2E8F0`, accompanied by a directional soft shadow: `0px -4px 12px rgba(15, 23, 42, 0.06)` for bottom docks and `0px 6px 16px rgba(15, 23, 42, 0.08)` for menus.
- **Level 3 (Modals & Critical Alerts):** System dialogs and drawer backdrops. A dim overlay (`rgba(17, 24, 39, 0.45)`) combined with a crisp modal card backed by `0px 12px 24px -4px rgba(15, 23, 42, 0.12)`.

## Shapes

The design system implements a **`Rounded` (Level 2)** shape architecture. 

- **Inputs, Buttons, and Surface Chips:** 8px (`0.5rem`) corner radius. This balances friendly, accessible touch affordances with an efficient, professional toolset.
- **Cards and Grouped Inset Blocks:** 12px to 16px (`rounded-lg` / `rounded-xl`) corner radius, cleanly framing multi-item laundry orders, service selections, and pricing tiers.
- **Badges and Status Pills:** Fully circular radii (`9999px`) to immediately distinguish contextual status stamps from interactive buttons and rectangular card surfaces.

## Components

### Buttons & Actions
- **Rule of Single Intent:** Every mobile view features exactly one primary action button (e.g., "Complete Drop-off", "Move to Washing", "Mark as Claimed").
- **Primary Button:** Background `#10B981`, hover `#059669`, text `#FFFFFF`. Full width on mobile, 48px height minimum, 8px radius, font weight 600.
- **Secondary / Utility Button:** Background `#FFFFFF`, border 1px `#E2E8F0`, text `#1F2937`, hover `#F8FAFC`. Minimum 48px height.
- **Destructive Button:** Background `#FEF2F2`, border 1px `#FCA5A5`, text `#DC2626`.

### Cards & Stacked Lists
- Cards form the core mobile administrative abstraction.
- Each order item sits in a dedicated `#FFFFFF` container with 16px interior padding and a 1px `#E2E8F0` border.
- Layout order: Customer name and order number prominent at the top; status badge pinned to the top right; weight, service type (Wash & Fold, Dry Clean), and time elapsed grouped in the center; action triggers aligned at the base.

### Form Controls & Text Inputs
- **Vertical Stack Pattern:** Text labels sit strictly above the input field in 14px Semibold (`#1F2937`).
- **Input Fields:** Minimum height 48px, pure white surface, 1px `#CBD5E1` border, 16px text (`#111827`) to guarantee touch stability and legibility. Active state introduces a 2px outer ring in `#10B981` with a zero-delay transition.
- **Helper & Validation Copy:** Placed strictly beneath the input container at 13px (`#64748B` for context, `#EF4444` for invalid entries).

### Status Badges & Chips
- Compact, pill-shaped tags (`9999px` radius) with 4px vertical and 10px horizontal padding.
- Rendered in paired tone sets: tinted low-saturation background with dark matching foreground text for high-contrast scanning.
- Chips for multi-selection (e.g., fabric options, detergent preferences) utilize an unselected state of `#FFFFFF` with `#E2E8F0` border, transforming into `#ECFDF5` with `#10B981` border and text when toggled.

### Checkboxes, Steppers & Radios
- Minimum physical tap container of 44x44px, with the visual checkbox or radio node sized to 20x20px.
- Counter steppers for garment piece counts or weight (kg/lbs) use segmented 48px blocks with prominent, central numeric readouts and oversized `+` / `−` target icons.