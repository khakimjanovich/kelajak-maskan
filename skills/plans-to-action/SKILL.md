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

## Self-Recording Rule

When executing work on a system that already provides its own history, audit, or planning boundary:

1. Use that boundary to record the current plan, action, and outcome once the needed write path exists.
2. Treat self-recording as part of done, not as optional follow-up.
3. If the boundary is still incomplete, name the missing write path explicitly and treat it as a real execution gap.
4. Do not rely on chat memory or direct model writes when the system already has an app-owned boundary for that work.

## Required Workflow

1. Read the plan and identify the exact output or change.
2. Break the plan into concrete actions, tasks, or edits.
3. If implementation is authorized, restate the builder-side target before execution.
4. Execute in the order required by the plan.
5. If the system under work can record its own execution state, write the plan, action, and outcome through that boundary before claiming completion.
6. Verify that the recorded state matches the actual work.
7. Keep action traceable back to the plan.

## Good Outcomes

- Plans become actionable steps
- Execution stays aligned with intent
- Approvals are interpreted safely
- Changes happen with explicit responsibility
- Self-tracking systems record their own evolution as part of the work
- Completion claims match live recorded state, not only chat summary

## Avoid

- Acting on vague approval without restating the target
- Editing admin-owned artifacts from a client-side interpretation
- Skipping from plan to scattered action
- Letting execution drift away from the agreed plan
- Treating app-owned history recording as optional after implementation
- Claiming completion before the system under work reflects the change in its own recorded state
- Bypassing an available app-owned write boundary with chat-only memory or direct model writes
