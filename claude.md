# Humanized Email Generation - OpportunityOS

## Overview

OpportunityOS now generates outreach emails that sound authentically human - not AI-generated. The system uses advanced prompt engineering to create emails that could plausibly be written by an executive search consultant.

## The Problem We Solved

**User Feedback:** "Generated emails sound too robotic and obviously AI-written"

**Root Causes:**
- Generic, formulaic language patterns
- AI tells like "I hope this email finds you well"
- Too long and overly structured
- Lack of personality variation
- No reference to specific opportunity details

## The Solution: Three Personality Styles

Instead of generic tones, we created three distinct personality styles based on how real people write business emails:

### 1. Direct/Brief (Sam Altman Style)
**Length:** 50-80 words
**Use Case:** When you want to get straight to the point

**Characteristics:**
- Super short, no fluff
- Fragment sentences are fine
- Skip pleasantries entirely
- Subject line: 3-5 words max

**Example:**
```
Subject: Quick Q

Saw your Series A note. We've placed 3 CTOs in similar stage companies. Worth a call?

[Your name]
```

### 2. Warm/Consultative
**Length:** 90-120 words
**Use Case:** Building rapport while staying professional

**Characteristics:**
- References specific details from their email
- Builds rapport naturally
- Helpful, not sales-y
- Conversational subject line: 5-7 words

**Example:**
```
Subject: Following up on your CRM project

I noticed you mentioned the CRM overhaul in your note. We did something similar with Acme Corp last year and learned some things the hard way.

Happy to share what worked if you're still figuring out the approach. The integration piece tends to be trickier than people expect.

Worth a quick call this week?

[Your name]
```

### 3. Professional/Formal
**Length:** 100-130 words
**Use Case:** Traditional executive search approach

**Characteristics:**
- Professional but not robotic
- Establishes credibility quickly
- Clear value proposition
- Formal subject line: 5-7 words

**Example:**
```
Subject: Regarding VP Engineering search

I'm reaching out regarding your recent posting for a VP of Engineering. My firm specializes in placing technical executives at growth-stage SaaS companies.

I have two qualified candidates currently in process who align well with the requirements outlined in your posting. Both have experience scaling engineering teams from 20 to 100+ and have worked in similar technology stacks.

Would you be open to a brief conversation this week to discuss?

[Your name]
```

## Anti-AI Prompt Engineering

### What We Explicitly Avoid

The system is instructed to NEVER use these AI tells:

❌ "I hope this email finds you well"
❌ "I wanted to reach out"
❌ "I'd love to connect"
❌ "I came across your..."
❌ Long formulaic intros
❌ Bullet points or structured formatting
❌ Perfect grammar (occasional fragments are fine)
❌ Overly enthusiastic tone

### What We Do Instead

✓ Start with a question, specific reference, or direct statement
✓ Use natural transitions ("Actually," "Quick thing," "So," "Anyway")
✓ Reference something specific from THEIR email
✓ Keep it conversational - like typing quickly between meetings
✓ 2-3 short paragraphs MAX
✓ One clear ask at the end
✓ Short, natural subject lines

## Structural Variation

Emails don't follow a single template. The AI randomly varies the structure:

1. **Question opener:** "Quick Q on the AI transformation project - is this already staffed?"
2. **Observation opener:** "Congrats on the Series B. Scaling from 20 to 100 engineers is chaos..."
3. **Direct statement:** "Saw your Series A note. We've placed 3 CTOs in similar companies."
4. **Context reference:** "Following up on your note about the CRM overhaul..."

This creates natural variation so emails don't feel templated.

## How It Works (Technical)

### Architecture

```
User clicks "Draft Email"
    ↓
Selects personality style (direct/warm/formal)
    ↓
Frontend sends: GET /api/draft.php?id={opportunityId}&tone={style}
    ↓
Backend fetches opportunity from database
    ↓
Builds context-aware prompt with:
    - Email subject from opportunity
    - Sender information
    - Company name
    - Email snippet/preview
    - Classification type (job/funding/consulting)
    ↓
Calls Claude API with personality-specific instructions
    ↓
Returns JSON: { subject: "...", body: "..." }
    ↓
Frontend displays draft for user to copy
```

### Key Files

**api/draft.php**
- Main API endpoint for draft generation
- Validates tone parameter (direct/warm/formal)
- Fetches opportunity data from database
- Constructs AI prompt with anti-AI instructions
- Calls Claude API
- Returns formatted draft

