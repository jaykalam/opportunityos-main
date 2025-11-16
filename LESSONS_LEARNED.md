Building with Claude Code for Web: Lessons Learned
Based on Real Experience Building OpportunityOS

Executive Summary
After spending 4+ hours and building a complete application with 9 parallel agents, here's what actually works vs. what the tutorials promise.
Bottom Line: Parallel multi-agent workflows sound amazing but fail in practice for new projects. Sequential single-agent builds are 10x more reliable.

What Went Wrong Today
1. The Parallel Agent Trap
What We Did:

Launched 9 agents simultaneously
Each agent built features in isolation
Expected them to merge seamlessly

What Actually Happened:

Agent 1 built a landing page
Agent 2 built authentication system
Agent 3 built COMPLETE app (auth + email scanner + database)
Agent 5 built ANOTHER complete app
Agent 6 built payment system

Result: 6 separate applications instead of 1 cohesive codebase
Cost: ~$13 in API calls, 4+ hours, massive merge conflicts

2. Repository Authorization Hell
The Problem:

Claude Code for Web can't push to new GitHub repos without authorization
Each agent runs in isolated environment
Network restrictions prevent git push

What Happened:

Spent 2 hours trying to get agents to push code
Had to manually create repos
Had to manually merge code
GitHub Desktop became the workaround

The Fix for Next Time:

Create GitHub repo FIRST (empty, on GitHub web)
Use ONE agent
Let it clone, build, push sequentially


3. The "No Coding Experience" Assumption
Reality Check:
You still need to understand:

File structures and folder hierarchies
Git concepts (clone, commit, push, pull)
Command line basics
Environment variables
Dependencies and package managers

What This Means:
Claude Code is NOT "no-code" - it's "less-code" with AI assistance.

What Actually Works: The Proven Workflow
Blueprint for Next Project
Phase 1: Planning (30 mins)

Write ONE clear feature description
Define your MVP (one core feature only)
Choose tech stack (stick with: PHP + SQLite for simplicity)
Sketch database schema on paper

Phase 2: Setup (15 mins)

Create GitHub repo (empty) via web interface
Clone to local machine
Open ONE Claude Code session
Give it complete context in first prompt

Phase 3: Sequential Build (2-4 hours)
Build features ONE AT A TIME:

Authentication first
Database second
Core feature third
Additional features fourth
Polish last

Test after EACH feature before moving to next
Phase 4: Deploy (30 mins)

Push to GitHub
Deploy to Railway/Heroku/PHP hosting
Test in production
Iterate


The Working Prompt Framework
Initial Project Prompt Template
CONTEXT:
I'm building [app name] - [one sentence description of what it does].

TARGET USERS: [who will use this]

MVP FEATURE: [the ONE core thing it must do]

TECH STACK:
- Backend: PHP 8+ with SQLite
- Frontend: HTML + Tailwind CSS + Vanilla JS
- Auth: Google OAuth (not email/password)
- APIs: [list any external APIs needed]

USER JOURNEY:
1. User lands on homepage
2. User clicks "Sign in with Google"
3. User [does the core action]
4. User sees [the result]

DATABASE NEEDS:
- Users table: [list fields]
- [Other tables]: [list fields]

DESIGN DIRECTION:
- Style: [Minimal/Modern/Professional/etc]
- Colors: [Primary and accent colors]
- Inspiration: [e.g., "Like Stripe's clean aesthetic"]

CONSTRAINTS:
- This is a solo project
- Must deploy to standard PHP hosting
- Keep dependencies minimal
- Mobile-responsive required

BUILD APPROACH:
1. Set up authentication first
2. Create database schema
3. Build [core feature]
4. Add basic UI
5. Test and deploy

Please start by creating the project structure and authentication system.

Feature Addition Prompt Template
CONTEXT:
Adding [feature name] to existing [app name] application.

CURRENT STATE:
- Authentication: [Working/Not started/etc]
- Database: [What tables exist]
- Existing features: [List what works]

