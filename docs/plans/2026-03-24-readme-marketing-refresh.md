# Kelajak-Maskan README Marketing Refresh Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the stock Laravel README with a product-first README that markets Kelajak-Maskan to potential adopters while accurately describing the current product surface.

**Architecture:** Treat the README as a product landing page for the current release. Lead with brand and problem statement, describe the SQLite-backed history engine and CLI access that exist today, then add a short next-version section that points to the upcoming product surface without overstating shipped features.

**Tech Stack:** Markdown, Laravel 13, SQLite, Artisan CLI

---

### Task 1: Define The Product Snapshot Copy

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/docs/plans/2026-03-24-readme-marketing-design.md`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/README.md`

**Step 1: Confirm the approved README direction**

Use the approved design:

- aspirational and brand-led tone
- audience is potential adopters
- current reality is sold first
- next version is called out explicitly

**Step 2: Replace the stock framework framing**

Remove:

- Laravel logo block
- Laravel badges
- default Laravel sections

Replace them with:

- product title
- value proposition
- problem statement

### Task 2: Rewrite The README Around The Current Product

**Files:**
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/README.md`

**Step 1: Add product-facing sections**

Write these sections in order:

1. `Kelajak-Maskan`
2. `Why Kelajak-Maskan Exists`
3. `What It Does Today`
4. `Who It Is For`
5. `What's Coming Next`
6. `Getting Started`
7. `Current Status`

**Step 2: Keep the shipped surface accurate**

Only describe features that exist now:

- local SQLite-backed history
- wants, validations, plans, actions, outcomes, audit history
- CLI commands:
  - `history:latest-want`
  - `history:open-cycle`
  - `history:summary`
  - `history:backfill-foreign-keys`

**Step 3: Add a restrained next-version promise**

Describe the next version as:

- a more accessible product surface
- built on the existing history engine
- intended to make capture and review easier

### Task 3: Verify The Final README

**Files:**
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/README.md`

**Step 1: Read the final README**

Run:

```bash
sed -n '1,240p' README.md
```

Expected: the README contains product-facing sections and no stock Laravel boilerplate.

**Step 2: Review the diff**

Run:

```bash
git diff -- README.md docs/plans/2026-03-24-readme-marketing-design.md docs/plans/2026-03-24-readme-marketing-refresh.md
```

Expected: the diff shows a full README rewrite plus the new design and plan documents.

**Step 3: Report completion**

Summarize:

- what changed in the README
- that the Laravel default README content was removed
- how the README now balances current reality with next-version direction