**dashboard.php**
- Frontend UI with modal for draft generation
- Tone selector dropdown (3 styles)
- Copy-to-clipboard functionality
- Regenerate capability

### Database Schema

The system pulls from the `opportunities` table:

```sql
CREATE TABLE opportunities (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    email_subject TEXT,      -- Used in draft context
    sender TEXT,             -- Used to extract name/company
    email_snippet TEXT,      -- Preview text for context
    company_name TEXT,       -- Referenced in draft
    classification TEXT,     -- job/funding/consulting
    relevance_score INTEGER,
    created_at DATETIME
)
```

## Prompt Engineering Deep Dive

### The Master Prompt Structure

```
1. CONTEXT SETTING
   "You're writing an outreach email to an actual person.
    This needs to sound like it came from an executive search consultant - not an AI."

2. THEIR EMAIL DATA
   - From, Subject, Preview, Company, Type

3. PERSONALITY INSTRUCTIONS
   - Style-specific examples and rules
   - Word count constraints
   - Voice characteristics

4. ANTI-AI PATTERNS
   - Explicit list of what NOT to say
   - Common AI tells to avoid

5. POSITIVE INSTRUCTIONS
   - What TO do instead
   - Natural transitions
   - Structural variation approaches

6. OUTPUT FORMAT
   - JSON with subject + body
   - Plain text with \n for line breaks
```

### Why This Works

1. **Negative Examples:** Telling Claude what NOT to write is as important as what TO write
2. **Concrete Examples:** Each personality style includes actual example emails, not just descriptions
3. **Word Count Constraints:** Forces brevity, which makes emails feel less AI-generated
4. **Structural Variation:** Randomized opening approaches prevent templated feel
5. **Context Integration:** Uses actual opportunity data for specific references

## User Journey

1. **Scan emails** → System finds opportunities in Gmail
2. **Review opportunities** → See jobs, funding, consulting leads
3. **Click "Draft Email"** → Modal opens
4. **Select style** → Choose direct/warm/formal from dropdown
5. **Email generates** → Claude API creates draft (2-3 seconds)
6. **Review draft** → See subject + body
7. **Copy or regenerate** → Use as-is or try different style
8. **Send from Gmail** → Paste into email client and send

## Testing Criteria

An email passes the "human test" if:

✅ You can't immediately tell it was AI-generated
✅ It references specific details from the opportunity
✅ It's SHORT (most AI emails are too long)
✅ It has natural imperfections (fragments, casual transitions)
✅ Subject line sounds natural, not clickbait-y
✅ No obvious AI language patterns
✅ Sounds like it came from someone typing quickly between meetings

## Examples by Classification Type

### Job Opportunity

**Input Data:**
- Subject: "Senior Engineering Manager - Series B Startup"
- Company: TechCorp
- Sender: hiring@techcorp.com
- Type: job

**Direct Style Output:**
```
Subject: re: Eng Manager role

Saw your posting. We've placed similar roles at 3 Series B companies this quarter.

Have a candidate in process who just scaled a team from 15 to 60. Worth discussing?
```

**Warm Style Output:**
```
Subject: Following up on your EM search

I noticed your posting for a Senior Engineering Manager. The requirements around scaling teams from startup to growth stage caught my eye - that's a specific transition that requires someone who's done it before.

We recently placed a similar role at a Series B fintech company. Happy to share what we learned about the profile that actually succeeds in this environment.

Worth comparing notes this week?
```

### Funding Announcement

**Input Data:**
- Subject: "TechStartup raises $20M Series A"
- Company: TechStartup
- Type: funding

**Direct Style Output:**
```
Subject: Congrats on Series A

Saw your funding news. Scaling eng teams from 10 to 50?

We've helped 3 companies at this stage. Quick call?
```

**Warm Style Output:**
```
Subject: Following your Series A announcement

Congrats on the $20M raise. The next phase - scaling from 10 to 50 engineers - is a specific kind of chaos. Different challenges than the 0 to 10 phase.

We helped a similar company navigate this last year. Happy to share what worked (and what didn't) if you're building out the team.

Worth a conversation?
```

### Consulting Lead

**Input Data:**
- Subject: "Looking for CRM implementation partner"
- Company: Enterprise Corp
- Type: consulting

**Formal Style Output:**
```
Subject: Regarding CRM implementation inquiry

I'm reaching out regarding your CRM implementation project. Our firm specializes in enterprise CRM transformations, with particular expertise in Salesforce implementations for companies in your industry vertical.

We recently completed a similar project for a Fortune 500 company that resulted in 40% improvement in sales cycle efficiency. I have availability this week to discuss your specific requirements and share relevant case studies.

Would Thursday or Friday work for a brief call?
```

