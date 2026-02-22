---
description: Session handoff — summarize work, update state.md, commit, push, and generate a resume string.
---

# /handoff Workflow

// turbo-all

When this workflow is invoked, perform the following steps in order:

## 1. Summarize Session Work

Review all changes made during this session. Create a concise bullet-point summary of:

- Files created, modified, or deleted
- Features implemented or bugs fixed
- Decisions made and their rationale

## 2. Update `docs/state.md`

Open `docs/state.md` and update the following sections:

- **Current Context**: Replace with a summary of what was changed in this session.
- **Technical Debt**: Add any new TODOs, hacks, or shortcuts introduced. Remove any items that were resolved.
- **Next Steps**: Write precise, actionable instructions for the next agent session. Be specific — include file paths, function names, and section references from `REQUIREMENTS.md`.
- **The 'Why' Log**: If any complex or non-obvious implementation decisions were made, document them here.
- **Blocking Issues**: Add anything that was attempted but failed, including error messages or API responses.

## 3. Stage All Changes

```bash
git add -A
```

## 4. Commit with Blackboard Prefix

```bash
git commit -m "agent(state): [One-line summary of session work]"
```

Use a descriptive message, e.g.:

- `agent(state): implement application submission workflow and update state`
- `agent(state): fix property card styling and document EUR formatting decisions`

## 5. Push the Branch

```bash
git push origin HEAD
```

If the branch has no upstream yet:

```bash
git push -u origin HEAD
```

## 6. Generate Resume String

Output a **Resume String** — a single paragraph that can be pasted into a new chat to instantly reboot the agent's context. Format:

> **Resume String:**  
> "Continue development on the `leaselink-core` WordPress plugin (`/Users/macbookpro/Local Sites/leaselink/app/public/wp-content/plugins/leaselink-core`). I'm on branch `[branch-name]`. Read `docs/state.md` for full context. Last session: [1-2 sentence summary]. Priority next steps: [top 2-3 items from Next Steps]. Run `git log -p docs/state.md` for evolution history."

---

## Important Notes

- **Never skip the state.md update** — this is the most critical step.
- **Never commit to `main` directly** — always use feature branches.
- **The resume string must be self-contained** — a brand new agent session should be able to start working immediately from it.
