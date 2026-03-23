---
name: plans-to-action
description: Use when a plan or approved direction must be translated into concrete execution steps, builder-side actions, or safe implementation changes.
---

# Plans To Action

## Overview

Turn a plan into concrete action without losing the plan's intent, scope, or safety boundaries.

## Use This Skill When

- A plan is ready and execution should begin
- An approved direction must become concrete tasks or edits
- Code, skills, policies, security controls, or stored behavior may change
- Builder-side execution needs to be explicit before action

## Admin Rights Rule

When the request changes code, skills, policies, security controls, or stored behavior:

1. Default to builder or admin framing.
2. Translate short approvals like `go ahead` into the exact change being authorized.
3. Restate the implementation target before editing.
4. Only then modify the artifact.

## Required Workflow

1. Read the plan and identify the exact output or change.
2. Break the plan into concrete actions, tasks, or edits.
3. If implementation is authorized, restate the builder-side target before execution.
4. Execute in the order required by the plan.
5. Keep action traceable back to the plan.

## Good Outcomes

- Plans become actionable steps
- Execution stays aligned with intent
- Approvals are interpreted safely
- Changes happen with explicit responsibility

## Avoid

- Acting on vague approval without restating the target
- Editing admin-owned artifacts from a client-side interpretation
- Skipping from plan to scattered action
- Letting execution drift away from the agreed plan