## Configuration

### Environment Variables

Set in `config/config.php`:

```php
define('ANTHROPIC_API_KEY', 'your-api-key-here');
```

### Model Configuration

Currently using: `claude-sonnet-4-20250514`
Max tokens: 2048
Typical response: ~300-500 tokens

### Tone Defaults

Default tone if none specified: `warm`
Valid tones: `['direct', 'warm', 'formal']`

## API Reference

### GET /api/draft.php

**Parameters:**
- `id` (required): Opportunity ID from database
- `tone` (optional): One of `direct`, `warm`, `formal` (default: `warm`)

**Response:**
```json
{
  "success": true,
  "draft": {
    "subject": "Short natural subject line",
    "body": "Email body with\nline breaks"
  }
}
```

**Error Response:**
```json
{
  "error": "Error message here"
}
```

**Status Codes:**
- 200: Success
- 401: Not authenticated
- 400: Missing opportunity ID
- 404: Opportunity not found
- 500: Draft generation failed

## Future Enhancements

### Potential Improvements

1. **User Customization**
   - Allow users to save preferred tone
   - Custom tone profiles ("My Voice")
   - Industry-specific variations

2. **Learning from Edits**
   - Track which emails users edit before sending
   - Learn patterns from user modifications
   - Improve prompts based on feedback

3. **A/B Testing**
   - Generate 2-3 variations simultaneously
   - Let users choose best one
   - Learn which approaches work

4. **Context Enrichment**
   - Pull full email content (not just snippet)
   - Analyze email threads
   - Research company background automatically

5. **Follow-up Templates**
   - Generate follow-up emails
   - Handle responses
   - Build email sequences

6. **Tone Mixing**
   - "Warm but brief"
   - "Formal but friendly"
   - Slider controls instead of discrete options

## Performance

**Typical Response Times:**
- Database query: <10ms
- Claude API call: 1.5-3 seconds
- Total: ~2-3 seconds

**Cost per Draft:**
- Input tokens: ~800-1000
- Output tokens: ~200-400
- Cost: ~$0.01-0.02 per draft (Claude Sonnet 4 pricing)

## Success Metrics

### Qualitative
- Emails don't immediately sound AI-generated
- Users can use drafts without editing
- Natural variation between drafts

### Quantitative (Future)
- % of drafts used without editing
- User regeneration rate (lower is better)
- Time saved per opportunity

## Troubleshooting

### "Draft sounds too AI-generated"
→ Try Direct style (forces brevity)
→ Regenerate 2-3 times for variation
→ Check if email_snippet has enough context

### "Not referencing specific details"
→ Ensure email_snippet field is populated
→ Check classification accuracy
→ May need more context in database

### "Too long"
→ Use Direct style (50-80 words)
→ Check word count constraints in prompt

### "Too short"
→ Use Warm or Formal style
→ Ensure email_snippet provides context

## Files Modified

```
api/draft.php              - Core generation logic + prompts
dashboard.php              - Frontend UI + tone selector
HUMANIZE_EMAIL_IMPROVEMENTS.md - Technical documentation
claude.md                  - This file
```

## Lessons Learned

1. **Negative prompting matters:** Telling AI what NOT to write is crucial
2. **Examples > Descriptions:** Showing the voice works better than describing it
3. **Brevity = Human:** Shorter emails feel less AI-generated
4. **Variation prevents detection:** Random structure choices help
5. **Context is king:** Referencing specific details makes it feel real

---

## Quick Start

```bash
# 1. Ensure ANTHROPIC_API_KEY is set in config/config.php

# 2. User workflow:
#    - Log in to OpportunityOS
#    - Click "Scan My Emails"
#    - Wait for opportunities to populate
#    - Click "Draft Email" on any opportunity
#    - Select tone: Direct/Warm/Formal
#    - Copy draft to clipboard
#    - Paste in Gmail and send

# 3. Test different tones:
#    - Direct: Super short, gets to point
#    - Warm: References specifics, builds rapport
#    - Formal: Traditional professional
```

## Support

**Issues:** https://github.com/jaykalam/opportunityos-main/issues
**Documentation:** This file
**API:** See api/draft.php comments

---

**Last Updated:** 2025-11-29
**Feature Branch:** `claude/humanize-email-generation-01FoqipjAs3hjnBN5G1mDeMc`
**Status:** ✅ Ready for production