NEW FEATURE:
[Clear description of what you're adding]

USER FLOW:
1. User [action]
2. System [response]
3. User sees [result]

TECHNICAL REQUIREMENTS:
- Database changes: [New tables or fields needed]
- API integrations: [If any]
- Files to create/modify: [If known]

DESIGN:
- Match existing styling
- [Any specific UI requirements]

TESTING CRITERIA:
Feature works when [specific test case passes]

Please implement this feature, test it, and confirm it works before committing.

Critical Rules for Success
Rule 1: One Agent, One Session, One Feature
DO:

Use ONE Claude Code session per feature
Build sequentially
Test before moving on

DON'T:

Launch multiple agents in parallel
Try to build everything at once
Move to next feature with bugs


Rule 2: GitHub First, Build Second
DO:

Create empty repo on GitHub first
Clone it locally
Let agent work in cloned folder
Push frequently

DON'T:

Try to create repo via CLI in Claude Code
Build locally then create repo later
Work without version control


Rule 3: Simple Stack = Success
Recommended for Solo Builders:

✅ PHP + SQLite (LLMs understand this well)
✅ Minimal dependencies
✅ Single-file components when possible

Avoid:

❌ Next.js/React (too complex for LLMs)
❌ Microservices architecture
❌ Excessive npm packages


Rule 4: Test Religiously
After EVERY feature:

Run the app locally
Click through the feature
Test with real data
Fix bugs immediately
Commit only when working

Never accumulate bugs across features.

Cost Management
What We Spent Today:

Design system: ~$3
9 parallel agents (mostly wasted): ~$10
Total: ~$13

What It Should Cost:

Well-planned single agent: $5-15 total
Sequential feature builds: $2-5 per feature

How to Save Money:

Plan before prompting (clear requirements = fewer iterations)
Use ONE agent sequentially
Test frequently (catch bugs early = less debugging cost)
Use Haiku for simple tasks, Sonnet for complex
Clear context between features (prevent confusion)


Common Pitfalls & Solutions
Pitfall 1: "Build my entire app"
Problem: Overwhelming the LLM with too much scope
Solution: Break into phases

Week 1: Auth + Database
Week 2: Core feature
Week 3: Additional features
Week 4: Polish + Deploy


Pitfall 2: Wrong tech stack
Problem: Choosing Next.js/React because it's "modern"
Why it fails: LLMs struggle with complex multi-file architectures
Solution: Use PHP + SQLite for MVPs

Single-file components
Minimal dependencies
LLMs understand it deeply
Easy to deploy


Pitfall 3: No database planning
Problem: Adding database later requires complete rebuild
Solution: Sketch schema BEFORE first prompt

Users table (always needed)
Core data tables
Relationship fields
Include in initial prompt


Pitfall 4: Vague prompts
Bad: "Make it look good"
Good: "Use Tailwind with teal accent (#00d4aa), inspired by Stripe's minimal aesthetic"
Bad: "Add user authentication"
Good: "Implement Google OAuth 2.0 for login, store user data in SQLite users table with fields: id, google_id, email, name, created_at"

The Reality of AI Coding Tools
What They're Actually Good For:
✅ Rapid prototyping
✅ Boilerplate generation
✅ Standard CRUD operations
✅ UI implementation from clear specs
✅ Code refactoring
✅ Bug fixing with clear reproduction steps
What They Struggle With:
❌ Architecture decisions across many files
❌ Complex state management
❌ Performance optimization
❌ Security best practices (always audit)
❌ Ambiguous requirements
❌ "Make it better" without specifics

Next Project Checklist
Before Starting:

 Clear one-sentence description of app
 MVP defined (one core feature only)
 Database schema sketched
 Tech stack chosen (PHP + SQLite recommended)
 GitHub repo created (empty)

During Build:

 ONE Claude Code session active
 Building ONE feature at a time
 Testing after each feature
 Committing working code frequently
 No parallel agents

Before Deploy:

 All features tested locally
 Security review completed
 Environment variables documented
 README with setup instructions
 Deployment target chosen


Quick Reference: When to Use What
Use Claude Code for Web When:

Building web applications
Need to see UI immediately
Working from phone/tablet
Want browser-based workflow

Use Terminal Claude Code When:

Building CLI tools
Need local file system access
Prefer command-line workflow
Have coding experience

Use Lovable/Bolt When:

Need rapid UI prototyping
Want visual development
Building simple frontend-heavy apps
Don't need complex backend

Use Traditional Coding When:

Building production systems at scale
Need complete control
Have specific performance requirements
Security is critical


Final Wisdom
What Actually Matters:

Shipping beats perfection - OpportunityOS v1.0 works, even with rough edges
Sequential beats parallel - One solid feature is better than five broken ones
Simple beats complex - PHP + SQLite ships faster than Next.js + Prisma
Testing beats hoping - Click every button after every change
Learning beats tutorials - Your mistakes teach more than videos

The Real Lesson:
AI coding tools are AMPLIFIERS, not replacements.
They amplify:

Clear thinking → Great results
Vague ideas → Wasted time
Good planning → Fast execution
Poor planning → Expensive confusion


Your Next Build Will Be Different
Now you know:

✅ How to structure prompts
✅ When to use parallel vs sequential
✅ How to avoid repository issues
✅ What tech stack actually works
✅ How to manage costs
✅ How to test properly
✅ How to ship despite imperfection

Use this document. Don't repeat today's mistakes.
Build v2.0 in 2 hours instead of 4.

Document Version: 1.0
Date: November 16, 2025
Based On: Real OpportunityOS build session
Status: Battle-tested, not theoretical
